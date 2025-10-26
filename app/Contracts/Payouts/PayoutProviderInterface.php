<?php

namespace App\Contracts\Payouts;

interface PayoutProviderInterface
{
    /**
     * Perform the upstream authentication handshake.
     */
    public function authenticate(): string;

    /**
     * Send funds to a digital wallet beneficiary.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function initiateWalletDisbursement(array $payload): array;

    /**
     * Share a QR disbursement request that can be settled by agents.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function initiateQrDisbursement(array $payload): array;

    /**
     * Wire funds to a traditional bank account.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function initiateBankTransfer(array $payload): array;

    /**
     * Allow webhook callbacks to be validated before processing status updates.
     *
     * @param  array<string, mixed>  $payload
     */
    public function verifySignature(array $payload, string $signature): bool;

    /**
     * Convert an amount for payouts requiring a specific settlement currency.
     */
    public function convertCurrency(string $fromCurrency, string $toCurrency, float $amount): float;

    /**
     * Simulate a wallet disbursement in sandbox mode.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function simulateWalletDisbursement(array $payload): array;

    /**
     * Simulate a QR disbursement without calling the provider.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function simulateQrDisbursement(array $payload): array;

    /**
     * Simulate a bank transfer for QA validation.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function simulateBankTransfer(array $payload): array;

    /**
     * Fetch the status of an upstream disbursement.
     *
     * @return array<string, mixed>
     */
    public function fetchPayoutStatus(string $reference): array;
}
