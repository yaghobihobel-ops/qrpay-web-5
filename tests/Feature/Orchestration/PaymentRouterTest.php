<?php

namespace Tests\Feature\Orchestration;

use App\Models\PaymentRoute;
use App\Services\Orchestration\Adapters\AlipayAdapter;
use App\Services\Orchestration\Adapters\BluBankAdapter;
use App\Services\Orchestration\Adapters\GenericProviderAdapter;
use App\Services\Orchestration\Adapters\YoomoneaAdapter;
use App\Services\Orchestration\PaymentRouter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentRouterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_selects_best_route_based_on_priority_and_sla(): void
    {
        $this->seedPaymentRoutes();

        $router = new PaymentRouter([
            new AlipayAdapter(),
            new BluBankAdapter(),
            new YoomoneaAdapter(),
            new GenericProviderAdapter('GlobePay'),
        ]);

        $decision = $router->selectBestRoute([
            'amount' => 500,
            'currency' => 'USD',
            'destination_country' => 'CN',
            'sla' => [
                'min_sla_score' => 0.97,
                'max_latency_ms' => 300,
                'min_success_rate' => 0.98,
            ],
        ]);

        $this->assertNotNull($decision);
        $this->assertSame('Alipay', $decision['provider']);
        $this->assertSame(1, $decision['priority']);
        $this->assertSame(0.995, $decision['sla']['score']);
        $this->assertEquals(0.997, $decision['sla']['kpi']['success_rate']);
    }

    public function test_it_provides_failover_when_primary_provider_is_down(): void
    {
        $this->seedPaymentRoutes();

        $router = new PaymentRouter([
            new AlipayAdapter(),
            new BluBankAdapter(),
            new YoomoneaAdapter(),
        ]);

        $context = [
            'amount' => 500,
            'currency' => 'USD',
            'destination_country' => 'CN',
            'sla' => [
                'min_sla_score' => 0.95,
                'max_latency_ms' => 400,
            ],
        ];

        $primary = $router->selectBestRoute($context);
        $this->assertNotNull($primary);
        $this->assertSame('Alipay', $primary['provider']);

        $failover = $router->getFailoverRoute($context, 'Alipay');
        $this->assertNotNull($failover);
        $this->assertSame('BluBank', $failover['provider']);
        $this->assertSame(2, $failover['priority']);

        $routerWithOutage = new PaymentRouter([
            new AlipayAdapter(false),
            new BluBankAdapter(),
            new YoomoneaAdapter(),
        ]);

        $outageDecision = $routerWithOutage->selectBestRoute($context);
        $this->assertNotNull($outageDecision);
        $this->assertSame('BluBank', $outageDecision['provider']);
    }

    protected function seedPaymentRoutes(): void
    {
        PaymentRoute::create([
            'provider' => 'Alipay',
            'currency' => 'USD',
            'priority' => 1,
            'fee' => 0.0120,
            'max_amount' => 1500,
            'sla_thresholds' => null,
        ]);

        PaymentRoute::create([
            'provider' => 'BluBank',
            'currency' => 'USD',
            'priority' => 2,
            'fee' => 0.0130,
            'max_amount' => 1500,
            'sla_thresholds' => null,
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

    public function test_it_switches_to_backup_provider_when_primary_breaches_sla_threshold(): void
    {
        $user = User::factory()->create();

        PaymentRoute::create([
            'provider' => 'Alipay',
            'currency' => 'USD',
            'destination_country' => 'US',
            'priority' => 1,
            'fee' => 0.0110,
            'max_amount' => 1200,
            'sla_thresholds' => [
                'sla' => ['uptime' => 99.9, 'latency' => 200],
                'kpi' => ['success_rate' => 0.99],
            ],
        ]);

        PaymentRoute::create([
            'provider' => 'BluBank',
            'currency' => 'USD',
            'destination_country' => 'US',
            'priority' => 2,
            'fee' => 0.0130,
            'max_amount' => 2000,
            'sla_thresholds' => [
                'sla' => ['uptime' => 99.0, 'latency' => 300],
                'kpi' => ['success_rate' => 0.95],
            ],
        ]);

        $alipay = new AlipayAdapter();
        $alipay->updateSlaProfile(['uptime' => 98.2, 'latency' => 280]);
        $alipay->updateKpiMetrics(['success_rate' => 0.93]);

        $router = new PaymentRouter([
            $alipay,
            new BluBankAdapter(),
            new YoomoneaAdapter(),
        ]);

        $result = $router->selectRoute($user, 'USD', 600, 'US');

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
    }
}
