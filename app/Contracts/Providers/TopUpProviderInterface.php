<?php

namespace App\Contracts\Providers;

/**
 * Wallet top-up providers (cash-in channels) must implement this contract.
 */
interface TopUpProviderInterface
{
    /**
     * Create an invoice or payment intent for wallet funding.
     */
    public function createInvoice(array $payload): array;

    /**
     * Confirm that a pending top-up has been completed successfully.
     */
    public function confirm(array $payload): array;

    /**
     * Cancel a pending invoice or funding attempt.
     */
    public function cancel(array $payload): array;
}
