<?php

namespace App\Services\Pricing;

use App\Models\Pricing\PricingRule;
use App\Services\Pricing\Exceptions\PricingRuleNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class FeeEngine
{
    public function __construct(protected CurrencyRateService $currencyRateService)
    {
    }

    /**
     * @param array{experiment?: string|null, variant?: string|null, metadata?: array} $context
     */
    public function quote(
        string $currency,
        string $provider,
        string $transactionType,
        string $userLevel,
        float $amount,
        array $context = []
    ): FeeQuote {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be greater than zero.');
        }

        $currency = strtoupper($currency);
        $provider = strtolower($provider);
        $transactionType = strtolower($transactionType);
        $userLevel = strtolower($userLevel);
        $experiment = isset($context['experiment']) ? strtolower((string) $context['experiment']) : null;
        $variant = isset($context['variant']) ? strtolower((string) $context['variant']) : null;

        $rules = PricingRule::query()
            ->with('feeTiers')
            ->active()
            ->whereRaw('LOWER(provider) = ?', [$provider])
            ->whereRaw('LOWER(transaction_type) = ?', [$transactionType])
            ->whereRaw('LOWER(currency) = ?', [$currency])
            ->orderBy('priority')
            ->orderByDesc('id')
            ->get()
            ->filter(function (PricingRule $rule) use ($userLevel, $experiment, $variant, $amount) {
                if (! $rule->isActiveAt(Carbon::now())) {
                    return false;
                }

                if (! $rule->matchesUserLevel($userLevel)) {
                    return false;
                }

                if (! $rule->matchesExperiment($experiment, $variant)) {
                    return false;
                }

                if (! $rule->matchesAmount($amount)) {
                    return false;
                }

                return true;
            })
            ->sortBy(function (PricingRule $rule) use ($variant) {
                $priority = $rule->priority ?? 100;
                $variantWeight = 1;
                if ($variant !== null && strtolower($rule->variant ?? '') === $variant) {
                    $variantWeight = 0;
                }

                return [$priority, $variantWeight, $rule->id * -1];
            })
            ->values();

        /** @var PricingRule|null $rule */
        $rule = $rules->first();

        if (! $rule) {
            throw new PricingRuleNotFoundException('No pricing rule matched the given parameters.');
        }

        $tier = $rule->resolveTier($amount);

        $feeType = $tier?->fee_type ?? $rule->fee_type;
        $feeAmount = (float) ($tier?->fee_amount ?? $rule->fee_amount);
        $feeCurrency = strtoupper($tier?->fee_currency ?? $rule->fee_currency ?? $currency);

        $calculatedFee = $this->calculateFee($feeType, $amount, $feeAmount);

        $exchangeRate = $feeCurrency === $currency
            ? 1.0
            : $this->currencyRateService->getRate($feeCurrency, $currency);

        $metadata = Arr::only($rule->metadata ?? [], ['description', 'notes']);
        $metadata['rate_value'] = $feeAmount;

        if ($tier) {
            $metadata['tier_priority'] = $tier->priority;
        }

        $quote = new FeeQuote(
            amount: $amount,
            transactionCurrency: $currency,
            feeAmount: $calculatedFee,
            feeCurrency: $feeCurrency,
            exchangeRate: $exchangeRate,
            feeType: $feeType,
            ruleId: $rule->id,
            tierId: $tier?->id,
            meta: $metadata
        );

        foreach (($context['metadata'] ?? []) as $key => $value) {
            if (\is_string($key)) {
                $quote = $quote->withMeta($key, $value);
            }
        }

        return $quote
            ->withMeta('rule_name', $rule->name)
            ->withMeta('provider', $provider)
            ->withMeta('transaction_type', $transactionType)
            ->withMeta('user_level', $userLevel)
            ->withMeta('experiment', $experiment)
            ->withMeta('variant', $variant)
            ->withMeta('calculation_source', $tier ? 'tier' : 'base');
    }

    protected function calculateFee(string $feeType, float $amount, float $value): float
    {
        $feeType = strtolower($feeType);

        return match ($feeType) {
            'percentage', 'percent' => ($amount * $value) / 100,
            'bps' => ($amount * $value) / 10000,
            default => $value,
        };
    }
}
