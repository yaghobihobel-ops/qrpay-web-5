<?php

namespace App\Services\Payments\Sandbox;

use App\Contracts\Payments\RegionalPaymentProviderInterface;

class PaymentSandbox
{
    public function __construct(protected RegionalPaymentProviderInterface $provider)
    {
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function digitalWallet(array $payload): array
    {
        return $this->provider->simulateDigitalWalletPayment($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function qr(array $payload): array
    {
        return $this->provider->simulateQrPayment($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function bank(array $payload): array
    {
        return $this->provider->simulateBankRemittance($payload);
    }
}
