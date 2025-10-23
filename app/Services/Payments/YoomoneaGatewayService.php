<?php

namespace App\Services\Payments;

use App\Contracts\Payments\RegionalPaymentProviderInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class YoomoneaGatewayService implements RegionalPaymentProviderInterface
{
    protected array $config;

    protected bool $sandbox;

    protected ?string $token = null;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?? config('payments.providers.yoomonea', []);
        $this->sandbox = (bool) config('payments.feature_flags.sandbox', true);
    }

    public function authenticate(): string
    {
        $seed = implode('|', [
            Arr::get($this->config, 'client_id'),
            Arr::get($this->config, 'client_secret'),
            now()->timestamp,
        ]);

        $this->token = hash('sha512', $seed);

        return $this->token;
    }

    public function initiateDigitalWalletPayment(array $payload): array
    {
        if ($this->sandbox) {
            return $this->simulateDigitalWalletPayment($payload);
        }

        return $this->dispatchSignedPayload('wallet', $payload);
    }

    public function initiateQrPayment(array $payload): array
    {
        if ($this->sandbox) {
            return $this->simulateQrPayment($payload);
        }

        return $this->dispatchSignedPayload('qr', $payload);
    }

    public function initiateBankRemittance(array $payload): array
    {
        if ($this->sandbox) {
            return $this->simulateBankRemittance($payload);
        }

        return $this->dispatchSignedPayload('bank', $payload);
    }

    public function verifySignature(array $payload, string $signature): bool
    {
        if ($this->sandbox && ! config('payments.sandbox.allow_mock_signatures')) {
            return false;
        }

        return hash_equals($this->signatureForPayload($payload), $signature);
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

    public function simulateDigitalWalletPayment(array $payload): array
    {
        return $this->buildSandboxResponse('wallet', $payload, [
            'session' => 'YMN-WALLET-' . strtoupper(Str::random(6)),
        ]);
    }

    public function simulateQrPayment(array $payload): array
    {
        return $this->buildSandboxResponse('qr', $payload, [
            'qr_code' => 'YMN-' . strtoupper(Str::random(8)),
        ]);
    }

    public function simulateBankRemittance(array $payload): array
    {
        return $this->buildSandboxResponse('bank', $payload, [
            'remittance_id' => Str::uuid()->toString(),
        ]);
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
        $secret = Arr::get($this->config, 'client_secret', '');

        return hash_hmac('sha1', json_encode($payload, JSON_THROW_ON_ERROR), $secret);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $extras
     * @return array<string, mixed>
     */
    protected function buildSandboxResponse(string $channel, array $payload, array $extras = []): array
    {
        $delay = (int) config('payments.sandbox.default_response_delay', 150);

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
