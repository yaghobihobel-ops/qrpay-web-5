<?php

namespace Tests\Feature;

use App\Services\Monitoring\HealthCheckService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HealthEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_provider_health_summary(): void
    {
        Http::fake([
            'https://alpha.example/health' => Http::response(['status' => 'ok'], 200),
            'https://beta.example/health' => Http::response('Service unavailable', 503),
        ]);

        config()->set('monitoring.providers', [
            [
                'name' => 'Alpha',
                'slug' => 'alpha',
                'url' => 'https://alpha.example/health',
            ],
            [
                'name' => 'Beta',
                'slug' => 'beta',
                'url' => 'https://beta.example/health',
            ],
        ]);

        $response = $this->getJson('/api/health');

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'checked_at',
                'services' => [
                    ['slug', 'name', 'status', 'latency_ms', 'checked_at'],
                ],
                'history',
            ]);

        $this->assertSame(HealthCheckService::STATUS_DOWN, $response->json('status'));
        $this->assertDatabaseCount('health_checks', 2);
        $this->assertDatabaseHas('health_checks', [
            'provider' => 'beta',
            'status' => HealthCheckService::STATUS_DOWN,
        ]);
    }
}
