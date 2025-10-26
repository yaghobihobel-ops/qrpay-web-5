<?php

namespace Tests\Unit;

use App\Notifications\Security\ExcessiveLoginAttemptsNotification;
use App\Traits\Security\LogsSecurityEvents;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Tests\TestCase;

class LogsSecurityEventsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_log_security_info_writes_structured_log(): void
    {
        $request = Request::create('/test', 'GET', [], [], [], [
            'REMOTE_ADDR' => '192.0.2.1',
            'HTTP_USER_AGENT' => 'phpunit',
        ]);

        app()->instance('request', $request);

        $logger = new class {
            use LogsSecurityEvents;

            public function record(string $event, array $context = []): void
            {
                $this->logSecurityInfo($event, $context);
            }
        };

        Log::shouldReceive('channel')->with('security')->andReturnSelf();
        Log::shouldReceive('info')->once()->with('test_event', Mockery::on(function (array $payload): bool {
            return $payload['event'] === 'test_event'
                && $payload['ip'] === '192.0.2.1'
                && $payload['user_agent'] === 'phpunit'
                && $payload['extra'] === 'value'
                && isset($payload['timestamp']);
        }));

        $logger->record('test_event', ['extra' => 'value']);
    }

    public function test_notify_login_threshold_exceeded_logs_and_notifies(): void
    {
        config([
            'security.login_attempts.threshold' => 2,
            'security.login_attempts.mail' => 'security@example.com',
            'security.login_attempts.slack_webhook' => null,
        ]);

        $request = Request::create('/login', 'POST', [], [], [], [
            'REMOTE_ADDR' => '198.51.100.10',
        ]);

        $logger = new class {
            use LogsSecurityEvents;

            public function trigger(Request $request, string $identifier, int $attempts, array $context = []): void
            {
                $this->notifyLoginThresholdExceeded($request, $identifier, $attempts, $context);
            }
        };

        Log::shouldReceive('channel')->with('security')->andReturnSelf();
        Log::shouldReceive('alert')->once()->with('login_threshold_exceeded', Mockery::on(function (array $payload): bool {
            return $payload['identifier'] === 'identifier@example.com'
                && $payload['attempts'] === 2
                && $payload['context'] === 'unit_test';
        }));

        $logger->trigger($request, 'identifier@example.com', 2, ['context' => 'unit_test']);

        Notification::assertSentOnDemand(ExcessiveLoginAttemptsNotification::class, function ($notification, array $channels, $notifiable) {
            return in_array('mail', $channels, true);
        });
    }
}
