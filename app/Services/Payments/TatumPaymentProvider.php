<?php

namespace App\Services\Payments;

use App\Http\Helpers\PaymentGateway;

class TatumPaymentProvider extends AbstractPaymentProvider
{
    protected string $defaultInitializeMethod = 'tatumInit';

    public function initialize(PaymentGateway $gateway, array $context = [])
    {
        $method = $context['method'] ?? $this->defaultInitializeMethod;

        if (method_exists($gateway, $method)) {
            return $gateway->$method($context['output'] ?? null);
        }

        return parent::initialize($gateway, $context);
    }
}
