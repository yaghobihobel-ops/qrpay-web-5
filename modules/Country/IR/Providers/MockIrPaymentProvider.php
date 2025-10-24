<?php

namespace Modules\Country\IR\Providers;

use App\Contracts\Providers\PaymentProviderInterface;

class MockIrPaymentProvider implements PaymentProviderInterface
{
    public function init(array $payload): array
    {
        return [
            'provider' => 'ir-mock-payment',
            'reference' => $payload['reference'] ?? 'IR-MOCK-' . uniqid('', true),
            'status' => 'INITIATED',
            'amount' => $payload['amount'] ?? 0,
            'currency' => $payload['currency'] ?? 'IRR',
        ];
    }

    public function authorize(array $payload): array
    {
        return $this->simpleResponse('AUTHORIZED', $payload);
    }

    public function capture(array $payload): array
    {
        return $this->simpleResponse('CAPTURED', $payload);
    }

    public function payout(array $payload): array
    {
        return $this->simpleResponse('SETTLED', $payload);
    }

    public function refund(array $payload): array
    {
        return $this->simpleResponse('REFUNDED', $payload);
    }

    public function getStatus(array $payload): array
    {
        return $this->simpleResponse('STATUS', $payload);
    }

    public function webhookVerify(array $headers, string $rawBody): bool
    {
        return true;
    }

    protected function simpleResponse(string $status, array $payload): array
    {
        return [
            'provider' => 'ir-mock-payment',
            'reference' => $payload['reference'] ?? 'IR-MOCK-' . uniqid('', true),
            'status' => $status,
            'meta' => $payload,
        ];
    }
}
