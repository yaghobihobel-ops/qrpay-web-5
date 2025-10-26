<?php

namespace App\Services\Pricing;

use App\Services\Pricing\Exceptions\CouldNotResolveExchangeRateException;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CurrencyRateService
{
    public function __construct(
        protected ConfigRepository $config,
        protected CacheRepository $cache
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
        $ttl = (int) $this->config->get('pricing.cache_ttl', 300);

        return $this->cache->remember($cacheKey, $ttl, function () use ($baseCurrency, $quoteCurrency) {
            $scenarioRates = (array) $this->config->get('pricing.scenario_rates', []);
            if (isset($scenarioRates[$baseCurrency][$quoteCurrency])) {
                return (float) $scenarioRates[$baseCurrency][$quoteCurrency];
            }

            $provider = $this->config->get('pricing.rate_provider');

            if (! $provider || ! isset($provider['base_url'])) {
                throw new CouldNotResolveExchangeRateException('No exchange rate provider configured.');
            }

            $endpoint = rtrim($provider['base_url'], '/');
            $timeout = (int) ($provider['timeout'] ?? 5);

            $params = [
                'base' => $baseCurrency,
                'symbols' => $quoteCurrency,
            ];

            if (! empty($provider['api_key']) && ! empty($provider['api_key_parameter'])) {
                $params[$provider['api_key_parameter']] = $provider['api_key'];
            }

            if (! empty($provider['additional_parameters']) && \is_array($provider['additional_parameters'])) {
                $params = array_merge($params, $provider['additional_parameters']);
            }

            $response = Http::timeout($timeout)
                ->acceptJson()
                ->get($endpoint, $params);

            if (! $response->successful()) {
                throw new CouldNotResolveExchangeRateException(sprintf(
                    'Exchange rate provider responded with status %s',
                    $response->status()
                ));
            }

            $body = $response->json();
            $rate = $this->extractRateFromResponse($body, $quoteCurrency);

            if ($rate === null) {
                throw new CouldNotResolveExchangeRateException('Exchange rate provider returned an unexpected payload.');
            }

            return (float) $rate;
        });
    }

    public function convert(float $amount, string $baseCurrency, string $quoteCurrency): float
    {
        return $amount * $this->getRate($baseCurrency, $quoteCurrency);
    }

    protected function extractRateFromResponse(mixed $body, string $quoteCurrency): ?float
    {
        if (! \is_array($body)) {
            return null;
        }

        $quoteCurrency = strtoupper($quoteCurrency);

        if (isset($body['rates'][$quoteCurrency])) {
            return (float) $body['rates'][$quoteCurrency];
        }

        if (isset($body['data'][$quoteCurrency])) {
            return (float) $body['data'][$quoteCurrency];
        }

        if (isset($body['result']) && \is_array($body['result']) && isset($body['result'][$quoteCurrency])) {
            return (float) $body['result'][$quoteCurrency];
        }

        if (isset($body['price'])) {
            return (float) $body['price'];
        }

        if (isset($body['rate'])) {
            return (float) $body['rate'];
        }

        if (isset($body['quotes']) && \is_array($body['quotes'])) {
            $key = sprintf('USD%s', $quoteCurrency);
            if (isset($body['quotes'][$key])) {
                return (float) $body['quotes'][$key];
            }
        }

        if (isset($body['conversion_rates']) && \is_array($body['conversion_rates']) && isset($body['conversion_rates'][$quoteCurrency])) {
            return (float) $body['conversion_rates'][$quoteCurrency];
        }

        $flatKey = Str::lower($quoteCurrency);
        if (isset($body[$flatKey])) {
            return (float) $body[$flatKey];
        }

        return null;
    }
}
