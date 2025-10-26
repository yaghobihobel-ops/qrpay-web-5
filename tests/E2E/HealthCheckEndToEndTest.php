<?php

namespace Tests\E2E;

use App\Models\Monitoring\ServiceHealthCheck;
use App\Notifications\Monitoring\ServiceAlertNotification;
use App\Services\Monitoring\HealthCheckService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class HealthCheckEndToEndTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_health_check_records_and_sends_alerts(): void
    {
        Notification::fake();
        Http::fake([
            config('monitoring.providers.alipay.endpoint') => Http::response(['metrics' => ['error_rate' => 5]], 200),
            '*' => Http::response(status: 200),
        ]);

        config(['monitoring.alerts.emails' => ['ops@example.com']]);
        config(['monitoring.alerts.on_call' => ['oncall@example.com']]);
        config(['monitoring.alerts.slack_webhook' => 'https://hooks.slack.com/services/T000/B000/XXXX']);

        $service = app(HealthCheckService::class);
        $service->run('alipay');

        $this->assertDatabaseHas(ServiceHealthCheck::class, [
            'service_name' => 'alipay',
            'status' => 'degraded',
        ]);

        Notification::assertSentOnDemandTimes(ServiceAlertNotification::class, 3);
    }
}
