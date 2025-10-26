<?php

namespace App\Services\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class SupportBotService
{
    public function sendMessage(string $sessionId, string $message, array $context = []): SupportBotResponse
    {
        $driver = config('support.bot.driver', 'dialogflow');

        try {
            return match ($driver) {
                'rasa' => $this->sendToRasa($sessionId, $message, $context),
                default => $this->sendToDialogflow($sessionId, $message, $context),
            };
        } catch (\Throwable $exception) {
            Log::warning('Support bot driver failed, using fallback.', [
                'driver' => $driver,
                'session_id' => $sessionId,
                'message' => $message,
                'exception' => $exception->getMessage(),
            ]);
        }

        return $this->fallbackResponse($message);
    }

    protected function sendToDialogflow(string $sessionId, string $message, array $context = []): SupportBotResponse
    {
        $settings = config('support.bot.dialogflow');
        if (empty($settings['endpoint']) || empty($settings['token']) || empty($settings['project_id'])) {
            throw new RuntimeException('Dialogflow endpoint, token or project id has not been configured.');
        }

        $languageCode = $settings['language_code'] ?? 'en';
        $endpoint = rtrim($settings['endpoint'], '/');
        $url = sprintf(
            '%s/v2/projects/%s/agent/sessions/%s:detectIntent',
            $endpoint,
            $settings['project_id'],
            $sessionId
        );

        $payload = [
            'queryInput' => [
                'text' => [
                    'text' => $message,
                    'languageCode' => $languageCode,
                ],
            ],
        ];

        if (!empty($context)) {
            $payload['queryParams'] = ['payload' => $context];
        }

        $response = Http::withToken($settings['token'])->acceptJson()->post($url, $payload);

        if ($response->failed()) {
            throw new RuntimeException('Dialogflow request failed: ' . $response->body());
        }

        $data = $response->json('queryResult');
        if (!$data) {
            throw new RuntimeException('Dialogflow response missing queryResult.');
        }

        $reply = $this->extractDialogflowReply($data);
        $intent = Arr::get($data, 'intent.displayName');
        $confidence = Arr::get($data, 'intentDetectionConfidence');

        $handoff = $this->shouldHandoff($message, $intent, $confidence, Arr::get($data, 'fulfillmentMessages', []));

        return new SupportBotResponse(
            reply: $reply,
            intent: $intent,
            confidence: $confidence !== null ? (float) $confidence : null,
            handoff: $handoff,
            metadata: [
                'driver' => 'dialogflow',
                'contexts' => Arr::get($data, 'outputContexts', []),
            ]
        );
    }

    protected function extractDialogflowReply(array $data): string
    {
        $reply = Arr::get($data, 'fulfillmentText');

        if (!$reply && isset($data['fulfillmentMessages']) && is_array($data['fulfillmentMessages'])) {
            foreach ($data['fulfillmentMessages'] as $message) {
                $text = Arr::get($message, 'text.text');
                if (is_array($text) && count($text)) {
                    $reply = $text[0];
                    break;
                }
            }
        }

        return $reply ?: $this->fallbackResponse('')->reply;
    }

    protected function sendToRasa(string $sessionId, string $message, array $context = []): SupportBotResponse
    {
        $endpoint = rtrim((string) config('support.bot.rasa.endpoint'), '/');
        if (!$endpoint) {
            throw new RuntimeException('Rasa endpoint has not been configured.');
        }

        $payload = array_merge([
            'sender' => $sessionId,
            'message' => $message,
        ], $context);

        $response = Http::acceptJson()->post($endpoint, $payload);
        if ($response->failed()) {
            throw new RuntimeException('Rasa request failed: ' . $response->body());
        }

        $messages = $response->json();
        if (!is_array($messages) || empty($messages)) {
            throw new RuntimeException('Rasa response missing messages.');
        }

        $reply = null;
        $intent = null;
        $confidence = null;
        $handoff = false;
        $metadata = ['driver' => 'rasa'];

        foreach ($messages as $messagePayload) {
            if (!$reply && isset($messagePayload['text'])) {
                $reply = $messagePayload['text'];
            }

            if (!$intent && isset($messagePayload['metadata']['intent'])) {
                $intent = $messagePayload['metadata']['intent'];
            }

            if ($confidence === null && isset($messagePayload['metadata']['confidence'])) {
                $confidence = (float) $messagePayload['metadata']['confidence'];
            }

            if (isset($messagePayload['metadata']['handoff']) && $messagePayload['metadata']['handoff'] === true) {
                $handoff = true;
            }

            if (isset($messagePayload['custom']) && is_array($messagePayload['custom'])) {
                $metadata['custom'] = $messagePayload['custom'];
                if (!$handoff && !empty($messagePayload['custom']['handoff'])) {
                    $handoff = (bool) $messagePayload['custom']['handoff'];
                }
            }
        }

        $reply ??= $this->fallbackResponse('')->reply;
        $handoff = $handoff || $this->shouldHandoff($message, $intent, $confidence);

        return new SupportBotResponse(
            reply: $reply,
            intent: $intent,
            confidence: $confidence,
            handoff: $handoff,
            metadata: $metadata
        );
    }

    protected function shouldHandoff(string $userMessage, ?string $intent, ?float $confidence, array $messages = []): bool
    {
        $threshold = (float) config('support.bot.confidence_threshold', 0.55);

        if ($confidence !== null && $confidence < $threshold) {
            return true;
        }

        if ($intent && Str::contains(Str::lower($intent), ['handoff', 'agent', 'operator', 'support'])) {
            return true;
        }

        if ($this->containsHandoffKeyword($userMessage)) {
            return true;
        }

        foreach ($messages as $message) {
            if (Arr::get($message, 'payload.handoff', false) === true) {
                return true;
            }
        }

        return false;
    }

    protected function containsHandoffKeyword(string $message): bool
    {
        $keywords = ['human', 'agent', 'operator', 'representative', 'staff'];
        $normalized = Str::lower($message);

        foreach ($keywords as $keyword) {
            if (Str::contains($normalized, $keyword)) {
                return true;
            }
        }

        return false;
    }

    protected function fallbackResponse(string $message): SupportBotResponse
    {
        $fallbacks = config('support.bot.fallback_responses', []);
        $reply = $fallbacks['default'] ?? 'I\'m sorry, I could not process that. Would you like me to connect you with an operator?';
        $handoff = true;

        if (!$message || Str::length(trim($message)) === 0) {
            $reply = $fallbacks['greeting'] ?? 'Hello! How can I help you today?';
            $handoff = false;
        }

        return new SupportBotResponse(reply: $reply, handoff: $handoff, metadata: ['driver' => 'fallback']);
    }
}
