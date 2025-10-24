<?php

namespace App\Contracts\Providers;

/**
 * Defines the contract all payment providers must follow so that
 * routing and transaction orchestration can interact with them in a
 * provider-agnostic way.
 */
interface PaymentProviderInterface
{
    /**
     * Prepare a payment session with the remote PSP or gateway.
     */
    public function init(array $payload): array;

    /**
     * Authorize a prepared payment (for two-step flows).
     */
    public function authorize(array $payload): array;

    /**
     * Capture funds for an authorized payment.
     */
    public function capture(array $payload): array;

    /**
     * Issue a payout/settlement to a beneficiary.
     */
    public function payout(array $payload): array;

    /**
     * Refund a captured transaction.
     */
    public function refund(array $payload): array;

    /**
     * Retrieve the current status of a transaction by its provider reference.
     */
    public function getStatus(array $payload): array;

    /**
     * Validate the authenticity of a webhook notification.
     */
    public function webhookVerify(array $headers, string $rawBody): bool;
}
