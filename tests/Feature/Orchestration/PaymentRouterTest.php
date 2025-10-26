<?php

namespace Tests\Feature\Orchestration;

use App\Models\FeeTier;
use App\Models\PaymentRoute;
use App\Models\PricingRule;
use App\Models\User;
use App\Services\Orchestration\Exceptions\NoAvailablePaymentRouteException;
use App\Services\Orchestration\PaymentRouter;
use App\Services\Orchestration\Providers\AlipayAdapter;
use App\Services\Orchestration\Providers\BluBankAdapter;
use App\Services\Orchestration\Providers\GenericPspAdapter;
use App\Services\Orchestration\Providers\YoomoneaAdapter;
use App\Services\Pricing\DTO\FeeQuote;
use App\Services\Pricing\FeeEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PaymentRouterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_selects_highest_priority_route_matching_sla_policies(): void
    {
        $user = User::factory()->create();

        PaymentRoute::create([
            'provider' => 'Alipay',
            'currency' => 'CNY',
            'destination_country' => 'CN',
            'priority' => 1,
            'fee' => 0.0100,
            'max_amount' => 1000,
        ]);

        PaymentRoute::create([
            'provider' => 'BluBank',
            'currency' => 'CNY',
            'destination_country' => 'CN',
            'priority' => 2,
            'fee' => 0.0080,
            'max_amount' => 2000,
        ]);

        $router = new PaymentRouter([
            new AlipayAdapter(),
            new BluBankAdapter(),
            new YoomoneaAdapter(),
        ]);

        $slaPolicies = [
            function ($provider, $route, array $sla, array $kpi) {
                return $sla['uptime'] >= 99.7 && $kpi['success_rate'] >= 0.97;
            },
        ];

        $result = $router->selectRoute($user, 'cny', 500, 'cn', $slaPolicies);

        $this->assertSame('Alipay', $result->getProvider()->getName());
        $this->assertSame('Alipay', $result->getRoute()->provider);
        $this->assertSame('CNY', $result->getRoute()->currency);
    }

    public function test_it_fails_over_when_primary_provider_is_unavailable(): void
    {
        $user = User::factory()->create();

        PaymentRoute::create([
            'provider' => 'Alipay',
            'currency' => 'USD',
            'destination_country' => 'US',
            'priority' => 1,
            'fee' => 0.0120,
            'max_amount' => 1500,
        ]);

        PaymentRoute::create([
            'provider' => 'BluBank',
            'currency' => 'USD',
            'destination_country' => 'US',
            'priority' => 2,
            'fee' => 0.0130,
            'max_amount' => 1500,
        ]);

        $alipay = new AlipayAdapter();
        $alipay->setAvailability(false);

        $router = new PaymentRouter([
            $alipay,
            new BluBankAdapter(),
            new YoomoneaAdapter(),
            new GenericPspAdapter('ContingencyPay', ['uptime' => 99.0, 'latency' => 260], ['success_rate' => 0.95]),
        ]);

        $result = $router->selectRoute($user, 'USD', 800, 'US');

        $this->assertSame('BluBank', $result->getProvider()->getName());
    }

    public function test_it_throws_exception_when_no_route_matches(): void
    {
        $this->expectException(NoAvailablePaymentRouteException::class);

        $user = User::factory()->create();
        $router = new PaymentRouter([
            new AlipayAdapter(),
            new BluBankAdapter(),
            new YoomoneaAdapter(),
        ]);

        $router->selectRoute($user, 'EUR', 100, 'FR');
    }

    public function test_it_attaches_fee_quote_when_fee_engine_available(): void
    {
        $user = User::factory()->create();

        PaymentRoute::create([
            'provider' => 'Alipay',
            'currency' => 'USD',
            'destination_country' => 'US',
            'priority' => 1,
            'fee' => 0.0100,
            'max_amount' => 1000,
        ]);

        $rule = PricingRule::factory()->create([
            'provider' => 'Alipay',
            'currency' => 'USD',
            'transaction_type' => 'make-payment',
            'user_level' => 'standard',
        ]);

        FeeTier::factory()->create([
            'pricing_rule_id' => $rule->id,
            'min_amount' => 0,
            'max_amount' => null,
            'percent_fee' => 1,
            'fixed_fee' => 0.5,
            'priority' => 1,
        ]);

        $feeEngine = Mockery::mock(FeeEngine::class);
        $feeEngine->shouldReceive('quote')->once()->andReturn(
            new FeeQuote('USD', 'Alipay', 'make-payment', 'standard', 100.0, 2.5, 0.5, 2.0, 1.0, 1.0, $rule)
        );

        $router = new PaymentRouter([
            new AlipayAdapter(),
        ], $feeEngine);

        $result = $router->selectRoute($user, 'USD', 100, 'US', [], [
            'transaction_type' => 'make-payment',
            'user_level' => 'standard',
        ]);

        $this->assertNotNull($result->getFeeQuote());
        $this->assertSame('Alipay', $result->getFeeQuote()->provider);
        $this->assertSame(2.5, $result->getFeeQuote()->totalFee);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
