<?php

namespace App\Services\Monitoring;

use App\Models\HealthCheck;
use App\Notifications\HealthCheckAlert;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Throwable;

class HealthCheckService
{
    public const STATUS_HEALTHY = 'healthy';
    public const STATUS_DEGRADED = 'degraded';
    public const STATUS_DOWN = 'down';

    protected array $providers;

    public function __construct(?array $providers = null)
    {
        $this->providers = $providers ?? config('monitoring.providers', []);
    }

    public function checkAll(): array
    {
        $results = [];

        foreach ($this->providers as $provider) {
            if (empty($provider['url'])) {
                continue;
            }

            $results[] = $this->check($provider);
        }

        return $results;
    }

    public function check(array $provider): array
    {
        $provider = $this->normaliseProvider($provider);
        $startedAt = microtime(true);
        $status = self::STATUS_DOWN;
        $statusCode = null;
        $message = null;
        $latency = null;
        $response = null;

        try {
            $method = strtoupper($provider['method'] ?? 'GET');
            $timeout = $provider['timeout'] ?? config('monitoring.defaults.timeout', 5);
            $response = Http::timeout($timeout)
                ->withOptions($provider['http_options'] ?? [])
                ->send($method, $provider['url'], $this->buildRequestOptions($provider));

            $latency = (int) round((microtime(true) - $startedAt) * 1000);
            $statusCode = $response->status();
            $status = $response->successful() ? self::STATUS_HEALTHY : self::STATUS_DOWN;

            if (! $response->successful()) {
                $message = $response->body();
            }
        } catch (Throwable $exception) {
            $latency = (int) round((microtime(true) - $startedAt) * 1000);
            $message = $exception->getMessage();
        }

        $threshold = $provider['latency_threshold'] ?? config('monitoring.defaults.latency_threshold');
        if ($status === self::STATUS_HEALTHY && $threshold !== null && $latency !== null && $latency > (int) $threshold) {
            $status = self::STATUS_DEGRADED;
        }

        $record = HealthCheck::create([
            'provider' => $provider['slug'],
            'status' => $status,
            'latency' => $latency,
            'status_code' => $statusCode,
            'message' => $message,
            'meta' => [
                'name' => $provider['name'],
                'url' => $provider['url'],
                'response' => $this->resolveResponseBody($response),
            ],
            'checked_at' => now(),
        ]);

        $result = [
            'slug' => $provider['slug'],
            'name' => $provider['name'],
            'status' => $status,
            'latency_ms' => $latency,
            'status_code' => $statusCode,
            'message' => $message,
            'checked_at' => $record->checked_at,
        ];

        if ($this->shouldAlert($status)) {
            $this->dispatchAlerts($provider, $result);
        }

        return $result;
    }

    public function normaliseProvider(array $provider): array
    {
        $provider['name'] = $provider['name'] ?? $provider['slug'] ?? $provider['url'];
        $provider['slug'] = $provider['slug'] ?? Str::slug($provider['name']);
        $provider['method'] = strtoupper($provider['method'] ?? 'GET');

        return $provider;
    }

    public function determineOverallStatus(array $results): string
    {
        $priority = [
            self::STATUS_DOWN => 3,
            self::STATUS_DEGRADED => 2,
            self::STATUS_HEALTHY => 1,
        ];

        $score = self::STATUS_HEALTHY;
        foreach ($results as $result) {
            $status = $result['status'] ?? self::STATUS_HEALTHY;
            if ($priority[$status] > $priority[$score]) {
                $score = $status;
            }
        }

        return $score;
    }

    protected function buildRequestOptions(array $provider): array
    {
        $options = [];

        foreach (['query', 'json', 'form_params', 'body'] as $key) {
            if (array_key_exists($key, $provider)) {
                $options[$key] = $provider[$key];
            }
        }

        return $options;
    }

    protected function resolveResponseBody(?Response $response): mixed
    {
        if (! $response) {
            return null;
        }

        try {
            $json = $response->json();
            return $json;
        } catch (Throwable) {
            return $response->body();
        }
    }

    protected function shouldAlert(string $status): bool
    {
        return $status !== self::STATUS_HEALTHY;
    }

    protected function dispatchAlerts(array $provider, array $result): void
    {
        $alerts = config('monitoring.alerts', []);

        if (Arr::get($alerts, 'mail.enabled') && ($recipients = Arr::get($alerts, 'mail.recipients', []))) {
            Notification::route('mail', $recipients)->notify(new HealthCheckAlert($provider, $result));
        }

        if (Arr::get($alerts, 'slack.enabled') && ($url = Arr::get($alerts, 'slack.webhook_url'))) {
            $this->safeHttpPost($url, [
                'text' => sprintf(':%s: %s is %s (latency: %sms)',
                    $result['status'] === self::STATUS_DOWN ? 'x' : 'warning',
                    $provider['name'],
                    strtoupper($result['status']),
                    $result['latency_ms'] ?? 'n/a'
                ),
            ]);
        }

        if (Arr::get($alerts, 'webhook.enabled') && ($url = Arr::get($alerts, 'webhook.url'))) {
            $payload = [
                'provider' => $provider['slug'],
                'name' => $provider['name'],
                'status' => $result['status'],
                'latency_ms' => $result['latency_ms'],
                'checked_at' => $this->formatCarbon($result['checked_at']),
                'message' => $result['message'],
            ];

            $headers = [];
            if ($secret = Arr::get($alerts, 'webhook.secret')) {
                $encodedPayload = json_encode($payload);
                if ($encodedPayload !== false) {
                    $headers['X-Health-Signature'] = hash_hmac('sha256', $encodedPayload, $secret);
                }
            }

            $this->safeHttpPost($url, $payload, $headers);
        }
    }

    protected function safeHttpPost(string $url, array $payload, array $headers = []): void
    {
        try {
            Http::withHeaders($headers)->post($url, $payload);
        } catch (Throwable $exception) {
            Log::warning('Health alert notification failed', [
                'url' => $url,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    protected function formatCarbon(CarbonInterface|string|null $value): ?string
    {
        if ($value instanceof CarbonInterface) {
            return $value->toIso8601String();
        }

        return $value;
    }
}
