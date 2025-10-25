<?php

namespace App\Services\Payout\Regional;

use App\Services\Payout\PayoutProviderInterface;
use App\Services\Payout\PayoutResponse;

abstract class AbstractRegionalPayoutService implements PayoutProviderInterface
{
    protected array $config;
    protected string $countryCode;

    public function __construct(array $config, string $countryCode)
    {
        $this->config = $config;
        $this->countryCode = strtoupper($countryCode);
    }

    protected function validateAmount(float $amount): ?string
    {
        $limits = $this->config['limits'] ?? [];
        $min = $limits['min'] ?? null;
        $max = $limits['max'] ?? null;

        if ($min !== null && $amount < $min) {
            return trans('payout.amount_below_minimum', ['min' => $min, 'country' => $this->countryCode]);
        }

        if ($max !== null && $amount > $max) {
            return trans('payout.amount_above_maximum', ['max' => $max, 'country' => $this->countryCode]);
        }

        return null;
    }

    protected function applyFee(float $amount): float
    {
        $fee = $this->config['fee'] ?? 0.0;

        if ($fee <= 0) {
            return $amount;
        }

        $calculatedFee = $fee < 1 ? $amount * $fee : $fee;

        return round($amount + $calculatedFee, 2);
    }

    protected function generateReference(string $prefix): string
    {
        return strtoupper($prefix) . '-' . now()->format('YmdHis') . '-' . substr(bin2hex(random_bytes(4)), 0, 8);
    }

    protected function banks(): array
    {
        return $this->config['banks'] ?? [];
    }

    protected function networks(): array
    {
        return $this->config['networks'] ?? [];
    }

    protected function findBankByCode(string $code): ?array
    {
        foreach ($this->banks() as $bank) {
            if (strcasecmp($bank['code'] ?? '', $code) === 0) {
                return $bank;
            }
        }

        return null;
    }
}
