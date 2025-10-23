<?php

namespace App\Services\Providers;

use App\Services\Contracts\PaymentProvider;

class NullPaymentProvider implements PaymentProvider
{
    public function listPayments(): array
    {
        return [];
    }

    public function processPayment(array $payload): array
    {
        return [
            'status' => 'unavailable',
            'reference' => $payload['reference'] ?? null,
            'message' => 'Live payment provider is not configured for CLI simulations.',
        ];
    }

    public function refundPayment(string $reference): array
    {
        return [
            'status' => 'unavailable',
            'reference' => $reference,
            'message' => 'Live payment provider is not configured for CLI simulations.',
        ];
    }
}
