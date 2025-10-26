<?php

namespace App\Services\Monitoring;

use App\Services\Domain\ProviderOverrideRepository;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Throwable;

class DomainInstrumentation
{
    public function __construct(protected ProviderOverrideRepository $overrideRepository)
    {
    }

    public function startOperation(string $domain, string $operation, array $config = [], array $attributes = []): DomainOperationContext
    {
        $provider = $attributes['provider'] ?? 'default';
        $config = $this->mergeOverrides($domain, $provider, $config);

        if (!data_get($config, 'feature_flags.enabled', true)) {
            throw new \RuntimeException(sprintf('%s service is currently disabled', Str::title($domain)));
        }

        $context = new DomainOperationContext($domain, $operation, $provider, $attributes, $config);

        $this->recordStart($context);

        return $context;
    }

    public function enforceRateLimit(string $domain, string $provider, string $key, array $config): void
    {
        $config = $this->mergeOverrides($domain, $provider, $config);

        $maxAttempts = (int) data_get($config, 'rate_limit.max_attempts', 0);
        $decaySeconds = (int) data_get($config, 'rate_limit.decay_seconds', 60);

        if ($maxAttempts <= 0) {
            return;
        }

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = RateLimiter::availableIn($key);
            $payload = [
                'domain'       => $domain,
                'provider'     => $provider,
                'rate_key'     => $key,
                'max_attempts' => $maxAttempts,
                'retry_after'  => $retryAfter,
            ];

            Log::warning('domain_rate_limit_blocked', $payload);

            throw new ThrottleRequestsException(__('Too many attempts. Please slow down.'), null, ['Retry-After' => $retryAfter]);
        }

        RateLimiter::hit($key, $decaySeconds);
    }

    public function recordSuccess(DomainOperationContext $context, array $extra = []): void
    {
        $context->finish($extra);

        $payload = $context->toLogPayload();
        Log::info('domain_operation_succeeded', $payload);

        $this->incrementMetric('domain_operation_succeeded_total', $context, $extra);
    }

    public function recordFailure(DomainOperationContext $context, Throwable $exception, array $extra = []): void
    {
        $context->finish($extra);

        $payload = array_merge($context->toLogPayload(), [
            'error_class'   => get_class($exception),
            'error_message' => $exception->getMessage(),
        ]);

        Log::error('domain_operation_failed', $payload);

        $this->incrementMetric('domain_operation_failed_total', $context, ['error_class' => get_class($exception)]);

        $this->trackFailure($context, $exception);
    }

    protected function recordStart(DomainOperationContext $context): void
    {
        Log::info('domain_operation_started', $context->toLogPayload());

        $this->incrementMetric('domain_operation_started_total', $context);
    }

    protected function incrementMetric(string $metric, DomainOperationContext $context, array $labels = []): void
    {
        if (!data_get($context->config, 'metrics.enabled', true)) {
            return;
        }

        $labelSet = array_merge([
            'domain'    => $context->domain,
            'operation' => $context->operation,
            'provider'  => $context->provider,
        ], $labels);

        ksort($labelSet);

        $hash = sha1(json_encode($labelSet));
        $cacheKey = sprintf('metrics:%s:%s', $metric, $hash);
        $current = Cache::get($cacheKey, 0);
        Cache::forever($cacheKey, $current + 1);

        Cache::forever($cacheKey.':labels', $labelSet);
    }

    protected function trackFailure(DomainOperationContext $context, Throwable $exception): void
    {
        $security = $context->config['security'] ?? [];
        $threshold = (int) data_get($security, 'failure_alert_threshold', 0);
        $decay = (int) data_get($security, 'failure_decay_seconds', 900);

        if ($threshold <= 0) {
            return;
        }

        $key = sprintf('domain_failure:%s:%s:%s', $context->domain, $context->provider, $context->operation);
        $failures = RateLimiter::hit($key, $decay);

        if ($failures >= $threshold) {
            event(new \App\Events\ServiceExecutionFailed($context, $exception, $failures, $threshold));
        }
    }

    protected function mergeOverrides(string $domain, string $provider, array $config): array
    {
        $config['feature_flags']['enabled'] = $this->overrideRepository->resolveBoolean(
            $domain,
            $provider,
            'feature_flags.enabled',
            data_get($config, 'feature_flags.enabled', true)
        );

        $config['rate_limit']['max_attempts'] = $this->overrideRepository->resolveInteger(
            $domain,
            $provider,
            'rate_limit.max_attempts',
            (int) data_get($config, 'rate_limit.max_attempts', 0)
        );

        $config['rate_limit']['decay_seconds'] = $this->overrideRepository->resolveInteger(
            $domain,
            $provider,
            'rate_limit.decay_seconds',
            (int) data_get($config, 'rate_limit.decay_seconds', 60)
        );

        $config['security']['failure_alert_threshold'] = $this->overrideRepository->resolveInteger(
            $domain,
            $provider,
            'security.failure_alert_threshold',
            (int) data_get($config, 'security.failure_alert_threshold', 0)
        );

        $config['security']['failure_decay_seconds'] = $this->overrideRepository->resolveInteger(
            $domain,
            $provider,
            'security.failure_decay_seconds',
            (int) data_get($config, 'security.failure_decay_seconds', 900)
        );

        return $config;
    }
}
