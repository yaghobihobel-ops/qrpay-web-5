<?php

namespace Modules\Country\CN\Providers;

use App\Contracts\Providers\FXProviderInterface;

class MockCnFxProvider implements FXProviderInterface
{
    public function quote(string $pair, float $amount, string $side, array $context = []): array
    {
        $rate = $this->resolveRate($pair);

        return [
            'provider' => 'cn-mock-fx',
            'pair' => strtoupper($pair),
            'side' => strtoupper($side),
            'amount' => $amount,
            'rate' => $rate,
            'converted_amount' => $amount * $rate,
            'context' => $context,
        ];
    }

    public function convert(array $payload): array
    {
        return [
            'provider' => 'cn-mock-fx',
            'reference' => $payload['quote_reference'] ?? 'CNFX-' . uniqid('', true),
            'status' => 'COMPLETED',
            'meta' => $payload,
        ];
    }

    public function settlementReport(array $payload): array
    {
        return [
            'provider' => 'cn-mock-fx',
            'generated_at' => gmdate('c'),
            'entries' => [],
            'filters' => $payload,
        ];
    }

    protected function resolveRate(string $pair): float
    {
        return match (strtoupper($pair)) {
            'IRR/CNY' => 0.00017,
            'CNY/IRR' => 5920.0,
            default => 1.0,
        };
    }
}
