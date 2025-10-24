<?php

namespace App\Services\Topup;

use App\Constants\GlobalConst;
use App\Contracts\TopupProviderInterface;
use App\Models\Admin\ReloadlyApi;
use Exception;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;

class ReloadlyService implements TopupProviderInterface
{
    protected ReloadlyApi $api;

    protected ?string $accessToken = null;

    protected array $config = [];

    public function __construct(protected ReloadlyApi $reloadlyApi, protected HttpClient $http)
    {
        $this->resolveApi();
    }

    protected function resolveApi(): void
    {
        $api = $this->reloadlyApi->reloadly()->mobileTopUp()->first();

        if (!$api) {
            throw new Exception(__('Airtime Provider Not Found!'));
        }

        $this->api = $api;
        $this->setConfig();
        $this->refreshAccessToken();
    }

    protected function setConfig(): void
    {
        $api = $this->api;

        $config['client_id'] = $api->credentials?->client_id;
        $config['secret_key'] = $api->credentials?->secret_key;
        $config['env'] = $api->env;

        if ($config['env'] == GlobalConst::ENV_PRODUCTION) {
            $config['request_url'] = $api->credentials?->production_base_url;
        } else {
            $config['request_url'] = $api->credentials?->sandbox_base_url;
        }

        $this->config = $config;
    }

    protected function refreshAccessToken(): void
    {
        if (!$this->config) {
            $this->setConfig();
        }

        $requestEndpoint = 'https://auth.reloadly.com/oauth/token';

        $response = $this->http->post($requestEndpoint, [
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['secret_key'],
            'grant_type' => 'client_credentials',
            'audience' => $this->config['request_url'],
        ])->throw(function (Response $response, RequestException $exception) {
            $body = $response->json();
            $message = $body['message'] ?? $exception->getMessage();
            $messageType = $body['errorCode'] ?? '';
            $errorMessage = trim($message . ' [' . $messageType . ']');
            throw new Exception($errorMessage);
        })->json();

        $this->accessToken = $response['access_token'] ?? null;
    }

    protected function ensureAccessToken(): void
    {
        if (!$this->accessToken) {
            $this->refreshAccessToken();
        }
    }

    public function detectOperator(string $phone, string $iso): array
    {
        $this->ensureAccessToken();

        $endpoint = $this->config['request_url'] . "/operators/auto-detect/phone/{$phone}/country-code/{$iso}?&suggestedAmountsMap=true";

        try {
            return $this->http->withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Accept: application/com.reloadly.utilities-v1+json',
            ])->get($endpoint)->throw()->json();
        } catch (RequestException $exception) {
            $errorResponse = json_decode($exception->response?->body() ?? '{}', true);
            return [
                'status' => false,
                'message' => $errorResponse['message'] ?? '',
                'errorCode' => $errorResponse['errorCode'] ?? '',
            ];
        }
    }

    public function makeTopUp(array $data): array
    {
        $this->ensureAccessToken();

        $endpoint = $this->config['request_url'] . '/topups';

        try {
            return $this->http->withHeaders([
                'Accept: application/com.reloadly.topups-v1+json',
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ])->post($endpoint, $data)->throw()->json();
        } catch (RequestException $exception) {
            $errorResponse = json_decode($exception->response?->body() ?? '{}', true);
            return [
                'status' => false,
                'message' => $errorResponse['message'] ?? '',
                'errorCode' => $errorResponse['errorCode'] ?? '',
            ];
        }
    }

    public function getTransaction(string $transactionId): array
    {
        $this->ensureAccessToken();

        $endpoint = $this->config['request_url'] . '/transactions/' . $transactionId;

        try {
            return $this->http->withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Accept: application/com.reloadly.utilities-v1+json',
            ])->get($endpoint)->throw()->json();
        } catch (RequestException $exception) {
            $body = $exception->response?->json();
            return [
                'status' => false,
                'message' => $body['message'] ?? $exception->getMessage(),
            ];
        }
    }
}
