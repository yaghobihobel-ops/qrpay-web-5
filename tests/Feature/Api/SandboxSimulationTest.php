<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SandboxSimulationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $path = config('app_env.fakes.repository_path');
        if ($path && file_exists($path)) {
            @unlink($path);
        }
    }

    /** @test */
    public function it_lists_sandbox_payments()
    {
        $response = $this->getJson('/api/sandbox/payments');

        $response->assertOk()
            ->assertJsonStructure(['data' => [['reference', 'status', 'amount', 'currency']]])
            ->assertJsonFragment(['status' => 'success']);
    }

    /** @test */
    public function it_creates_a_sandbox_payment_record()
    {
        $payload = [
            'amount' => 99.50,
            'currency' => 'USD',
            'channel' => 'card',
            'reference' => 'TEST-REF-1234',
        ];

        $response = $this->postJson('/api/sandbox/payments', $payload);

        $response->assertCreated()
            ->assertJsonFragment(['reference' => 'TEST-REF-1234', 'status' => 'success']);
    }

    /** @test */
    public function it_returns_sandbox_exchange_rates()
    {
        $response = $this->getJson('/api/sandbox/exchange-rates');

        $response->assertOk()
            ->assertJsonFragment(['status' => true])
            ->assertJsonStructure(['data']);
    }

    /** @test */
    public function it_returns_sandbox_billers()
    {
        $response = $this->getJson('/api/sandbox/billers');

        $response->assertOk()
            ->assertJsonStructure(['content' => [['id', 'name']]]);
    }

    /** @test */
    public function it_returns_airtime_countries()
    {
        $response = $this->getJson('/api/sandbox/airtime/countries');

        $response->assertOk()
            ->assertJsonStructure(['data' => [['iso', 'name']]]);
    }

    /** @test */
    public function it_returns_gift_card_products()
    {
        $response = $this->getJson('/api/sandbox/gift-cards');

        $response->assertOk()
            ->assertJsonStructure(['content' => [['productId', 'productName']]]);
    }
}
