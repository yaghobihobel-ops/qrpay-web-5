<?php

namespace App\Services\Edge;

use Closure;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EdgeCacheRepository
{
    public const SCOPE_BANKS = 'banks';
    public const SCOPE_RATES = 'rates';
    public const SCOPE_SETTINGS = 'settings';

    public function __construct(
        private readonly CacheManager $cacheManager,
        private readonly ConfigRepository $config
    ) {
    }

    public function rememberBanks(?string $countryCode, Closure $callback): mixed
    {
        $identifier = $countryCode ? strtoupper($countryCode) : 'global';

        return $this->remember(self::SCOPE_BANKS, $identifier, $callback);
    }

    public function rememberRates(string $source, Closure $callback): mixed
    {
        return $this->remember(self::SCOPE_RATES, Str::slug($source, '_') ?: 'default', $callback);
    }

    public function rememberSettings(string $segment, Closure $callback): mixed
    {
        return $this->remember(self::SCOPE_SETTINGS, Str::slug($segment, '_') ?: 'default', $callback);
    }

    public function remember(string $scope, ?string $identifier, Closure $callback): mixed
    {
        $key = $this->key($scope, $identifier);
        $ttl = $this->ttlForScope($scope);
        $store = $this->store();

        if ($ttl <= 0) {
            return $store->rememberForever($key, $callback);
        }

        return $store->remember($key, now()->addSeconds($ttl), $callback);
    }

    public function forget(string $scope, ?string $identifier = null): void
    {
        $this->store()->forget($this->key($scope, $identifier));
    }

    public function withEdgeHeaders(Response $response, string $scope, ?string $identifier = null, ?int $ttlOverride = null): Response
    {
        $ttl = $ttlOverride ?? $this->ttlForScope($scope);
        $key = $this->key($scope, $identifier);

        if ($ttl > 0) {
            $cacheControl = sprintf('public, max-age=%d, stale-while-revalidate=%d', $ttl, (int) ceil($ttl / 2));
            $response->headers->set('Cache-Control', $cacheControl, false);
            $response->headers->set('Edge-Cache-TTL', (string) $ttl, false);
        }

        $response->headers->set('Edge-Cache-Key', $key, false);
        $response->headers->set('Edge-Cache-Scope', $scope, false);

        if ($identifier !== null) {
            $response->headers->set('Edge-Cache-Identifier', $identifier, false);
        }

        return $response;
    }

    public function ttlForScope(string $scope): int
    {
        $ttlConfig = $this->config->get("edge.cache.ttl.{$scope}");

        return $ttlConfig !== null ? (int) $ttlConfig : 0;
    }

    protected function key(string $scope, ?string $identifier = null): string
    {
        $prefix = $this->config->get('edge.cache.prefix', 'edge:qrpay');
        $identifier = $identifier !== null ? Str::of($identifier)->replaceMatches('/[^A-Za-z0-9:_-]/', '_')->toString() : 'default';

        return sprintf('%s:%s:%s', $prefix, $scope, $identifier);
    }

    protected function store(): CacheRepository
    {
        $store = (string) $this->config->get('edge.cache.store', config('cache.default'));

        return $this->cacheManager->store($store);
    }
}
