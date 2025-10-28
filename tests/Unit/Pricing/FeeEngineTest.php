<?php

namespace Tests\Unit\Pricing;

use App\Models\FeeTier;
use App\Models\PricingRule;
use App\Services\Pricing\Exceptions\PricingRuleNotFoundException;
use App\Services\Pricing\ExchangeRateResolver;
use App\Services\Pricing\FeeEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class FeeEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_applies_fee_tiers_with_currency_conversion(): void
    {
        $rule = PricingRule::factory()->create([
            'name' => 'IRR make payment',
            'provider' => 'internal-ledger',
            'currency' => 'IRR',
            'transaction_type' => 'make-payment',
            'user_level' => 'standard',
            'base_currency' => 'USD',
            'spread_bps' => 50,
        ]);

        FeeTier::factory()->create([
            'pricing_rule_id' => $rule->id,
            'min_amount' => 0,
            'max_amount' => null,
            'percent_fee' => 1.5,
            'fixed_fee' => 2,
            'priority' => 1,
        ]);

        $resolver = Mockery::mock(ExchangeRateResolver::class);
        $resolver->shouldReceive('getRate')
            ->with('IRR', 'USD')
            ->andReturn(0.000024);

        $engine = new FeeEngine($resolver);

        $quote = $engine->quote('IRR', 'internal-ledger', 'make-payment', 'standard', 1_000_000);

        $this->assertSame('IRR', $quote->getTransactionCurrency());
        $this->assertEquals(1_000_000.0, $quote->getAmount());
        $this->assertEqualsWithDelta(98_825.0, $quote->getFeeAmount(), 0.5);
        $this->assertEquals('percentage', $quote->getFeeType());
        $this->assertEqualsWithDelta(83_333.33, $quote->getMeta('percent_component'), 1);
        $this->assertEqualsWithDelta(15_491.67, $quote->getMeta('fixed_component'), 1);
        $this->assertEqualsWithDelta(0.00002412, $quote->getExchangeRate(), 1.0E-9);
    }

    public function test_it_switches_fee_based_on_user_level(): void
    {
        $standardRule = PricingRule::factory()->create([
            'name' => 'Standard make payment',
            'provider' => 'internal-ledger',
            'currency' => 'USD',
            'transaction_type' => 'make-payment',
            'user_level' => 'standard',
            'base_currency' => 'USD',
        ]);

        $vipRule = PricingRule::factory()->create([
            'name' => 'VIP make payment',
            'provider' => 'internal-ledger',
            'currency' => 'USD',
            'transaction_type' => 'make-payment',
            'user_level' => 'verified',
            'base_currency' => 'USD',
        ]);

        FeeTier::factory()->create([
            'pricing_rule_id' => $standardRule->id,
            'min_amount' => 0,
            'max_amount' => null,
            'percent_fee' => 2,
            'fixed_fee' => 1,
            'priority' => 1,
        ]);

        FeeTier::factory()->create([
            'pricing_rule_id' => $vipRule->id,
            'min_amount' => 0,
            'max_amount' => null,
            'percent_fee' => 1,
            'fixed_fee' => 0.5,
            'priority' => 1,
        ]);

        $resolver = Mockery::mock(ExchangeRateResolver::class);
        $resolver->shouldReceive('getRate')->andReturn(1.0);

        $engine = new FeeEngine($resolver);

        $standardQuote = $engine->quote('USD', 'internal-ledger', 'make-payment', 'standard', 1000);
        $vipQuote = $engine->quote('USD', 'internal-ledger', 'make-payment', 'verified', 1000);

        $this->assertEqualsWithDelta(21.0, $standardQuote->getFeeAmount(), 0.01);
        $this->assertEqualsWithDelta(11.5, $vipQuote->getFeeAmount(), 0.01);
        $this->assertTrue($vipQuote->getFeeAmount() < $standardQuote->getFeeAmount());
    }

    public function test_it_throws_when_no_rule_matches(): void
    {
        $this->expectException(PricingRuleNotFoundException::class);

        $resolver = Mockery::mock(ExchangeRateResolver::class);
        $resolver->shouldReceive('getRate')->never();

        $engine = new FeeEngine($resolver);
        $engine->quote('USD', 'internal-ledger', 'make-payment', 'standard', 100);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
