<?php

namespace App\Services\Exchange;

use App\Constants\GlobalConst;
use App\Models\LiveExchangeRateApiSetting;
use Exception;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CurrencyLayerExchangeRateProvider implements ExchangeRateProviderInterface
{
    protected ?LiveExchangeRateApiSetting $api;
    protected array $config = [];
    protected string $rateCacheKey;
    protected string $supportedCurrenciesCacheKey;
    protected int $cacheTtl;
    protected int $timeout;
    protected int $retryTimes;
    protected int $retrySleep;

    public function __construct()
    {
        $this->api = LiveExchangeRateApiSetting::active()->first();
        $this->setConfig();
        $this->rateCacheKey = config('exchange.cache.fallback_rates_key', 'exchange:rates:currency-layer:last-success');
        $this->supportedCurrenciesCacheKey = config('exchange.cache.supported_currencies_key', 'exchange:rates:currency-layer:supported-currencies');
        $this->cacheTtl = (int) config('exchange.cache.ttl', 3600);
        $this->timeout = (int) config('exchange.http.timeout', 10);
        $this->retryTimes = (int) config('exchange.http.retry_times', 3);
        $this->retrySleep = (int) config('exchange.http.retry_sleep', 500);
    }

    public function getIdentifier(): string
    {
        return GlobalConst::CURRENCY_LAYER;
    }

    public function getLiveExchangeRates(): array
    {
        if (!$this->config) {
            $this->setConfig();
        }

        $query = [
            'access_key' => $this->config['access_key'],
            'currencies' => filterValidCurrencies(systemCurrenciesCode()),
            'format' => 1,
            'source' => get_default_currency_code(),
        ];

        try {
            $response = Http::withOptions([
                    'timeout' => $this->timeout,
                ])
                ->retry($this->retryTimes, $this->retrySleep)
                ->get(rtrim($this->config['request_url'], '/').'/live', $query);

            $response->throw();

            $payload = $response->json();

            if (!Arr::get($payload, 'success')) {
                throw new Exception(Arr::get($payload, 'error.info', 'Currency Layer request failed.'));
            }

            $formatted = $this->formatQuotes($payload['quotes'] ?? [], $payload['source'] ?? $query['source']);

            Cache::put($this->rateCacheKey, $formatted, $this->cacheTtl);

            return [
                'status' => true,
                'message' => 'Successfully retrieved exchange rates.',
                'data' => $formatted,
            ];
        } catch (RequestException|Exception $exception) {
            $cached = Cache::get($this->rateCacheKey);
            if ($cached) {
                return [
                    'status' => true,
                    'message' => $exception->getMessage() ?? 'Returning cached exchange rates.',
                    'data' => $cached,
                    'from_cache' => true,
                ];
            }

            return [
                'status' => false,
                'message' => $exception->getMessage() ?? 'Failed to retrieve exchange rates.',
                'data' => [],
            ];
        }
    }

    public function getSupportedCurrencies(): array
    {
        if (!$this->config) {
            $this->setConfig();
        }

        $query = [
            'access_key' => $this->config['access_key'],
        ];

        try {
            $response = Http::withOptions([
                    'timeout' => $this->timeout,
                ])
                ->retry($this->retryTimes, $this->retrySleep)
                ->get(rtrim($this->config['request_url'], '/').'/list', $query);

            $response->throw();

            $payload = $response->json();

            if (!Arr::get($payload, 'success')) {
                throw new Exception(Arr::get($payload, 'error.info', 'Currency Layer request failed.'));
            }

            $currencies = $payload['currencies'] ?? [];

            Cache::put($this->supportedCurrenciesCacheKey, $currencies, $this->cacheTtl);

            return [
                'status' => true,
                'message' => 'Successfully retrieved supported currencies.',
                'data' => $currencies,
            ];
        } catch (RequestException|Exception $exception) {
            $cached = Cache::get($this->supportedCurrenciesCacheKey);
            if ($cached) {
                return [
                    'status' => true,
                    'message' => $exception->getMessage() ?? 'Returning cached currency list.',
                    'data' => $cached,
                    'from_cache' => true,
                ];
            }

            return [
                'status' => false,
                'message' => $exception->getMessage() ?? 'Failed to retrieve supported currencies.',
                'data' => [],
            ];
        }
    }

    protected function setConfig(): void
    {
        if (!$this->api) {
            throw new Exception('Exchange Rate Provider Not Found!');
        }

        $this->config = [
            'access_key' => $this->api->value?->access_key,
            'request_url' => $this->api->value?->base_url,
            'multiply_by' => $this->api->multiply_by ?? 1,
        ];
    }

    protected function formatQuotes(array $quotes, string $source): array
    {
        $formattedQuotes = [];
        $adminAdditionRate = $this->config['multiply_by'] ?? 1;

        foreach ($quotes as $currency => $value) {
            if (str_starts_with($currency, $source)) {
                $currency = substr($currency, strlen($source));
            }

            $formattedQuotes[$currency] = get_amount(($value * $adminAdditionRate), null, 12);
        }

        return $formattedQuotes;
    }
}
