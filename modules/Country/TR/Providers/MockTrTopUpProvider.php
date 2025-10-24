<?php

namespace Modules\Country\TR\Providers;

use App\Contracts\Providers\TopUpProviderInterface;

class MockTrTopUpProvider implements TopUpProviderInterface
{
    public function createInvoice(array $payload): array
    {
        return [
            'provider' => 'tr-mock-topup',
            'invoice_id' => $payload['invoice_id'] ?? 'TR-TOPUP-' . uniqid('', true),
            'status' => 'PENDING',
            'amount' => $payload['amount'] ?? 0,
            'currency' => $payload['currency'] ?? 'TRY',
            'expires_at' => $payload['expires_at'] ?? gmdate('c', strtotime('+15 minutes')),
        ];
    }

    public function confirm(array $payload): array
    {
        return [
            'provider' => 'tr-mock-topup',
            'invoice_id' => $payload['invoice_id'] ?? 'TR-TOPUP-' . uniqid('', true),
            'status' => 'CONFIRMED',
            'confirmed_at' => gmdate('c'),
            'meta' => $payload,
        ];
    }

    public function cancel(array $payload): array
    {
        return [
            'provider' => 'tr-mock-topup',
            'invoice_id' => $payload['invoice_id'] ?? 'TR-TOPUP-' . uniqid('', true),
            'status' => 'CANCELLED',
            'meta' => $payload,
        ];
    }
}
