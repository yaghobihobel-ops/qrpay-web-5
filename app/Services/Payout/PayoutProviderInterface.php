<?php

namespace App\Services\Payout;

use App\Models\Admin\PaymentGateway;

interface PayoutProviderInterface
{
    /**
     * Initiate a payout transfer request with the upstream provider.
     *
     * @param  object  $moneyOutData
     * @param  \App\Models\Admin\PaymentGateway  $gateway
     * @param  array<string, mixed>  $attributes
     */
    public function initiateTransfer(object $moneyOutData, PaymentGateway $gateway, array $attributes = []): PayoutResponse;

    /**
     * Verify a beneficiary account with the upstream provider.
     *
     * @param  string  $accountNumber
     * @param  string  $bankCode
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function verifyBankAccount(string $accountNumber, string $bankCode, array $context = []): array;

    /**
     * Handle webhook payloads posted by the upstream provider.
     *
     * @param  array<string, mixed>  $payload
     */
    public function handleWebhook(array $payload): void;
}
