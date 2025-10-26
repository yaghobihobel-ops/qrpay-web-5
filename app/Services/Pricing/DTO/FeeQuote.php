<?php

namespace App\Services\Pricing\DTO;

use App\Models\PricingRule;

class FeeQuote
{
    public function __construct(
        public readonly string $currency,
        public readonly string $provider,
        public readonly string $transactionType,
        public readonly string $userLevel,
        public readonly float $amount,
        public readonly float $totalFee,
        public readonly float $fixedFee,
        public readonly float $percentFee,
        public readonly float $appliedPercent,
        public readonly float $exchangeRate,
        public readonly PricingRule $rule,
    ) {
    }

    public function toArray(): array
    {
        return [
            'currency' => $this->currency,
            'provider' => $this->provider,
            'transaction_type' => $this->transactionType,
            'user_level' => $this->userLevel,
            'amount' => $this->amount,
            'total_fee' => $this->totalFee,
            'fixed_fee' => $this->fixedFee,
            'percent_fee' => $this->percentFee,
            'applied_percent' => $this->appliedPercent,
            'exchange_rate' => $this->exchangeRate,
            'rule_id' => $this->rule->getKey(),
            'rule_name' => $this->rule->name,
        ];
    }
}
