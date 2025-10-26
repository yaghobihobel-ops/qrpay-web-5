<?php

namespace App\Services\Payments;

use App\Http\Helpers\PaymentGateway;

interface PaymentProviderInterface
{
    /**
     * Initialize a payment with the given gateway context.
     *
     * @param PaymentGateway $gateway
     * @param array $context
     * @return mixed
     */
    public function initialize(PaymentGateway $gateway, array $context = []);

    /**
     * Capture a payment after initialization.
     *
     * @param PaymentGateway $gateway
     * @param array $context
     * @return mixed
     */
    public function capture(PaymentGateway $gateway, array $context = []);

    /**
     * Refund a previously captured payment.
     *
     * @param PaymentGateway $gateway
     * @param array $context
     * @return mixed
     */
    public function refund(PaymentGateway $gateway, array $context = []);

    /**
     * Returns the default initialization method that should be called on the gateway helper.
     */
    public function defaultInitializeMethod(): string;
}
