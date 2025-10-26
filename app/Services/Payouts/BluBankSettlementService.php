<?php

namespace App\Services\Payouts;

use App\Contracts\Payouts\PayoutProviderInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use function filter_var;

class BluBankSettlementService implements PayoutProviderInterface
{
    protected array $config;

    protected bool $sandbox;

    protected ?string $token = null;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?? config('payouts.providers.blubank', []);
        $this->sandbox = filter_var(config('payouts.feature_flags.sandbox', true), FILTER_VALIDATE_BOOLEAN);
    }

    public function authenticate(): string
    {
        $seed = implode('|', [
            Arr::get($this->config, 'merchant_id'),
            now()->timestamp,
            Str::random(6),
        ]);

        $this->token = base64_encode($seed);

        return $this->token;
    }

    public function initiateWalletDisbursement(array $payload): array
    {
        if ($this->sandbox) {
            return $this->simulateWalletDisbursement($payload);
        }

        return $this->dispatchSignedPayload('wallet', $payload);
    }

    public function initiateQrDisbursement(array $payload): array
    {
        if ($this->sandbox) {
            return $this->simulateQrDisbursement($payload);
        }

        return $this->dispatchSignedPayload('qr', $payload);
    }

    public function initiateBankTransfer(array $payload): array
    {
        if ($this->sandbox) {
            return $this->simulateBankTransfer($payload);
        }

        return $this->dispatchSignedPayload('bank', $payload);
    }

    public function verifySignature(array $payload, string $signature): bool
    {
        $expected = $this->signatureForPayload($payload);

        if ($this->sandbox && ! config('payouts.sandbox.allow_mock_signatures')) {
            return false;
        }

        return hash_equals($expected, $signature);
    }

    public function convertCurrency(string $fromCurrency, string $toCurrency, float $amount): float
    {
        $rates = Arr::get($this->config, 'exchange_rates', []);
        $fromRate = (float) Arr::get($rates, strtoupper($fromCurrency), 1.0);
        $toRate = (float) Arr::get($rates, strtoupper($toCurrency), 1.0);

        if ($fromRate === 0.0) {
            return 0.0;
        }

        return round($amount * ($toRate / $fromRate), 2);
    }

    public function simulateWalletDisbursement(array $payload): array
    {
        return $this->buildSandboxResponse('wallet', $payload, [
            'wallet_reference' => 'BLU-WAL-' . strtoupper(Str::random(5)),
        ]);
    }

    public function simulateQrDisbursement(array $payload): array
    {
        return $this->buildSandboxResponse('qr', $payload, [
            'qr_token' => Str::uuid()->toString(),
        ]);
    }

    public function simulateBankTransfer(array $payload): array
    {
        return $this->buildSandboxResponse('bank', $payload, [
            'transfer_reference' => 'BLU-' . strtoupper(Str::random(10)),
        ]);
    }

    public function fetchPayoutStatus(string $reference): array
    {
        if ($this->sandbox) {
            return [
                'reference' => $reference,
                'status' => 'sandbox_settled',
                'mode' => 'sandbox',
            ];
        }

        $endpoint = Arr::get($this->config, 'endpoints.status');

        return [
            'endpoint' => $endpoint,
            'request' => [
                'token' => $this->token ?? $this->authenticate(),
                'reference' => $reference,
            ],
        ];
    }

    protected function dispatchSignedPayload(string $channel, array $payload): array
    {
        $endpoint = Arr::get($this->config, "endpoints.{$channel}");

        $body = [
            'token' => $this->token ?? $this->authenticate(),
            'payload' => $payload,
            'signature' => $this->signatureForPayload($payload),
        ];

        return [
            'endpoint' => $endpoint,
            'request' => $body,
        ];
    }

    protected function signatureForPayload(array $payload): string
    {
        $secret = Arr::get($this->config, 'secret_key', '');

        return hash_hmac('sha256', json_encode($payload, JSON_THROW_ON_ERROR), $secret);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $extras
     * @return array<string, mixed>
     */
    protected function buildSandboxResponse(string $channel, array $payload, array $extras = []): array
    {
        $delay = (int) config('payouts.sandbox.default_response_delay', 200);

        usleep(max($delay, 1) * 1000);

        return array_merge([
            'channel' => $channel,
            'mode' => 'sandbox',
            'payload' => $payload,
            'signature' => $this->signatureForPayload($payload),
            'token' => $this->token ?? $this->authenticate(),
        ], $extras);
    }
}
