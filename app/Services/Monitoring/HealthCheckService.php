<?php

namespace App\Services\Monitoring;

use App\Models\Monitoring\ServiceHealthCheck;
use App\Notifications\Monitoring\ServiceAlertNotification;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class HealthCheckService
{
    /**
     * Run health checks for all configured services.
     */
    public function run(?string $serviceFilter = null): Collection
    {
        $results = collect();

        foreach ($this->configuredServices() as $type => $services) {
            foreach ($services as $name => $configuration) {
                if ($serviceFilter && $serviceFilter !== $name) {
                    continue;
                }

                $results->push($this->checkAndPersist($type, $name, $configuration));
            }
        }

        return $results;
    }

    /**
     * Get the configured services grouped by type.
     */
    protected function configuredServices(): array
    {
        return [
            'provider' => config('monitoring.providers', []),
            'internal' => config('monitoring.internal', []),
        ];
    }

    /**
     * Run a single service check and persist the result.
     */
    protected function checkAndPersist(string $type, string $name, array $configuration): array
    {
        $previousStatus = ServiceHealthCheck::query()
            ->where('service_name', $name)
            ->where('service_type', $type)
            ->latest('id')
            ->value('status');

        $result = $this->performCheck($type, $name, $configuration);

        ServiceHealthCheck::query()->create([
            'service_name' => $name,
            'service_type' => $type,
            'status' => $result['status'],
            'latency_ms' => $result['latency_ms'],
            'error_message' => $result['error_message'],
            'meta' => $result['meta'],
            'checked_at' => $result['checked_at'],
        ]);

        $this->maybeAlert($result, $previousStatus);

        return $result;
    }

    /**
     * Execute the network call and prepare the result payload.
     */
    protected function performCheck(string $type, string $name, array $configuration): array
    {
        $endpoint = Arr::get($configuration, 'endpoint');
        $timeout = (float) Arr::get($configuration, 'timeout', 5);
        $retries = (int) Arr::get($configuration, 'retries', 1);
        $latencyThreshold = (float) Arr::get($configuration, 'latency_warning', 1500);
        $maxErrorRate = Arr::get($configuration, 'max_error_rate');
        $maxFee = Arr::get($configuration, 'max_fee');

        $checkedAt = CarbonImmutable::now();
        $status = 'up';
        $errorMessage = null;
        $meta = [];
        $latencyMs = null;

        if (!$endpoint) {
            $status = 'degraded';
            $errorMessage = 'Endpoint is not configured';
            Log::warning('Health check skipped because endpoint is missing.', compact('type', 'name'));

            return [
                'service_name' => $name,
                'service_type' => $type,
                'status' => $status,
                'latency_ms' => $latencyMs,
                'error_message' => $errorMessage,
                'meta' => $meta,
                'checked_at' => $checkedAt,
                'message' => 'Missing endpoint configuration',
            ];
        }

        try {
            $start = microtime(true);
            $response = Http::timeout($timeout)
                ->retry(max($retries, 1), 100)
                ->get($endpoint);
            $latencyMs = round((microtime(true) - $start) * 1000, 2);

            if ($response->failed()) {
                $status = 'down';
                $errorMessage = sprintf('Service responded with status %s', $response->status());
            } else {
                if ($latencyMs > $latencyThreshold) {
                    $status = 'degraded';
                    $meta['latency_threshold'] = $latencyThreshold;
                }

                $payload = $this->extractPayload($response->json());
                $meta = array_merge($meta, $payload);

                if ($maxErrorRate !== null && isset($payload['error_rate']) && $payload['error_rate'] > $maxErrorRate) {
                    $status = 'degraded';
                    $meta['error_rate_threshold'] = $maxErrorRate;
                }

                if ($maxFee !== null && isset($payload['fee']) && $payload['fee'] > $maxFee) {
                    $status = 'degraded';
                    $meta['fee_threshold'] = $maxFee;
                }
            }
        } catch (Throwable $throwable) {
            $status = 'down';
            $errorMessage = $throwable->getMessage();
            Log::error('Health check failed', [
                'service' => $name,
                'type' => $type,
                'exception' => $throwable,
            ]);
        }

        $message = $this->buildMessage($name, $status, $errorMessage, $meta, $latencyMs);

        return [
            'service_name' => $name,
            'service_type' => $type,
            'status' => $status,
            'latency_ms' => $latencyMs,
            'error_message' => $errorMessage,
            'meta' => $meta,
            'checked_at' => $checkedAt,
            'message' => $message,
        ];
    }

    /**
     * Extracts the relevant payload fields from the response body.
     */
    protected function extractPayload(?array $payload): array
    {
        if (!$payload) {
            return [];
        }

        return [
            'error_rate' => Arr::get($payload, 'error_rate', Arr::get($payload, 'metrics.error_rate')),
            'fee' => Arr::get($payload, 'fee', Arr::get($payload, 'metrics.fee')),
            'availability' => Arr::get($payload, 'availability', Arr::get($payload, 'metrics.availability')),
        ];
    }

    /**
     * Build a human readable message for logging and alerting.
     */
    protected function buildMessage(string $name, string $status, ?string $errorMessage, array $meta, ?float $latencyMs): string
    {
        $parts = [sprintf('%s is %s', ucfirst($name), strtoupper($status))];

        if ($latencyMs !== null) {
            $parts[] = sprintf('latency: %sms', $latencyMs);
        }

        if ($errorMessage) {
            $parts[] = $errorMessage;
        }

        if (isset($meta['error_rate'], $meta['error_rate_threshold'])) {
            $parts[] = sprintf('error rate %.2f%% > threshold %.2f%%', $meta['error_rate'], $meta['error_rate_threshold']);
        }

        if (isset($meta['fee'], $meta['fee_threshold'])) {
            $parts[] = sprintf('fee %.2f > threshold %.2f', $meta['fee'], $meta['fee_threshold']);
        }

        return implode(' | ', $parts);
    }

    /**
     * Dispatch notifications when the status changes or recovers.
     */
    protected function maybeAlert(array $result, ?string $previousStatus = null): void
    {
        $status = $result['status'];

        if ($previousStatus === $status) {
            return;
        }

        if ($status === 'up' && $previousStatus && $previousStatus !== 'up') {
            $this->sendNotification($result, 'recovered');
            return;
        }

        if (in_array($status, ['degraded', 'down'], true)) {
            $severity = $status === 'down' ? 'critical' : 'warning';
            $this->sendNotification($result, $severity);
        }
    }

    /**
     * Send notifications using the configured channels.
     */
    protected function sendNotification(array $result, string $severity): void
    {
        $notification = new ServiceAlertNotification($result, $severity);

        foreach ($this->emails() as $email) {
            Notification::route('mail', $email)->notify($notification);
        }

        foreach ($this->onCallRecipients() as $email) {
            Notification::route('mail', $email)->notify($notification);
        }

        if ($webhook = $this->slackWebhook()) {
            Notification::route('slack', $webhook)->notify($notification);
        }
    }

    /**
     * Get email recipients for alerts.
     */
    protected function emails(): array
    {
        return $this->parseRecipients(config('monitoring.alerts.emails', []));
    }

    /**
     * Get on-call recipients for alerts.
     */
    protected function onCallRecipients(): array
    {
        return $this->parseRecipients(config('monitoring.alerts.on_call', []));
    }

    /**
     * Retrieve the configured Slack webhook URL.
     */
    protected function slackWebhook(): ?string
    {
        $webhook = config('monitoring.alerts.slack_webhook');

        return $webhook ? trim($webhook) : null;
    }

    /**
     * Normalise recipient configuration values.
     */
    protected function parseRecipients($value): array
    {
        if (is_string($value)) {
            $value = array_map('trim', explode(',', $value));
        }

        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map('trim', $value)));
    }
}
