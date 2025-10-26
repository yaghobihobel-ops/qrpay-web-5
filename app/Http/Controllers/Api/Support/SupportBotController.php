<?php

namespace App\Http\Controllers\Api\Support;

use App\Http\Controllers\Controller;
use App\Http\Requests\Support\SendBotMessageRequest;
use App\Models\SupportBotMessage;
use App\Models\SupportBotSession;
use App\Services\Support\SupportBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class SupportBotController extends Controller
{
    public function __construct(private readonly SupportBotService $botService)
    {
    }

    public function message(SendBotMessageRequest $request): JsonResponse
    {
        $sessionId = $request->string('session_id')->whenEmpty(fn () => Str::uuid()->toString())->toString();
        $context = $request->input('context', []);
        $locale = $request->input('locale');

        $session = SupportBotSession::firstOrNew(['session_id' => $sessionId]);
        $session->last_interaction_at = now();

        if ($request->user() && !$session->user_id) {
            $session->user_id = $request->user()->getAuthIdentifier();
        }

        if ($locale) {
            $session->locale = $locale;
        }

        $session->save();

        SupportBotMessage::create([
            'support_bot_session_id' => $session->id,
            'sender' => 'user',
            'message' => $request->input('message'),
            'metadata' => empty($context) ? null : ['context' => $context],
        ]);

        $response = $this->botService->sendMessage($session->session_id, $request->input('message'), $context);

        SupportBotMessage::create([
            'support_bot_session_id' => $session->id,
            'sender' => 'bot',
            'message' => $response->reply,
            'intent' => $response->intent,
            'confidence' => $response->confidence,
            'metadata' => $response->metadata,
        ]);

        if ($response->handoff) {
            $session->handoff_recommended = true;
            $session->save();
        }

        return response()->json([
            'data' => [
                'session_id' => $session->session_id,
                'support_bot_session_id' => $session->id,
                'reply' => $response->reply,
                'intent' => $response->intent,
                'confidence' => $response->confidence,
                'handoff' => $response->handoff,
                'metadata' => $response->metadata,
            ],
        ]);
    }
}
