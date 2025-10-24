<?php

namespace App\Services\Providers;

use App\Constants\GlobalConst;
use App\Models\Admin\ReloadlyApi;
use App\Services\Contracts\AirtimeProvider;
use Exception;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class ReloadlyAirtimeProvider implements AirtimeProvider
{
    public $api;

    protected $access_token;

    protected array $config;

    public const AIRTIME_CACHE_KEY = 'airtime_api_{provider}_{env}';

    public const STATUS_SUCCESS = 'SUCCESSFUL';
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_PROCESSING = 'PROCESSING';
    public const STATUS_REFUNDED = 'REFUNDED';
    public const STATUS_FAILED = 'FAILED';

    public const PRICE_TYPES = [
        'FIXED' => 'FIXED',
        'RANGE' => 'RANGE',
    ];

    public function __construct()
    {
        $this->api = ReloadlyApi::reloadly()->mobileTopUp()->first();
        $this->setConfig();
        $this->accessToken();
    }

    public function setConfig()
    {
        $api = $this->api;

        if (!$api) {
            throw new Exception('Airtime Provider Not Found!');
        }

        $config['client_id'] = $api->credentials?->client_id;
        $config['secret_key'] = $api->credentials?->secret_key;
        $config['env'] = $api->env;

        if ($config['env'] == GlobalConst::ENV_PRODUCTION) {
            $config['request_url'] = $api->credentials?->production_base_url;
        } else {
            $config['request_url'] = $api->credentials?->sandbox_base_url;
        }

        $this->config = $config;

        return $this;
    }

    public function accessToken()
    {
        if (!$this->config) {
            $this->setConfig();
        }

        $request_endpoint = 'https://auth.reloadly.com/oauth/token';

        $client_id = $this->config['client_id'];
        $secret_key = $this->config['secret_key'];
        $request_url = $this->config['request_url'];

        $grant_type = 'client_credentials';

        $response = Http::post($request_endpoint, [
            'client_id' => $client_id,
            'client_secret' => $secret_key,
            'grant_type' => $grant_type,
            'audience' => $request_url,
        ])->throw(function (Response $response, RequestException $exception) {
            $response = $response->json();

            $message = $response['message'];
            $message_type = $response['errorCode'];

            $error_message = $message . ' [' . $message_type . ']';

            throw new Exception($error_message);
        })->json();

        $access_token = $response['access_token'];

        $this->access_token = $access_token;

        return $this;
    }

    public function resolveCacheKey(string $key): string
    {
        $api = $this->api;

        $provider = $api->provider;
        $env = $api->env;

        $cache_key = str_replace(['{provider}', '{env}'], [$provider, $env], $key);

        return $cache_key;
    }

    public function getCountries(?string $iso = null): array
    {
        if (!$this->access_token) {
            $this->accessToken();
        }

        $access_token = $this->access_token;

        $base_url = $this->config['request_url'];

        $request_endpoint = $base_url . '/countries' . ($iso ? '/' . $iso : '');
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $access_token,
                'Accept' => 'application/com.reloadly.topups-v1+json',
            ])->get($request_endpoint)->throw(function (Response $response, RequestException $exception) {
                // handled below
            })->json();
        } catch (RequestException $e) {
            $error_response = json_decode($e->response->body(), true);
            $data = [
                'status' => false,
                'message' => $error_response['message'] ?? '',
                'errorCode' => $error_response['errorCode'] ?? '',
            ];
            return $data;
        }
        if (!is_array($response)) {
            throw new Exception(__('Something went wrong! Please try again.'));
        }
        return $response;
    }

    public function autoDetectOperator(string $phone, string $iso)
    {
        if (!$this->access_token) {
            $this->accessToken();
        }
        $access_token = $this->access_token;
        $base_url = $this->config['request_url'];
        $request_endpoint = $base_url . "/operators/auto-detect/phone/$phone/country-code/" . $iso . '?&suggestedAmountsMap=true';
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $access_token,
                'Accept' => 'application/com.reloadly.utilities-v1+json',
            ])->get($request_endpoint)->throw(function (Response $response, RequestException $exception) {
                // handled below
            })->json();
        } catch (RequestException $e) {
            $error_response = json_decode($e->response->body(), true);
            $data = [
                'status' => false,
                'message' => $error_response['message'] ?? '',
                'errorCode' => $error_response['errorCode'] ?? '',
            ];
            return $data;
        }
        if (!is_array($response)) {
            throw new Exception(__('Something went wrong! Please try again.'));
        }
        return $response;
    }

    public function makeTopUp(array $data): array
    {
        if (!$this->access_token) {
            $this->accessToken();
        }

        $base_url = $this->config['request_url'];
        $endpoint = $base_url . '/topups';

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->access_token,
                'Accept' => 'application/com.reloadly.topups-v1+json',
            ])->post($endpoint, $data)->throw(function (Response $response, RequestException $exception) {
                $response_array = $response->json();
                $message = $response_array['message'] ?? '';
                throw new Exception($message);
            })->json();
        } catch (Exception $e) {
            $data = [
                'status' => false,
                'message' => $e->getMessage(),
            ];
            return $data;
        }

        if (!is_array($response)) {
            throw new Exception(__('Something went wrong! Please try again.'));
        }

        return $response;
    }
}
