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
        $this->seedBasicRoutes();

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
        $this->assertEquals(0.995, $decision['sla']['score']);
        $this->assertEquals(0.997, $decision['sla']['kpi']['success_rate']);
    }

    public function test_it_provides_failover_when_primary_provider_is_unavailable(): void
    {
        $this->seedBasicRoutes();

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

    public function test_it_respects_route_thresholds(): void
    {
        PaymentRoute::create([
            'provider' => 'Alipay',
            'currency' => 'USD',
            'destination_country' => 'US',
            'priority' => 1,
            'fee' => 0.0110,
            'max_amount' => 1200,
            'sla_thresholds' => [
                'sla' => ['latency_ms' => 200],
                'kpi' => ['success_rate' => 0.99],
            ],
            'is_active' => true,
        ]);

        PaymentRoute::create([
            'provider' => 'BluBank',
            'currency' => 'USD',
            'destination_country' => 'US',
            'priority' => 2,
            'fee' => 0.0130,
            'max_amount' => 2000,
            'sla_thresholds' => [
                'sla' => ['latency_ms' => 320],
                'kpi' => ['success_rate' => 0.95],
            ],
            'is_active' => true,
        ]);

        $alipay = new AlipayAdapter();
        $alipay->setAvailability(true);

        $router = new PaymentRouter([
            $alipay,
            new BluBankAdapter(),
            new YoomoneaAdapter(),
        ]);

        $result = $router->selectBestRoute([
            'amount' => 600,
            'currency' => 'USD',
            'destination_country' => 'US',
            'sla' => [
                'max_latency_ms' => 500,
                'min_success_rate' => 0.90,
            ],
        ]);

        $this->assertNotNull($result);
        $this->assertSame('BluBank', $result['provider']);
    }

    protected function seedBasicRoutes(): void
    {
        PaymentRoute::create([
            'provider' => 'Alipay',
            'currency' => 'USD',
            'destination_country' => 'CN',
            'priority' => 1,
            'fee' => 0.0120,
            'max_amount' => 1500,
            'sla_thresholds' => null,
            'is_active' => true,
        ]);

        PaymentRoute::create([
            'provider' => 'BluBank',
            'currency' => 'USD',
            'destination_country' => 'CN',
            'priority' => 2,
            'fee' => 0.0130,
            'max_amount' => 1500,
            'sla_thresholds' => null,
            'is_active' => true,
        ]);
    }
}
