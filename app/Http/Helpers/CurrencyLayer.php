<?php

namespace App\Http\Helpers;

use App\Constants\GlobalConst;
use App\Models\LiveExchangeRateApiSetting;
use App\Services\Cache\GlobalCacheService;
use Exception;

class CurrencyLayer{

    /**
     * Active API
     */
    public $api;

    /**
     * Store access token
     */
    protected $access_token;

    /**
     * store configuration
     */
    protected array $config;


    public function __construct()
    {
        $this->api = GlobalCacheService::rememberProvider(GlobalConst::CURRENCY_LAYER, function () {
            return LiveExchangeRateApiSetting::where('slug', GlobalConst::CURRENCY_LAYER)->active()->first();
        });
        $this->setConfig();
    }
    /**
     * Set configuration
     */
    public function setConfig()
    {
        $api = $this->api;

        if(!$api) throw new Exception("Exchange Rate Provider Not Found!");

        $config['access_key']       = $api->value?->access_key;
        $config['request_url']      = $api->value?->base_url;
        $config['multiply_by']      = $api->multiply_by;

        $this->config = $config;

        return $this;
    }
    /**
     * Authenticate API access token retrieve
     */
    public function getLiveExchangeRates(bool $forceRefresh = false)
    {

        if(!$this->config) $this->setConfig();

        $access_key = $this->config['access_key'];
        $admin_addition_rate = $this->config['multiply_by'] ?? 1;
        $currencies = filterValidCurrencies(systemCurrenciesCode());

        $source = get_default_currency_code();
        $url =  $this->config['request_url']."/live?access_key=$access_key&currencies=$currencies&format=1&source=$source";

        return GlobalCacheService::rememberExchangeRates(function () use ($url, $admin_addition_rate) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);
            $results = json_decode($response,true);

            curl_close($ch);

            if(isset($results) && isset($results['success']) && $results['success'] == true){

                $quotes = $results['quotes'];
                $formattedQuotes = [];
                foreach ($quotes as $currency => $value) {
                    $formattedValue = get_amount(($value * $admin_addition_rate),null,12);
                    if (strpos($currency, $results['source']) === 0) {
                        $currency = substr($currency, 3);
                    }
                    $formattedQuotes[$currency] = $formattedValue;
                }
                return [
                    'status'    => true,
                    'message'   => "Successfully Get Exchange Rate",
                    'data'   => $formattedQuotes,
                ];

            }

            return [
                'status'    => false,
                'message'   => $results['error']['info']??'something went wrong in currency layer api',
                'data'   => [],
            ];
        }, $forceRefresh);
    }
    public function apiCurrencyList()
    {
        if(!$this->config) $this->setConfig();

        $access_key = $this->config['access_key'];
        $url =  $this->config['request_url']."/list?access_key=$access_key";

        //start curl request.
        $ch = curl_init();

        // Set the options for cURL
        curl_setopt($ch, CURLOPT_URL, $url); // Set the URL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the result as a string
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL certificate verification

        // Execute cURL request
        $response = curl_exec($ch);
        $results = json_decode($response,true);



        //handle data
        if(isset($results) && isset($results['success']) && $results['success'] == true){

            $data = [
                'status'    => true,
                'message'   => "Successfully Get All Currency List",
                'data'   => $results['currencies']??[],
            ];

        }else{
            $data = [
                'status'    => false,
                'message'   => 'something went wrong in currency layer api',
                'data'   => [],
            ];
        }
        curl_close($ch);


        return $data??[];
    }

}
