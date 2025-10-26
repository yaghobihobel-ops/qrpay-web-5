<?php

namespace App\Services\Payout;

use App\Events\PayoutWebhookReceived;
use App\Models\Admin\PaymentGateway;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class FlutterwavePayoutService implements PayoutProviderInterface
{
    protected string $baseUrl;

    protected string $secretKey;

    protected int $timeout;

    protected int $retryTimes;

    protected int $retrySleepMs;

    public function __construct(protected HttpFactory $http)
    {
        $this->baseUrl = rtrim((string) config('payout.providers.flutterwave.base_url'), '/');
        $this->secretKey = (string) config('payout.providers.flutterwave.secret_key');
        $this->timeout = (int) config('payout.http.timeout', 10);
        $this->retryTimes = (int) config('payout.http.retry.times', 2);
        $this->retrySleepMs = (int) config('payout.http.retry.sleep', 200);
    }

    public function initiateTransfer(object $moneyOutData, PaymentGateway $gateway, array $attributes = []): PayoutResponse
    {
        $secretKey = $this->resolveSecretKey($gateway);
        $baseUrl = $this->resolveBaseUrl($gateway);
        $payload = [
            'account_bank' => $attributes['bank_name'] ?? $attributes['account_bank'] ?? null,
            'account_number' => $attributes['account_number'] ?? null,
            'amount' => $moneyOutData->will_get ?? $attributes['amount'] ?? null,
            'narration' => $attributes['narration'] ?? __('Withdraw from wallet'),
            'currency' => $moneyOutData->gateway_currency ?? $attributes['currency'] ?? null,
            'reference' => $attributes['reference'] ?? generateTransactionReference(),
            'callback_url' => $attributes['callback_url'] ?? url('/flutterwave/withdraw_webhooks'),
            'debit_currency' => $moneyOutData->gateway_currency ?? $attributes['debit_currency'] ?? null,
            'beneficiary_name' => $attributes['beneficiary_name'] ?? '',
        ];

        if (! empty($attributes['destination_branch_code'])) {
            $payload['destination_branch_code'] = $attributes['destination_branch_code'];
        }

        $maskedPayload = $this->maskSensitive($payload);
        Log::info('Initiating Flutterwave payout transfer', [
            'provider' => 'flutterwave',
            'payload' => $maskedPayload,
        ]);

        $response = $this->sendRequest('post', '/transfers', $payload, $secretKey, $baseUrl);

        $data = $response->json() ?? [];
        $status = $data['status'] ?? null;
        $message = $data['message'] ?? $response->body();

        Log::info('Flutterwave payout transfer response', [
            'provider' => 'flutterwave',
            'status_code' => $response->status(),
            'status' => $status,
            'message' => $message,
        ]);

        $successful = $response->successful() && $status === 'success';

        return new PayoutResponse(
            $successful,
            $data,
            is_string($message) ? $message : null,
            $response->status()
        );
    }

    public function verifyBankAccount(string $accountNumber, string $bankCode, array $context = []): array
    {
        $gateway = $context['gateway'] ?? null;
        $secretKey = $this->resolveSecretKey($gateway instanceof PaymentGateway ? $gateway : null);
        $baseUrl = $this->resolveBaseUrl($gateway instanceof PaymentGateway ? $gateway : null);

        $payload = [
            'account_number' => $accountNumber,
            'account_bank' => $bankCode,
        ];

        Log::info('Verifying Flutterwave bank account', [
            'provider' => 'flutterwave',
            'account_number' => Str::mask($accountNumber, '*', 0, max(strlen($accountNumber) - 4, 0)),
            'bank_code' => $bankCode,
        ]);

        $response = $this->sendRequest('post', '/accounts/resolve', $payload, $secretKey, $baseUrl);

        return $response->json() ?? [];
    }

    public function handleWebhook(array $payload): void
    {
        $status = Arr::get($payload, 'data.status');
        if (! in_array($status, ['SUCCESSFUL', 'FAILED'], true)) {
            Log::warning('Received unsupported Flutterwave payout webhook status', [
                'status' => $status,
            ]);
            return;
        }

        event(new PayoutWebhookReceived($payload));
    }

    protected function resolveSecretKey(?PaymentGateway $gateway = null): string
    {
        $secretKey = $this->secretKey;
        if (empty($secretKey) && $gateway && $gateway->credentials) {
            $secretKey = (string) getPaymentCredentials($gateway->credentials, 'Secret key');
        }

        if (empty($secretKey)) {
            throw new RuntimeException('Flutterwave secret key is not configured.');
        }

        return $secretKey;
    }

    protected function resolveBaseUrl(?PaymentGateway $gateway = null): string
    {
        $baseUrl = $this->baseUrl;
        if (empty($baseUrl) && $gateway && $gateway->credentials) {
            $baseUrl = (string) getPaymentCredentials($gateway->credentials, 'Base Url');
        }

        if (empty($baseUrl)) {
            throw new RuntimeException('Flutterwave base URL is not configured.');
        }

        return rtrim($baseUrl, '/');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function maskSensitive(array $payload): array
    {
        $payload['account_number'] = isset($payload['account_number'])
            ? Str::mask((string) $payload['account_number'], '*', 0, max(strlen((string) $payload['account_number']) - 4, 0))
            : null;
        $payload['reference'] = isset($payload['reference'])
            ? Str::mask((string) $payload['reference'], '*', 4, max(strlen((string) $payload['reference']) - 8, 0))
            : null;

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function sendRequest(string $method, string $uri, array $payload, string $secretKey, ?string $baseUrl = null): Response
    {
        $url = $this->buildUrl($uri, $baseUrl);

        return $this->http
            ->withToken($secretKey)
            ->acceptJson()
            ->timeout($this->timeout)
            ->retry($this->retryTimes, $this->retrySleepMs)
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->{$method}($url, $payload);
    }

    protected function buildUrl(string $uri, ?string $baseUrl = null): string
    {
        $base = rtrim($baseUrl ?? $this->baseUrl, '/');

        if (empty($base)) {
            throw new RuntimeException('Flutterwave base URL is not configured.');
        }

        return $base . '/' . ltrim($uri, '/');
    }
}
