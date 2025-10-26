<?php

namespace Tests\Unit\Pricing;

use App\Services\Pricing\CurrencyRateService;
use App\Services\Pricing\Exceptions\CouldNotResolveExchangeRateException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CurrencyRateServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('cache.default', 'array');
        Cache::store('array')->clear();
        Config::set('pricing.rate_provider', [
            'base_url' => 'https://example.test/latest',
            'timeout' => 2,
        ]);
        Config::set('pricing.cache_ttl', 1);
        Config::set('pricing.scenario_rates', []);
    }

    public function test_it_fetches_rate_from_http_provider(): void
    {
        Http::fake([
            'https://example.test/*' => Http::response([
                'rates' => [
                    'EUR' => 0.92,
                ],
            ], 200),
        ]);

        $service = new CurrencyRateService(app('config'), Cache::store('array'));

        $rate = $service->getRate('USD', 'EUR');

        $this->assertSame(0.92, $rate);
    }

    public function test_it_uses_scenario_rate_when_available(): void
    {
        Config::set('pricing.scenario_rates', [
            'USD' => ['IRR' => 42000],
        ]);

        $service = new CurrencyRateService(app('config'), Cache::store('array'));

        $rate = $service->getRate('USD', 'IRR');

        $this->assertSame(42000.0, $rate);
    }

    public function test_it_returns_one_when_currency_matches(): void
    {
        $service = new CurrencyRateService(app('config'), Cache::store('array'));

        $this->assertSame(1.0, $service->getRate('USD', 'USD'));
    }

    public function test_it_throws_exception_when_provider_fails(): void
    {
        Http::fake([
            'https://example.test/*' => Http::response(null, 500),
        ]);

        $service = new CurrencyRateService(app('config'), Cache::store('array'));

        $this->expectException(CouldNotResolveExchangeRateException::class);

        $service->getRate('USD', 'CAD');
    }

    public function test_it_converts_amount_using_resolved_rate(): void
    {
        Http::fake([
            'https://example.test/*' => Http::response([
                'rates' => [
                    'JPY' => 110.55,
                ],
            ], 200),
        ]);

        $service = new CurrencyRateService(app('config'), Cache::store());

        $converted = $service->convert(10, 'USD', 'JPY');

        $this->assertEquals(1105.5, $converted);
    }
}
