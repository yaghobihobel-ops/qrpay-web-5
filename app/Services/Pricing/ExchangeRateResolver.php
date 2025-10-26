<?php

namespace App\Services\Pricing;

use App\Models\Admin\Currency;
use App\Models\LiveExchangeRateApiSetting;
use App\Services\Pricing\Exceptions\ExchangeRateException;
use Illuminate\Cache\CacheManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExchangeRateResolver
{
    public function __construct(
        protected CacheManager $cache
    ) {
    }

    public function getRate(string $baseCurrency, string $quoteCurrency): float
    {
        $baseCurrency = strtoupper($baseCurrency);
        $quoteCurrency = strtoupper($quoteCurrency);

        if ($baseCurrency === $quoteCurrency) {
            return 1.0;
        }

        $cacheKey = sprintf('pricing:rates:%s:%s', $baseCurrency, $quoteCurrency);

        return $this->cache->remember($cacheKey, now()->addMinutes(10), function () use ($baseCurrency, $quoteCurrency) {
            $rate = $this->tryActiveProviders($baseCurrency, $quoteCurrency);

            if ($rate !== null) {
                return $rate;
            }

            $fallbackRate = $this->fallbackFromStaticRates($baseCurrency, $quoteCurrency);

            if ($fallbackRate !== null) {
                return $fallbackRate;
            }

            throw new ExchangeRateException(sprintf('Unable to resolve exchange rate for %s/%s', $baseCurrency, $quoteCurrency));
        });
    }

    protected function tryActiveProviders(string $baseCurrency, string $quoteCurrency): ?float
    {
        $providers = LiveExchangeRateApiSetting::query()->active()->get();

        foreach ($providers as $provider) {
            $baseUrl = Arr::get((array) $provider->value, 'base_url');
            $accessKey = Arr::get((array) $provider->value, 'access_key');

            if (! $baseUrl) {
                continue;
            }

            try {
                $response = Http::timeout(5)->get(rtrim($baseUrl, '/') . '/convert', [
                    'access_key' => $accessKey,
                    'from' => $baseCurrency,
                    'to' => $quoteCurrency,
                    'amount' => 1,
                ]);

                if ($response->successful()) {
                    $rate = $response->json('result');

                    if (is_numeric($rate) && $rate > 0) {
                        $multiplier = (float) ($provider->multiply_by ?? 1);

                        return (float) $rate * $multiplier;
                    }
                }
            } catch (\Throwable $exception) {
                Log::warning('Live exchange rate provider request failed', [
                    'provider' => $provider->provider,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return null;
    }

    protected function fallbackFromStaticRates(string $baseCurrency, string $quoteCurrency): ?float
    {
        $base = Currency::where('currency_code', $baseCurrency)->first();
        $quote = Currency::where('currency_code', $quoteCurrency)->first();

        if ($base && $quote) {
            $baseRate = (float) $base->rate;
            $quoteRate = (float) $quote->rate;

            if ($baseRate > 0 && $quoteRate > 0) {
                return $quoteRate / $baseRate;
            }
        }

        return null;
    }
}
