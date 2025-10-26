<?php

namespace App\Contracts\Payments;

interface RegionalPaymentProviderInterface
{
    /**
     * Issue an authentication token that downstream calls can reuse.
     */
    public function authenticate(): string;

    /**
     * Dispatch a digital wallet transaction to the provider.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function initiateDigitalWalletPayment(array $payload): array;

    /**
     * Request a QR-based payment session.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function initiateQrPayment(array $payload): array;

    /**
     * Trigger a regional bank remittance.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function initiateBankRemittance(array $payload): array;

    /**
     * Verify that an incoming callback signature matches the payload.
     *
     * @param  array<string, mixed>  $payload
     */
    public function verifySignature(array $payload, string $signature): bool;

    /**
     * Convert a monetary amount into the provider supported currency.
     */
    public function convertCurrency(string $fromCurrency, string $toCurrency, float $amount): float;

    /**
     * Simulate a digital wallet payment without reaching the upstream provider.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function simulateDigitalWalletPayment(array $payload): array;

    /**
     * Simulate a QR payment scenario in sandbox mode.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function simulateQrPayment(array $payload): array;

    /**
     * Simulate a bank remittance flow for integration tests.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function simulateBankRemittance(array $payload): array;
}
