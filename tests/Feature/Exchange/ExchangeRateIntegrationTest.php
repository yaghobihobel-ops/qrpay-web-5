<?php

namespace Tests\Feature\Exchange;

use App\Jobs\SyncExchangeRates;
use App\Models\Admin\ExchangeRate;
use App\Models\Admin\PaymentGatewayCurrency;
use App\Services\Exchange\ExchangeRateManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * @group exchange
 */
class ExchangeRateIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('exchange.cache_store', 'array');
        Config::set('exchange.cache_ttl', 600);
    }

    public function test_manager_fetches_rates_with_fallback(): void
    {
        Config::set('exchange.fallback_order', ['nima', 'pboc']);
        Config::set('exchange.providers.nima.base_url', 'https://nima.test');
        Config::set('exchange.providers.nima.response_path', 'rates');
        Config::set('exchange.providers.pboc.base_url', 'https://pboc.test');
        Config::set('exchange.providers.pboc.response_path', 'rates');

        Http::fake([
            'https://nima.test*' => Http::response([], 500),
            'https://pboc.test*' => Http::response(['rates' => ['USD' => 42000, 'EUR' => 45000]], 200),
        ]);

        $manager = app(ExchangeRateManager::class);
        $rates = $manager->fetchRates(['USD', 'EUR']);

        $this->assertSame(42000.0, $rates['USD']);
        $this->assertSame(45000.0, $rates['EUR']);
    }

    public function test_exchange_update_command_dispatches_job(): void
    {
        Config::set('exchange.fallback_order', ['nima']);
        Config::set('exchange.providers.nima.base_url', 'https://nima.test');
        Config::set('exchange.providers.nima.response_path', 'rates');

        Http::fake([
            'https://nima.test*' => Http::response(['rates' => ['USD' => 50000]], 200),
        ]);

        Queue::fake();

        $this->artisan('exchange:update --symbols=USD')
            ->expectsOutput('Exchange rates queued for update: USD')
            ->assertExitCode(0);

        Queue::assertPushed(SyncExchangeRates::class);
    }

    public function test_sync_exchange_rates_job_updates_database_and_cache(): void
    {
        ExchangeRate::create([
            'name' => 'US Dollar',
            'mobile_code' => '1',
            'currency_name' => 'US Dollar',
            'currency_code' => 'USD',
            'currency_symbol' => '$',
            'rate' => 1,
            'status' => 1,
        ]);

        PaymentGatewayCurrency::create([
            'payment_gateway_id' => 1,
            'name' => 'USD Gateway',
            'alias' => 'usd-gateway',
            'currency_code' => 'USD',
            'currency_symbol' => '$',
            'min_limit' => 0,
            'max_limit' => 0,
            'percent_charge' => 0,
            'fixed_charge' => 0,
            'rate' => 1,
        ]);

        SyncExchangeRates::dispatchSync(['USD' => 49500.0], 'USD');

        $this->assertSame(49500.0, ExchangeRate::firstWhere('currency_code', 'USD')->rate);

        $cacheRates = Cache::store('array')->get('exchange:rates:all');
        $this->assertIsArray($cacheRates);
        $this->assertSame(49500.0, $cacheRates['USD']);
    }
}
