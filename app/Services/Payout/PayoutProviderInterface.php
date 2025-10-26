<?php

namespace App\Services\Payout;

interface PayoutProviderInterface
{
    /**
     * Lookup a bank or payout destination using provider-specific identifiers.
     */
    public function lookupBank(array $payload): PayoutResponse;

    /**
     * Create a payout transfer with the provider.
     */
    public function createPayout(array $payload): PayoutResponse;

    /**
     * Check the status of a payout request.
     */
    public function checkStatus(string $reference, array $context = []): PayoutResponse;
}
