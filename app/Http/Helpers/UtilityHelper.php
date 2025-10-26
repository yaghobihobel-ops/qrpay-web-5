<?php

namespace App\Http\Helpers;

use App\Constants\GlobalConst;
use App\Models\Admin\ReloadlyApi;
use App\Services\Monitoring\DomainInstrumentation;
use Exception;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

class UtilityHelper{

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

    protected DomainInstrumentation $instrumentation;

    protected string $domain = 'payment';

    /**
     * Get Billers Cache KEY
     */
    const BILLERS_CACHE_KEY = "utilities_biller_api_{provider}_{env}";

    /**
     * API Status
     */
    const STATUS_SUCCESS    = "SUCCESSFUL";
    const STATUS_PENDING    = "PENDING";
    const STATUS_PROCESSING = "PROCESSING";
    const STATUS_REFUNDED   = "REFUNDED";
    const STATUS_FAILED     = "FAILED";

    /**
     * Price Types
     */
    const PRICE_TYPES = [
        "FIXED" => "FIXED",
        "RANGE" => "RANGE",
    ];

    public function __construct(?DomainInstrumentation $instrumentation = null)
    {
        $this->instrumentation = $instrumentation ?: app(DomainInstrumentation::class);
        $this->api = ReloadlyApi::reloadly()->utilityPayment()->first();
        $this->setConfig();
        $this->accessToken();
    }
    /**
     * Set configuration
     */
    public function setConfig()
    {
        $api = $this->api;

        if(!$api) throw new Exception("Utility Payment Provider Not Found!");

        $config['client_id']    = $api->credentials?->client_id;
        $config['secret_key']   = $api->credentials?->secret_key;
        $config['env']          = $api->env;

        if($config['env'] == GlobalConst::ENV_PRODUCTION) {
            $config['request_url']  = $api->credentials?->production_base_url;
        }else {
            $config['request_url']  = $api->credentials?->sandbox_base_url;
        }

        $this->config = $config;

        return $this;
    }
    /**
     * Authenticate API access token retrieve
     */
    public function accessToken()
    {
        if(!$this->config) $this->setConfig();

        $api = $this->api;

        $request_endpoint = "https://auth.reloadly.com/oauth/token";

        $client_id = $this->config['client_id'];
        $secret_key = $this->config['secret_key'];
        $request_url    = $this->config['request_url'];

        $grant_type = "client_credentials";

        $response = Http::post($request_endpoint,[
            "client_id" => $client_id,
            "client_secret" => $secret_key,
            "grant_type" => $grant_type,
            "audience" => $request_url,
        ])->throw(function(Response $response, RequestException $exception) {
            $response = $response->json();

            $message = $response['message'];
            $message_type   = $response['errorCode'];

            $error_message = $message . " [$message_type]";

            throw new Exception($error_message);

        })->json();


        $access_token = $response['access_token'];
        $expire_in      = $response['expires_in'];

        $this->access_token = $access_token;

        return $this;
    }
    /**
     * Resolve cache key
     */
    public function resolveCacheKey(string $key):string
    {
        $api = $this->api;

        $provider = $api->provider;
        $env = $api->env;

        $cache_key = str_replace(['{provider}','{env}'],[$provider, $env], $key);

        return $cache_key;
    }
    /**
     * get all billers information
     */
    public function getBillers():array
    {
        // $biller_cache_key = $this->resolveCacheKey(self::BILLERS_CACHE_KEY);
        // if(cache()->driver('file')->get($biller_cache_key)) return cache()->driver('file')->get($biller_cache_key);

        if(!$this->access_token) $this->accessToken();

        $access_token = $this->access_token;

        $base_url = $this->config['request_url'];

        $request_endpoint = $base_url . "/billers";
        $config = config($this->domain, []);
        $context = $this->instrumentation->startOperation($this->domain, 'fetch_billers', $config, [
            'provider' => $this->api->provider ?? data_get($config, 'credentials.provider', 'unknown'),
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer " . $access_token,
                "Accept: application/com.reloadly.utilities-v1+json",
            ])->get($request_endpoint)->throw(function(Response $response, RequestException $exception) {
                throw new Exception($exception->getMessage());
            })->json();
        } catch (RequestException $exception) {
            $error_response = $exception->response?->json() ?? [];
            $data = [
                'status' => false,
                'message' => $error_response['message'] ?? $exception->getMessage(),
                'errorCode' => $error_response['errorCode'] ?? null,
            ];
            $this->instrumentation->recordFailure($context, $exception, $data);
            return $data;
        } catch (Throwable $exception) {
            $this->instrumentation->recordFailure($context, $exception);
            throw $exception;
        }

        if(!is_array($response)) {
            $exception = new Exception(__("Something went wrong! Please try again."));
            $this->instrumentation->recordFailure($context, $exception);
            throw $exception;
        }

        $this->instrumentation->recordSuccess($context, ['billers' => is_countable($response) ? count($response) : null]);

        // cache()->driver('file')->put($biller_cache_key, $response, 43200);

        return $response;
    }
    /**
     * get all billers information
     */
    public function getSingleBiller($id)
    {
        if(!$this->access_token) $this->accessToken();
        $access_token = $this->access_token;
        $base_url = $this->config['request_url'];
        $request_endpoint = $base_url . "/billers?id=".$id;
        $config = config($this->domain, []);
        $context = $this->instrumentation->startOperation($this->domain, 'fetch_biller', $config, [
            'provider' => $this->api->provider ?? data_get($config, 'credentials.provider', 'unknown'),
            'biller_id' => $id,
        ]);
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer " . $access_token,
                "Accept: application/com.reloadly.utilities-v1+json",
            ])->get($request_endpoint)->throw(function(Response $response, RequestException $exception) {
                throw new Exception($exception->getMessage());
            })->json();
        } catch (RequestException $exception) {
            $error_response = $exception->response?->json() ?? [];
            $data = [
                'status' => false,
                'message' => $error_response['message'] ?? $exception->getMessage(),
                'errorCode' => $error_response['errorCode'] ?? null,
            ];
            $this->instrumentation->recordFailure($context, $exception, $data);
            return $data;
        } catch (Throwable $exception) {
            $this->instrumentation->recordFailure($context, $exception);
            throw $exception;
        }
        if(!is_array($response)) {
            $exception = new Exception(__("Something went wrong! Please try again."));
            $this->instrumentation->recordFailure($context, $exception);
            throw $exception;
        }
        $this->instrumentation->recordSuccess($context, ['biller_id' => data_get($response, '0.id')]);
        return $response;
    }
    /**
     * Payment Utility Bill
     */
    public function payUtilityBill(array $data)
    {
        if(!$this->access_token) $this->accessToken();

        $base_url = $this->config['request_url'];
        $endpoint = $base_url . "/pay";
        $config = config($this->domain, []);
        $context = $this->instrumentation->startOperation($this->domain, 'pay_utility_bill', $config, [
            'provider' => $this->api->provider ?? data_get($config, 'credentials.provider', 'unknown'),
            'amount' => data_get($data, 'amount'),
            'biller_id' => data_get($data, 'billerId'),
        ]);

        try{
            $response = Http::withHeaders([
                "Authorization" => "Bearer " . $this->access_token,
                "Accept: application/com.reloadly.utilities-v1+json",
            ])->post($endpoint, $data)->throw(function(Response $response, RequestException $exception) {
                $response_array = $response->json();
                $message = $response_array['message'] ?? "";
                throw new Exception($message);
            })->json();
        }catch(Exception $e){
            $payload =[
                'status' =>false,
                'message' => $e->getMessage()
            ];
            $this->instrumentation->recordFailure($context, $e, $payload);
            return $payload;
        }

        if(!is_array($response)) {
            $exception = new Exception(__("Something went wrong! Please try again."));
            $this->instrumentation->recordFailure($context, $exception);
            throw $exception;
        }

        $this->instrumentation->recordSuccess($context, [
            'transaction_id' => $response['transactionId'] ?? null,
            'status' => $response['status'] ?? null,
        ]);

        return $response;
    }
   /**
     * get all billers information
     */
    public function getTransaction($id)
    {
        if(!$this->access_token) $this->accessToken();
        $access_token = $this->access_token;
        $base_url = $this->config['request_url'];
        $request_endpoint = $base_url . "/transactions"."/".$id;
        $config = config($this->domain, []);
        $context = $this->instrumentation->startOperation($this->domain, 'fetch_payment_transaction', $config, [
            'provider' => $this->api->provider ?? data_get($config, 'credentials.provider', 'unknown'),
            'transaction_id' => $id,
        ]);
        try{
            $response = Http::withHeaders([
                'Authorization' => "Bearer " . $access_token,
                "Accept: application/com.reloadly.utilities-v1+json",
            ])->get($request_endpoint)->throw(function(Response $response, RequestException $exception) {
                throw new Exception($exception->getMessage());
            })->json();
        }catch(Exception $e){
            $data =[
                'status' =>false,
                'message' => $e->getMessage()
            ];
            $this->instrumentation->recordFailure($context, $e, $data);
            return $data;
        }
        if(!is_array($response)) {
            $exception = new Exception(__("Something went wrong! Please try again."));
            $this->instrumentation->recordFailure($context, $exception);
            throw $exception;
        }
        $this->instrumentation->recordSuccess($context, ['status' => $response['status'] ?? null]);
        return $response;
    }
}
