<?php

namespace App\Services\Airwallex;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use RuntimeException;

class AirwallexClient
{
    public function __construct(private readonly HttpFactory $http)
    {
    }

    /**
     * Retrieve an authentication token from Airwallex.
     */
    public function authenticate(): array
    {
        $response = $this->request()
            ->withHeaders($this->authenticationHeaders())
            ->post($this->config('authentication_path'));

        return $response->throw()->json();
    }

    /**
     * Retrieve cardholder information.
     */
    public function listCardholders(string $token, array $filters = []): array
    {
        $response = $this->request()
            ->withToken($token)
            ->get($this->config('cardholders_path'), $filters);

        return $response->throw()->json();
    }

    /**
     * Create a new cardholder record.
     */
    public function createCardholder(string $token, array $payload): array
    {
        $response = $this->request()
            ->withToken($token)
            ->asJson()
            ->post($this->config('cardholder_create_path'), $payload);

        return $response->throw()->json();
    }

    private function request(): PendingRequest
    {
        $baseUrl = rtrim((string) $this->config('base_url'), '/');

        if ($baseUrl === '') {
            throw new RuntimeException('Airwallex base URL is not configured.');
        }

        return $this->http
            ->baseUrl($baseUrl)
            ->timeout((int) $this->config('timeout', 30));
    }

    private function authenticationHeaders(): array
    {
        $clientId = (string) $this->config('client_id');
        $apiKey = (string) $this->config('api_key');

        if ($clientId === '' || $apiKey === '') {
            throw new RuntimeException('Airwallex credentials are not configured.');
        }

        return [
            'Content-Type' => 'application/json',
            'x-client-id' => $clientId,
            'x-api-key' => $apiKey,
        ];
    }

    private function config(string $key, mixed $default = null): mixed
    {
        $value = config("airwallex.{$key}", $default);

        if (is_string($value)) {
            return trim($value);
        }

        return $value;
    }
}
