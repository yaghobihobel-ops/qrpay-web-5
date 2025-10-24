<?php

namespace App\Contracts;

interface RegionalPaymentProviderInterface
{
    /**
     * Prepare the checkout payload for the regional provider.
     *
     * @param  array  $payload
     * @return array
     */
    public function prepareCheckout(array $payload): array;

    /**
     * Execute a payment request against the provider.
     *
     * @param  array  $payload
     * @return array
     */
    public function executePayment(array $payload): array;

    /**
     * Refund a previously executed payment.
     *
     * @param  array  $payload
     * @return array
     */
    public function refundPayment(array $payload): array;

    /**
     * Determine whether the provider can handle the supplied currency code.
     *
     * @param  string  $currency
     * @return bool
     */
    public function supportsCurrency(string $currency): bool;
}
