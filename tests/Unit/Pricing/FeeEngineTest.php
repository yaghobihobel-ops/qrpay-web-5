<?php

namespace Tests\Unit\Pricing;

use App\Models\Pricing\PricingRule;
use App\Services\Pricing\CurrencyRateService;
use App\Services\Pricing\Exceptions\PricingRuleNotFoundException;
use App\Services\Pricing\FeeEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class FeeEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('cache.default', 'array');
        Cache::store('array')->clear();
        Config::set('pricing.scenario_rates', [
            'USD' => ['EUR' => 0.92],
            'EUR' => ['USD' => 1.087],
        ]);
    }

    protected function engine(): FeeEngine
    {
        return new FeeEngine(new CurrencyRateService(app('config'), Cache::store('array')));
    }

    public function test_it_calculates_flat_fee(): void
    {
        PricingRule::create([
            'name' => 'Flat fee',
            'currency' => 'USD',
            'provider' => 'provider-a',
            'transaction_type' => 'withdraw',
            'fee_type' => 'flat',
            'fee_amount' => 5,
            'fee_currency' => 'USD',
            'priority' => 10,
            'active' => true,
        ]);

        $quote = $this->engine()->quote('USD', 'provider-a', 'withdraw', 'standard', 100);

        $this->assertSame(5.0, $quote->getConvertedFee());
        $this->assertSame('flat', $quote->getFeeType());
    }

    public function test_it_applies_percentage_tier(): void
    {
        $rule = PricingRule::create([
            'name' => 'Tiered percentage',
            'currency' => 'USD',
            'provider' => 'provider-b',
            'transaction_type' => 'withdraw',
            'fee_type' => 'percentage',
            'fee_amount' => 1.5,
            'fee_currency' => 'USD',
            'priority' => 5,
            'active' => true,
        ]);

        $rule->feeTiers()->createMany([
            [
                'min_amount' => 0,
                'max_amount' => 999.99,
                'fee_type' => 'percentage',
                'fee_amount' => 1.0,
                'priority' => 10,
            ],
            [
                'min_amount' => 1000,
                'max_amount' => null,
                'fee_type' => 'percentage',
                'fee_amount' => 0.8,
                'priority' => 20,
            ],
        ]);

        $quote = $this->engine()->quote('USD', 'provider-b', 'withdraw', 'standard', 1500);

        $this->assertEqualsWithDelta(12.0, $quote->getConvertedFee(), 0.0001);
        $this->assertSame('percentage', $quote->getFeeType());
        $this->assertSame($rule->feeTiers()->where('fee_amount', 0.8)->first()->id, $quote->getTierId());
    }

    public function test_it_converts_fee_currency(): void
    {
        PricingRule::create([
            'name' => 'Cross currency',
            'currency' => 'EUR',
            'provider' => 'provider-c',
            'transaction_type' => 'withdraw',
            'fee_type' => 'flat',
            'fee_amount' => 5,
            'fee_currency' => 'USD',
            'priority' => 1,
            'active' => true,
        ]);

        $quote = $this->engine()->quote('EUR', 'provider-c', 'withdraw', 'standard', 200);

        $this->assertEqualsWithDelta(4.6, $quote->getConvertedFee(), 0.0001);
        $this->assertSame('flat', $quote->getFeeType());
    }

    public function test_it_throws_when_rule_not_found(): void
    {
        $this->expectException(PricingRuleNotFoundException::class);

        $this->engine()->quote('USD', 'missing-provider', 'withdraw', 'standard', 50);
    }

    public function test_it_selects_variant_when_experiment_context_provided(): void
    {
        PricingRule::create([
            'name' => 'Control rule',
            'currency' => 'USD',
            'provider' => 'provider-d',
            'transaction_type' => 'withdraw',
            'fee_type' => 'flat',
            'fee_amount' => 10,
            'fee_currency' => 'USD',
            'priority' => 50,
            'active' => true,
            'experiment' => 'spring-test',
            'variant' => 'control',
        ]);

        PricingRule::create([
            'name' => 'Variant B',
            'currency' => 'USD',
            'provider' => 'provider-d',
            'transaction_type' => 'withdraw',
            'fee_type' => 'flat',
            'fee_amount' => 7,
            'fee_currency' => 'USD',
            'priority' => 60,
            'active' => true,
            'experiment' => 'spring-test',
            'variant' => 'b',
        ]);

        $quote = $this->engine()->quote('USD', 'provider-d', 'withdraw', 'standard', 100, [
            'experiment' => 'spring-test',
            'variant' => 'b',
        ]);

        $this->assertSame(7.0, $quote->getConvertedFee());
        $this->assertSame('Variant B', $quote->getMeta('rule_name'));
        $this->assertSame('b', $quote->getMeta('variant'));
    }
}
