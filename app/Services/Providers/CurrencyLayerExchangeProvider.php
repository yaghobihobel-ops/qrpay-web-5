<?php

namespace App\Services\Providers;

use App\Models\LiveExchangeRateApiSetting;
use App\Services\Contracts\ExchangeRateProvider;
use Exception;

class CurrencyLayerExchangeProvider implements ExchangeRateProvider
{
    public $api;

    protected array $config;

    public function __construct()
    {
        $this->api = LiveExchangeRateApiSetting::active()->first();
        $this->setConfig();
    }

    public function setConfig()
    {
        $api = $this->api;

        if (!$api) {
            throw new Exception('Exchange Rate Provider Not Found!');
        }

        $config['access_key'] = $api->value?->access_key;
        $config['request_url'] = $api->value?->base_url;
        $config['multiply_by'] = $api->multiply_by;

        $this->config = $config;

        return $this;
    }

    public function getLiveExchangeRates(): array
    {
        if (!$this->config) {
            $this->setConfig();
        }

        $access_key = $this->config['access_key'];
        $admin_addition_rate = $this->config['multiply_by'] ?? 1;
        $currencies = filterValidCurrencies(systemCurrenciesCode());

        $source = get_default_currency_code();
        $url = $this->config['request_url'] . "/live?access_key=$access_key&currencies=$currencies&format=1&source=$source";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $results = json_decode($response, true);

        if (isset($results) && isset($results['success']) && $results['success'] == true) {
            $quotes = $results['quotes'];
            $formattedQuotes = [];
            foreach ($quotes as $currency => $value) {
                $formattedValue = get_amount(($value * $admin_addition_rate), null, 12);
                if (strpos($currency, $results['source']) === 0) {
                    $currency = substr($currency, 3);
                }
                $formattedQuotes[$currency] = $formattedValue;
            }
            $data = [
                'status' => true,
                'message' => 'Successfully Get Exchange Rate',
                'data' => $formattedQuotes,
            ];
        } else {
            $data = [
                'status' => false,
                'message' => $results['error']['info'] ?? 'something went wrong in currency layer api',
                'data' => [],
            ];
        }

        curl_close($ch);

        return $data ?? [];
    }

    public function apiCurrencyList(): array
    {
        if (!$this->config) {
            $this->setConfig();
        }

        $access_key = $this->config['access_key'];
        $url = $this->config['request_url'] . "/list?access_key=$access_key";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $results = json_decode($response, true);

        if (isset($results) && isset($results['success']) && $results['success'] == true) {
            $data = [
                'status' => true,
                'message' => 'Successfully Get All Currency List',
                'data' => $results['currencies'] ?? [],
            ];
        } else {
            $data = [
                'status' => false,
                'message' => 'something went wrong in currency layer api',
                'data' => [],
            ];
        }
        curl_close($ch);

        return $data ?? [];
    }
}
