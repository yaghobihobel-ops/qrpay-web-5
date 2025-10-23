<?php

namespace App\Services\Cache;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class GlobalCacheService
{
    protected static function ttl(int $seconds)
    {
        return now()->addSeconds($seconds);
    }

    public static function rememberExchangeRates(Closure $resolver, bool $force = false)
    {
        $key = 'global:exchange_rates';
        $ttl = (int) config('performance.cache.exchange_rates_ttl', 600);

        if ($force) {
            Cache::forget($key);
        }

        if (!$force && Cache::has($key)) {
            return Cache::get($key);
        }

        $value = $resolver();

        if (is_array($value) && ($value['status'] ?? false) === true) {
            Cache::put($key, $value, self::ttl($ttl));
        }

        return $value;
    }

    public static function forgetExchangeRates(): void
    {
        Cache::forget('global:exchange_rates');
    }

    public static function rememberProvider(string $slug, Closure $resolver, bool $force = false)
    {
        $cacheKey = 'global:provider:' . Str::slug($slug);
        $ttl = (int) config('performance.cache.provider_settings_ttl', 1800);

        if ($force) {
            Cache::forget($cacheKey);
        }

        if (!$force && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $value = $resolver();
        Cache::put($cacheKey, $value, self::ttl($ttl));

        return $value;
    }

    public static function forgetProvider(string $slug): void
    {
        Cache::forget('global:provider:' . Str::slug($slug));
    }

    public static function rememberUserAccount(int $userId, Closure $resolver, bool $force = false)
    {
        $cacheKey = "user:account-summary:{$userId}";
        $ttl = (int) config('performance.cache.account_summary_ttl', 300);

        if ($force) {
            Cache::forget($cacheKey);
        }

        if (!$force && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $value = $resolver();
        Cache::put($cacheKey, $value, self::ttl($ttl));

        return $value;
    }

    public static function forgetUserAccount(int $userId): void
    {
        Cache::forget("user:account-summary:{$userId}");
    }

}
