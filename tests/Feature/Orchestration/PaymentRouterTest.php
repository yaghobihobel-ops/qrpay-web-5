<?php

namespace Tests\Feature\Orchestration;

use App\Models\PaymentRoute;
use App\Models\User;
use App\Services\Orchestration\Exceptions\NoAvailablePaymentRouteException;
use App\Services\Orchestration\PaymentRouter;
use App\Services\Orchestration\Providers\AlipayAdapter;
use App\Services\Orchestration\Providers\BluBankAdapter;
use App\Services\Orchestration\Providers\GenericPspAdapter;
use App\Services\Orchestration\Providers\YoomoneaAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'sla_thresholds' => null,
        ]);

        PaymentRoute::create([
            'provider' => 'BluBank',
            'currency' => 'USD',
            'destination_country' => 'US',
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

        $router->selectRoute($user, 'EUR', 100, 'FR');
    }
}
