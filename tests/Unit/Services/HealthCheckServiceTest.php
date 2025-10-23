<?php

namespace Tests\Unit\Services;

use App\Notifications\HealthCheckAlert;
use App\Services\Monitoring\HealthCheckService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class HealthCheckServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_records_health_checks_and_history(): void
    {
        Http::fake([
            'https://alpha.example/health' => Http::sequence()
                ->push(['status' => 'ok'], 200)
                ->push(['status' => 'ok'], 200),
        ]);

        config()->set('monitoring.providers', [
            [
                'name' => 'Alpha',
                'slug' => 'alpha',
                'url' => 'https://alpha.example/health',
                'latency_threshold' => -1,
            ],
        ]);

        $service = app(HealthCheckService::class);

        $firstRun = $service->checkAll();
        $secondRun = $service->checkAll();

        $this->assertCount(1, $firstRun);
        $this->assertCount(1, $secondRun);
        $this->assertDatabaseCount('health_checks', 2);
        $this->assertDatabaseHas('health_checks', [
            'provider' => 'alpha',
        ]);
        $this->assertTrue(in_array($firstRun[0]['status'], [HealthCheckService::STATUS_HEALTHY, HealthCheckService::STATUS_DEGRADED], true));
    }

    public function test_it_dispatches_alerts_for_non_healthy_providers(): void
    {
        Notification::fake();

        Http::fake([
            'https://beta.example/health' => Http::response('Server Error', 500),
            'https://hooks.slack.test/*' => Http::response('ok', 200),
            'https://webhook.test/*' => Http::response('accepted', 200),
        ]);

        config()->set('monitoring.providers', [
            [
                'name' => 'Beta',
                'slug' => 'beta',
                'url' => 'https://beta.example/health',
            ],
        ]);

        config()->set('monitoring.alerts', [
            'mail' => [
                'enabled' => true,
                'recipients' => ['ops@example.com'],
            ],
            'slack' => [
                'enabled' => true,
                'webhook_url' => 'https://hooks.slack.test/123',
            ],
            'webhook' => [
                'enabled' => true,
                'url' => 'https://webhook.test/alert',
                'secret' => 'secret',
            ],
        ]);

        $service = app(HealthCheckService::class);
        $service->checkAll();

        Notification::assertSentOnDemand(HealthCheckAlert::class, function ($notification, $channels, $notifiable) {
            return in_array('mail', $channels, true);
        });

        Http::assertSentCount(3);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://hooks.slack.test/123';
        });
        Http::assertSent(function ($request) {
            return $request->url() === 'https://webhook.test/alert';
        });

        $this->assertDatabaseHas('health_checks', [
            'provider' => 'beta',
            'status' => HealthCheckService::STATUS_DOWN,
        ]);
    }
}
