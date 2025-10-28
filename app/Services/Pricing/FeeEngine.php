<?php

namespace App\Services\Pricing;

use App\Models\FeeTier;
use App\Models\PricingRule;
use App\Services\Pricing\Exceptions\PricingRuleNotFoundException;
use App\Services\Pricing\FeeQuote;
use Illuminate\Support\Collection;

class FeeEngine
{
    public function __construct(
        protected ExchangeRateResolver $exchangeRateResolver
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function quote(
        string $currency,
        string $provider,
        string $transactionType,
        string $userLevel,
        float $amount,
        array $options = []
    ): FeeQuote {
        $currency = strtoupper($currency);
        $provider = strtolower($provider);
        $userLevel = strtolower($userLevel);
        $transactionType = strtolower($transactionType);

        $rule = $this->resolveRule($currency, $provider, $transactionType, $userLevel);

        [$baseRate, $spreadMultiplier] = $this->resolveRate($currency, $rule);
        $baseRate = max($baseRate, 0.00000001);
        $amountInBase = $amount * $baseRate;

        $tier = $this->matchTier($rule->feeTiers, $amountInBase);

        $percentFeeAmount = ($amountInBase * (float) $tier->percent_fee) / 100;
        $fixedFeeAmount = (float) $tier->fixed_fee;
        $totalFeeInBase = $percentFeeAmount + $fixedFeeAmount;

        $convertedPercentFee = ($percentFeeAmount / $baseRate) * $spreadMultiplier;
        $convertedFixedFee = ($fixedFeeAmount / $baseRate) * $spreadMultiplier;
        $totalFee = ($totalFeeInBase / $baseRate) * $spreadMultiplier;

        $feeType = $this->determineFeeType($percentFeeAmount, $fixedFeeAmount);

        $meta = [
            'calculation_source' => 'rule',
            'rule_name' => $rule->name,
            'base_currency' => strtoupper($rule->base_currency ?? $currency),
            'spread_bps' => (float) ($rule->spread_bps ?? 0),
            'spread_multiplier' => $spreadMultiplier,
            'rate_value' => $baseRate,
        ];

        if (! empty($options['metadata']) && is_array($options['metadata'])) {
            $meta = array_merge($meta, $options['metadata']);
        }

        if (! empty($options['experiment'])) {
            $meta['experiment'] = $options['experiment'];
        }

        if (! empty($options['variant'])) {
            $meta['variant'] = $options['variant'];
        }

        return new FeeQuote(
            amount: $amount,
            transactionCurrency: $currency,
            feeAmount: round($totalFee, 8),
            feeCurrency: $currency,
            exchangeRate: round($baseRate * $spreadMultiplier, 8),
            feeType: $feeType,
            ruleId: $rule->getKey(),
            tierId: $tier->getKey(),
            meta: array_merge($meta, [
                'percent_component' => round($convertedPercentFee, 8),
                'fixed_component' => round($convertedFixedFee, 8),
            ])
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
                    ->orWhereRaw('LOWER(provider) = ?', [$provider])
                    ->orWhere('provider', '*');
            })
            ->where(function ($query) use ($transactionType) {
                $query->whereRaw('LOWER(transaction_type) = ?', [$transactionType])
                    ->orWhere('transaction_type', '*');
            })
            ->where(function ($query) use ($userLevel) {
                $query->whereRaw('LOWER(user_level) = ?', [$userLevel])
                    ->orWhere('user_level', '*');
            })
            ->orderByRaw("CASE WHEN LOWER(currency) = ? THEN 0 WHEN currency = '*' THEN 1 ELSE 2 END", [$currency])
            ->orderByRaw("CASE WHEN LOWER(provider) = ? THEN 0 WHEN provider = '*' THEN 1 ELSE 2 END", [$provider])
            ->orderByRaw("CASE WHEN LOWER(user_level) = ? THEN 0 WHEN user_level = '*' THEN 1 ELSE 2 END", [$userLevel])
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
        $spreadMultiplier = 1 + ((float) ($rule->spread_bps ?? 0) / 10000);

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

    protected function determineFeeType(float $percentFeeAmount, float $fixedFeeAmount): string
    {
        $hasPercent = $percentFeeAmount > 0;
        $hasFixed = $fixedFeeAmount > 0;

        return match (true) {
            $hasPercent && $hasFixed => 'hybrid',
            $hasPercent => 'percentage',
            $hasFixed => 'flat',
            default => 'none',
        };
    }
}
