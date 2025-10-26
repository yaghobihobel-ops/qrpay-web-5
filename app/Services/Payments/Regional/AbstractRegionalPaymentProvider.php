<?php

namespace App\Services\Payments\Regional;

use App\Contracts\RegionalPaymentProviderInterface;
use App\Services\Payments\InternalWalletService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;

abstract class AbstractRegionalPaymentProvider implements RegionalPaymentProviderInterface
{
    protected string $currencyCode;

    protected string $name;

    protected InternalWalletService $walletService;

    protected array $config;

    public function __construct(InternalWalletService $walletService, array $config = [])
    {
        $this->walletService = $walletService;
        $this->config = $config;

        if (isset($config['currency'])) {
            $this->currencyCode = strtoupper($config['currency']);
        }

        if (isset($config['name'])) {
            $this->name = $config['name'];
        }
    }

    public function supportsCurrency(string $currency): bool
    {
        return strtoupper($currency) === strtoupper($this->currencyCode ?? $currency);
    }

    public function prepareCheckout(array $payload): array
    {
        $wallet = $this->extractWallet($payload);
        $amount = (float) ($payload['amount'] ?? 0);
        $this->walletService->reserveFunds($wallet, $amount);

        return [
            'provider' => $this->name ?? static::class,
            'currency' => $this->currencyCode,
            'amount' => $amount,
            'reference' => $payload['reference'] ?? (string) Str::uuid(),
            'meta' => $this->meta($payload),
        ];
    }

    public function executePayment(array $payload): array
    {
        $wallet = $this->extractWallet($payload);
        $amount = (float) ($payload['amount'] ?? 0);
        $this->walletService->capture($wallet, $amount);

        return [
            'provider' => $this->name ?? static::class,
            'currency' => $this->currencyCode,
            'amount' => $amount,
            'status' => 'captured',
        ];
    }

    public function refundPayment(array $payload): array
    {
        $wallet = $this->extractWallet($payload);
        $amount = (float) ($payload['amount'] ?? 0);
        $this->walletService->refund($wallet, $amount);

        return [
            'provider' => $this->name ?? static::class,
            'currency' => $this->currencyCode,
            'amount' => $amount,
            'status' => 'refunded',
        ];
    }

    protected function extractWallet(array $payload): Model
    {
        if (!isset($payload['wallet']) || !$payload['wallet'] instanceof Model) {
            throw new InvalidArgumentException('A valid wallet model instance is required for regional payments.');
        }

        return $payload['wallet'];
    }

    protected function meta(array $payload): array
    {
        $configMeta = Arr::only($this->config, [
            'api_url',
            'api_key',
            'merchant_id',
            'network',
            'settlement_account',
            'connector_bank',
            'callback_url',
        ]);

        return array_merge($configMeta, $payload['meta'] ?? []);
    }
}
