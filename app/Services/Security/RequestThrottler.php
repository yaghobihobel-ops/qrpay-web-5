<?php

namespace App\Services\Security;

use App\Notifications\ThrottleLimitExceeded;
use App\Support\CircuitBreaker;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

class RequestThrottler
{
    public const SERVICE_PAYMENT = 'payment';
    public const SERVICE_WITHDRAWAL = 'withdrawal';
    public const SERVICE_EXCHANGE = 'exchange';

    private RateLimiter $rateLimiter;
    private CircuitBreaker $circuitBreaker;

    public function __construct(RateLimiter $rateLimiter, CircuitBreaker $circuitBreaker)
    {
        $this->rateLimiter = $rateLimiter;
        $this->circuitBreaker = $circuitBreaker;
    }

    public function ensureWithinLimit(string $service, ?string $userIdentifier = null, ?string $ipAddress = null): void
    {
        $serviceConfig = $this->getServiceConfig($service);

        if ($userIdentifier !== null) {
            $this->checkDimension($service, 'per_user', $userIdentifier, $serviceConfig['per_user'] ?? []);
        }

        if ($ipAddress !== null) {
            $this->checkDimension($service, 'per_ip', $ipAddress, $serviceConfig['per_ip'] ?? []);
        }
    }

    public function run(string $service, callable $callback, ?string $userIdentifier = null, ?string $ipAddress = null)
    {
        $circuit = $this->getCircuitForService($service);

        if ($circuit !== null && ! $this->circuitBreaker->allows($circuit)) {
            throw $this->circuitBreaker->toOpenCircuitException($service, $circuit);
        }

        $this->ensureWithinLimit($service, $userIdentifier, $ipAddress);

        try {
            $result = $callback();

            if ($circuit !== null) {
                $this->circuitBreaker->recordSuccess($circuit);
            }

            return $result;
        } catch (Throwable $exception) {
            if ($circuit !== null) {
                $this->circuitBreaker->recordFailure($circuit);
            }

            throw $exception;
        }
    }

    protected function checkDimension(string $service, string $dimension, string $identifier, array $limitConfig): void
    {
        $maxAttempts = (int) ($limitConfig['max_attempts'] ?? 0);

        if ($maxAttempts <= 0) {
            return;
        }

        $decayMinutes = max(1, (int) ($limitConfig['decay_minutes'] ?? 1));
        $cacheTtl = $decayMinutes * 60;
        $key = $this->formatKey($service, $dimension, $identifier);

        if (! $this->rateLimiter->tooManyAttempts($key, $maxAttempts)) {
            $this->rateLimiter->hit($key, $cacheTtl);

            return;
        }

        $retryAfter = $this->rateLimiter->availableIn($key);
        $this->handleLimitExceeded($service, $dimension, $identifier, $maxAttempts, $retryAfter, $cacheTtl);
    }

    protected function formatKey(string $service, string $dimension, string $identifier): string
    {
        return Str::lower(sprintf('throttle:%s:%s:%s', $service, $dimension, $identifier));
    }

    protected function getServiceConfig(string $service): array
    {
        $services = config('api.rate_limits.services', []);
        $default = $services['default'] ?? [];

        return array_replace_recursive($default, $services[$service] ?? []);
    }

    protected function getCircuitForService(string $service): ?string
    {
        $mapping = config('api.service_circuits', []);

        return $mapping[$service] ?? null;
    }

    protected function handleLimitExceeded(
        string $service,
        string $dimension,
        string $identifier,
        int $maxAttempts,
        int $retryAfter,
        int $cacheTtl
    ): void {
        $alertKey = sprintf('throttle-alert:%s:%s:%s', $service, $dimension, $identifier);

        if (Cache::add($alertKey, true, $cacheTtl)) {
            $this->recordAlert($service, $dimension, $identifier, $maxAttempts, $retryAfter);
        }

        $message = __('Too many requests for :service (:dimension)', [
            'service' => $service,
            'dimension' => $dimension,
        ]);

        throw new TooManyRequestsHttpException($retryAfter, $message);
    }

    protected function recordAlert(string $service, string $dimension, string $identifier, int $maxAttempts, int $retryAfter): void
    {
        $alerts = config('api.alerts', []);
        $channel = $alerts['log_channel'] ?? config('logging.default');

        Log::channel($channel)->warning('Throttle limit exceeded.', [
            'service' => $service,
            'dimension' => $dimension,
            'identifier' => $identifier,
            'max_attempts' => $maxAttempts,
            'retry_after' => $retryAfter,
        ]);

        $channels = [];
        $slack = $alerts['slack_webhook'] ?? null;
        $emails = Arr::wrap($alerts['email']['recipients'] ?? []);

        if ($slack) {
            $channels[] = 'slack';
        }

        if (! empty($emails)) {
            $channels[] = 'mail';
        }

        if (empty($channels)) {
            return;
        }

        $notification = new ThrottleLimitExceeded(
            $service,
            $dimension,
            $identifier,
            $maxAttempts,
            $retryAfter,
            $channels
        );

        $routes = null;

        if ($slack) {
            $routes = Notification::route('slack', $slack);
        }

        foreach ($emails as $email) {
            if ($routes) {
                $routes->route('mail', $email);
            } else {
                $routes = Notification::route('mail', $email);
            }
        }

        if ($routes) {
            $routes->notify($notification);
        }
    }
}
