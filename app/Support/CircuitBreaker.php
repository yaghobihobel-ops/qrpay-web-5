<?php

namespace App\Support;

use App\Exceptions\CircuitBreakerOpenException;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use InvalidArgumentException;

class CircuitBreaker
{
    protected CacheRepository $cache;

    public function __construct(CacheRepository $cache)
    {
        $this->cache = $cache;
    }

    public function allows(string $circuit): bool
    {
        $openUntil = (int) $this->cache->get($this->openKey($circuit), 0);

        return $openUntil <= now()->getTimestamp();
    }

    public function recordSuccess(string $circuit): void
    {
        $this->cache->forget($this->failureKey($circuit));
        $this->cache->forget($this->openKey($circuit));
    }

    public function recordFailure(string $circuit): void
    {
        $config = $this->getCircuitConfig($circuit);
        $failuresKey = $this->failureKey($circuit);
        $failures = (int) $this->cache->get($failuresKey, 0) + 1;

        $this->cache->put($failuresKey, $failures, $config['decay_seconds']);

        if ($failures < $config['failure_threshold']) {
            return;
        }

        $this->trip($circuit, $config['retry_timeout']);
    }

    public function toOpenCircuitException(string $service, string $circuit): CircuitBreakerOpenException
    {
        $retryAfter = $this->retryAfter($circuit);

        return new CircuitBreakerOpenException($service, $circuit, $retryAfter);
    }

    public function trip(string $circuit, int $retryTimeout): void
    {
        $openUntil = now()->addSeconds($retryTimeout)->getTimestamp();
        $this->cache->put($this->openKey($circuit), $openUntil, $retryTimeout);
        $this->cache->forget($this->failureKey($circuit));
    }

    public function retryAfter(string $circuit): int
    {
        $openUntil = (int) $this->cache->get($this->openKey($circuit), 0);
        $now = now()->getTimestamp();

        return max(0, $openUntil - $now);
    }

    protected function getCircuitConfig(string $circuit): array
    {
        $config = config('api.circuit_breaker', []);
        $defaults = $config['default'] ?? [];
        $specific = $config[$circuit] ?? [];
        $merged = array_merge($defaults, $specific);

        $threshold = (int) ($merged['failure_threshold'] ?? 0);
        $retryTimeout = (int) ($merged['retry_timeout'] ?? 0);
        $decaySeconds = (int) ($merged['decay_seconds'] ?? 0);

        if ($threshold <= 0 || $retryTimeout <= 0 || $decaySeconds <= 0) {
            throw new InvalidArgumentException(sprintf('Invalid circuit breaker configuration for [%s].', $circuit));
        }

        return [
            'failure_threshold' => $threshold,
            'retry_timeout' => $retryTimeout,
            'decay_seconds' => $decaySeconds,
        ];
    }

    protected function failureKey(string $circuit): string
    {
        return sprintf('circuit:%s:failures', $circuit);
    }

    protected function openKey(string $circuit): string
    {
        return sprintf('circuit:%s:open-until', $circuit);
    }
}
