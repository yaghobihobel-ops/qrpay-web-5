<?php

namespace App\Services\Exchange;

use App\Services\Exchange\Exceptions\ExchangeRateException;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class ExchangeRateManager
{
    /**
     * @var array<string, ExchangeRateProviderInterface>
     */
    protected array $providers = [];

    /**
     * @var array<string, mixed>
     */
    protected array $config;

    protected CacheRepository $cache;

    public function __construct(?CacheRepository $cache = null)
    {
        $this->config = config('exchange', []);
        $store = $this->config['cache_store'] ?? null;
        $store = $store ?: config('cache.default');
        $this->cache = $cache ?? Cache::store($store);
    }

    public function fetchRates(array $symbols): array
    {
        $symbols = array_values(array_unique(array_map('strtoupper', $symbols)));
        $cacheKey = $this->cacheKey($symbols);

        if ($this->config['cache'] ?? false) {
            $cached = $this->cache->get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        }

        foreach ($this->getFallbackOrder() as $alias) {
            $provider = $this->resolveProvider($alias);

            try {
                $rates = $provider->fetchRates($symbols);
            } catch (\Throwable $exception) {
                report($exception);
                continue;
            }

            if (!empty($rates)) {
                if ($this->config['cache'] ?? false) {
                    $ttl = $this->config['cache_ttl'] ?? 3600;
                    $this->cache->put($cacheKey, $rates, $ttl);
                }

                return $rates;
            }
        }

        throw ExchangeRateException::providersFailed();
    }

    public function convert(float $amount, string $from, string $to): float
    {
        foreach ($this->getFallbackOrder() as $alias) {
            $provider = $this->resolveProvider($alias);

            try {
                return $provider->convert($amount, strtoupper($from), strtoupper($to));
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        throw ExchangeRateException::providersFailed();
    }

    protected function resolveProvider(string $alias): ExchangeRateProviderInterface
    {
        if (isset($this->providers[$alias])) {
            return $this->providers[$alias];
        }

        $providerConfig = Arr::get($this->config, "providers.{$alias}");

        if (!$providerConfig || !isset($providerConfig['class'])) {
            throw new \InvalidArgumentException("Exchange provider [{$alias}] is not configured.");
        }

        $class = $providerConfig['class'];
        $this->providers[$alias] = app($class, ['config' => $providerConfig]);

        return $this->providers[$alias];
    }

    /**
     * @return array<int, string>
     */
    protected function getFallbackOrder(): array
    {
        $order = Arr::get($this->config, 'fallback_order', []);

        if (empty($order)) {
            $order = array_keys($this->config['providers'] ?? []);
        }

        return $order;
    }

    /**
     * @param  array<int, string>  $symbols
     */
    protected function cacheKey(array $symbols): string
    {
        sort($symbols);

        return 'exchange:rates:' . implode('-', $symbols);
    }
}
