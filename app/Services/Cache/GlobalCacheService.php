<?php

namespace App\Services\Cache;

use App\Models\UserWallet;
use Closure;
use Illuminate\Support\Carbon;
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

        $currentSignature = self::userAccountSignature($userId);

        if (!$force && Cache::has($cacheKey)) {
            [$cachedValue, $cachedSignature] = self::normalizeUserAccountCache(Cache::get($cacheKey));

            if ($cachedSignature !== null && $cachedSignature === $currentSignature) {
                return $cachedValue;
            }
        }

        $value = $resolver();

        if ($value === null) {
            Cache::forget($cacheKey);

            return $value;
        }

        $freshSignature = self::userAccountSignature($userId);

        Cache::put(
            $cacheKey,
            [
                'user' => $value,
                'signature' => $freshSignature,
            ],
            self::ttl($ttl)
        );

        return $value;
    }

    public static function forgetUserAccount(int $userId): void
    {
        Cache::forget("user:account-summary:{$userId}");
    }

    protected static function normalizeUserAccountCache(mixed $cached): array
    {
        if (is_array($cached) && array_key_exists('user', $cached)) {
            return [$cached['user'], $cached['signature'] ?? null];
        }

        return [$cached, null];
    }

    protected static function userAccountSignature(int $userId): string
    {
        $metrics = UserWallet::query()
            ->where('user_id', $userId)
            ->selectRaw('COALESCE(SUM(balance), 0) as total_balance, COUNT(*) as wallet_count, MAX(updated_at) as latest_update')
            ->first();

        $latestUpdate = $metrics?->latest_update;

        if ($latestUpdate instanceof Carbon) {
            $latestTimestamp = $latestUpdate->getTimestamp();
        } elseif ($latestUpdate) {
            $latestTimestamp = Carbon::parse($latestUpdate)->getTimestamp();
        } else {
            $latestTimestamp = 0;
        }

        $total = $metrics?->total_balance ?? 0;
        $totalBalance = number_format((float) $total, 8, '.', '');
        $totalBalance = rtrim(rtrim($totalBalance, '0'), '.');
        if ($totalBalance === '') {
            $totalBalance = '0';
        }

        $walletCount = (int) ($metrics?->wallet_count ?? 0);

        return implode(':', [
            $totalBalance,
            (string) $walletCount,
            (string) $latestTimestamp,
        ]);
    }
}
