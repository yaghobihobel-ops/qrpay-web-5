<?php

namespace App\Services\Domain;

use App\Models\ProviderOverride;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ProviderOverrideRepository
{
    protected const CACHE_TTL = 60; // seconds

    public function getOverrides(string $domain, ?string $provider = null): Collection
    {
        $cacheKey = $this->cacheKey($domain, $provider);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($domain, $provider) {
            return ProviderOverride::query()
                ->active()
                ->forDomain($domain)
                ->forProvider($provider)
                ->get();
        });
    }

    public function resolveBoolean(string $domain, ?string $provider, string $key, bool $default): bool
    {
        $value = $this->resolveValue($domain, $provider, $key);

        return is_null($value) ? $default : filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $value;
    }

    public function resolveInteger(string $domain, ?string $provider, string $key, int $default): int
    {
        $value = $this->resolveValue($domain, $provider, $key);

        return is_null($value) ? $default : (int) $value;
    }

    public function resolveValue(string $domain, ?string $provider, string $key, $default = null)
    {
        $overrides = $this->getOverrides($domain, $provider);

        /** @var ProviderOverride|null $match */
        $match = $overrides->first(function (ProviderOverride $override) use ($key) {
            return $override->key === $key;
        });

        if (!$match) {
            return $default;
        }

        $value = $match->value;

        if (is_array($value)) {
            $leafKey = Arr::last(explode('.', $key));
            return data_get($value, $leafKey, $default);
        }

        return $value ?? $default;
    }

    public function forget(string $domain, ?string $provider = null): void
    {
        Cache::forget($this->cacheKey($domain, $provider));
    }

    protected function cacheKey(string $domain, ?string $provider = null): string
    {
        return sprintf('provider_overrides:%s:%s', $domain, $provider ?? 'default');
    }
}
