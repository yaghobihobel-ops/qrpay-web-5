<?php

namespace App\Services\Payouts\Sandbox;

use App\Contracts\Payouts\PayoutProviderInterface;

class PayoutSandbox
{
    public function __construct(protected PayoutProviderInterface $provider)
    {
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function wallet(array $payload): array
    {
        return $this->provider->simulateWalletDisbursement($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function qr(array $payload): array
    {
        return $this->provider->simulateQrDisbursement($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function bank(array $payload): array
    {
        return $this->provider->simulateBankTransfer($payload);
    }

    public function status(string $reference): array
    {
        return $this->provider->fetchPayoutStatus($reference);
    }
}
