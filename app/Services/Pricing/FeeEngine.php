<?php

namespace App\Services\Pricing;

use App\Models\FeeTier;
use App\Models\PricingRule;
use App\Services\Pricing\DTO\FeeQuote;
use App\Services\Pricing\Exceptions\PricingRuleNotFoundException;
use Illuminate\Support\Collection;

class FeeEngine
{
    public function __construct(
        protected ExchangeRateResolver $exchangeRateResolver
    ) {
    }

    public function quote(
        string $currency,
        string $provider,
        string $transactionType,
        string $userLevel,
        float $amount
    ): FeeQuote {
        $rule = $this->resolveRule($currency, $provider, $transactionType, $userLevel);

        [$baseRate, $spreadMultiplier] = $this->resolveRate($currency, $rule);
        $amountInBase = $amount * $baseRate;

        $tier = $this->matchTier($rule->feeTiers, $amountInBase);

        $percentFeeAmount = ($amountInBase * (float) $tier->percent_fee) / 100;
        $fixedFeeAmount = (float) $tier->fixed_fee;
        $totalFeeInBase = $percentFeeAmount + $fixedFeeAmount;

        $totalFee = ($totalFeeInBase / $baseRate) * $spreadMultiplier;
        $fixedFee = ($fixedFeeAmount / $baseRate) * $spreadMultiplier;
        $percentFee = ($percentFeeAmount / $baseRate) * $spreadMultiplier;

        return new FeeQuote(
            currency: strtoupper($currency),
            provider: $provider,
            transactionType: $transactionType,
            userLevel: $userLevel,
            amount: $amount,
            totalFee: round($totalFee, 8),
            fixedFee: round($fixedFee, 8),
            percentFee: round($percentFee, 8),
            appliedPercent: (float) $tier->percent_fee,
            exchangeRate: $baseRate * $spreadMultiplier,
            rule: $rule,
        );
    }

    protected function resolveRule(string $currency, string $provider, string $transactionType, string $userLevel): PricingRule
    {
        $candidates = PricingRule::query()
            ->with('feeTiers')
            ->active()
            ->where(function ($query) use ($currency) {
                $query->whereNull('currency')
                    ->orWhere('currency', strtoupper($currency))
                    ->orWhere('currency', '*');
            })
            ->where(function ($query) use ($provider) {
                $query->whereNull('provider')
                    ->orWhere('provider', $provider)
                    ->orWhere('provider', '*');
            })
            ->where(function ($query) use ($transactionType) {
                $query->where('transaction_type', $transactionType)
                    ->orWhere('transaction_type', '*');
            })
            ->where(function ($query) use ($userLevel) {
                $query->where('user_level', $userLevel)
                    ->orWhere('user_level', '*');
            })
            ->orderByRaw("CASE WHEN currency = ? THEN 0 WHEN currency = '*' THEN 1 ELSE 2 END", [strtoupper($currency)])
            ->orderByRaw("CASE WHEN provider = ? THEN 0 WHEN provider = '*' THEN 1 ELSE 2 END", [$provider])
            ->orderByRaw("CASE WHEN user_level = ? THEN 0 WHEN user_level = '*' THEN 1 ELSE 2 END", [$userLevel])
            ->orderByDesc('created_at')
            ->get();

        /** @var PricingRule|null $rule */
        $rule = $candidates->first();

        if (! $rule) {
            throw new PricingRuleNotFoundException('No pricing rule matched the provided parameters.');
        }

        if ($rule->feeTiers->isEmpty()) {
            throw new PricingRuleNotFoundException('Pricing rule is missing fee tiers.');
        }

        return $rule;
    }

    /**
     * @return array{0: float, 1: float}
     */
    protected function resolveRate(string $currency, PricingRule $rule): array
    {
        $currency = strtoupper($currency);
        $baseCurrency = strtoupper($rule->base_currency ?? $currency);

        $rate = $this->exchangeRateResolver->getRate($currency, $baseCurrency);
        $spreadMultiplier = 1 + ((float) $rule->spread_bps / 10000);

        return [$rate, $spreadMultiplier];
    }

    /**
     * @param Collection<int, FeeTier> $tiers
     */
    protected function matchTier(Collection $tiers, float $amountInBase): FeeTier
    {
        $sorted = $tiers->sortBy([['priority', 'asc'], ['min_amount', 'asc']]);

        foreach ($sorted as $tier) {
            $min = (float) $tier->min_amount;
            $max = $tier->max_amount !== null ? (float) $tier->max_amount : null;

            if ($amountInBase < $min) {
                continue;
            }

            if ($max === null || $amountInBase <= $max) {
                return $tier;
            }
        }

        return $sorted->last();
    }
}
