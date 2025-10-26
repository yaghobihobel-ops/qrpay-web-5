<?php

namespace Tests\Unit;

use App\Exceptions\CircuitBreakerOpenException;
use App\Services\Security\RequestThrottler;
use App\Support\CircuitBreaker;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Tests\TestCase;

class RequestThrottlerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Notification::fake();
    }

    public function test_it_allows_requests_within_limit(): void
    {
        config([
            'api.rate_limits.services.payment.per_user.max_attempts' => 2,
            'api.rate_limits.services.payment.per_user.decay_minutes' => 1,
            'api.rate_limits.services.payment.per_ip.max_attempts' => 2,
            'api.rate_limits.services.payment.per_ip.decay_minutes' => 1,
        ]);

        $throttler = $this->makeThrottler();

        $throttler->ensureWithinLimit(RequestThrottler::SERVICE_PAYMENT, 'user-1', '127.0.0.1');
        $throttler->ensureWithinLimit(RequestThrottler::SERVICE_PAYMENT, 'user-1', '127.0.0.1');

        $this->assertTrue(true);
    }

    public function test_it_throws_and_notifies_when_limit_exceeded(): void
    {
        config([
            'api.rate_limits.services.payment.per_user.max_attempts' => 1,
            'api.rate_limits.services.payment.per_user.decay_minutes' => 1,
            'api.alerts.log_channel' => 'stack',
            'api.alerts.slack_webhook' => 'https://hooks.slack.test/foo',
            'api.alerts.email.recipients' => ['ops@example.com'],
        ]);

        Log::shouldReceive('channel')->with('stack')->andReturnSelf();
        Log::shouldReceive('warning')->once();

        $throttler = $this->makeThrottler();

        $throttler->ensureWithinLimit(RequestThrottler::SERVICE_PAYMENT, 'user-1', '127.0.0.1');

        $this->expectException(TooManyRequestsHttpException::class);

        try {
            $throttler->ensureWithinLimit(RequestThrottler::SERVICE_PAYMENT, 'user-1', '127.0.0.1');
        } finally {
            Notification::assertSentOnDemand(function ($notification, $channels, $notifiable) {
                return in_array('slack', $channels, true) && in_array('mail', $channels, true);
            });
        }
    }

    public function test_it_trips_circuit_on_failures(): void
    {
        config([
            'api.rate_limits.services.payment.per_user.max_attempts' => 5,
            'api.rate_limits.services.payment.per_user.decay_minutes' => 1,
            'api.service_circuits.payment' => 'psp',
            'api.circuit_breaker.default.failure_threshold' => 1,
            'api.circuit_breaker.default.retry_timeout' => 60,
            'api.circuit_breaker.default.decay_seconds' => 60,
            'api.circuit_breaker.psp.failure_threshold' => 1,
            'api.circuit_breaker.psp.retry_timeout' => 60,
            'api.circuit_breaker.psp.decay_seconds' => 60,
        ]);

        $throttler = $this->makeThrottler();

        try {
            $throttler->run(RequestThrottler::SERVICE_PAYMENT, function () {
                throw new \RuntimeException('failure');
            }, 'user-1', '127.0.0.1');
        } catch (\RuntimeException $exception) {
            $this->assertSame('failure', $exception->getMessage());
        }

        $this->expectException(CircuitBreakerOpenException::class);
        $throttler->run(RequestThrottler::SERVICE_PAYMENT, function () {
            return true;
        }, 'user-1', '127.0.0.1');
    }

    protected function makeThrottler(): RequestThrottler
    {
        return new RequestThrottler(
            $this->app->make(RateLimiter::class),
            $this->app->make(CircuitBreaker::class)
        );
    }
}
