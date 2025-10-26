<?php

namespace App\Jobs;

use App\Models\Admin\ExchangeRate;
use App\Models\Admin\PaymentGatewayCurrency;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class SyncExchangeRates implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<string, float>  $rates
     */
    public function __construct(protected array $rates, protected ?string $baseCurrency = null)
    {
    }

    public function handle(DatabaseManager $database): void
    {
        $config = config('exchange');
        $store = $config['cache_store'] ?? null;
        $store = $store ?: config('cache.default');
        $ttl = $config['cache_ttl'] ?? 3600;

        Cache::store($store)->put('exchange:rates:all', $this->rates, $ttl);

        $database->transaction(function () {
            $this->updateExchangeRates();
            $this->updateGatewayCurrencies();
        });
    }

    public function tags(): array
    {
        return ['exchange', 'rates'];
    }

    protected function updateExchangeRates(): void
    {
        if (empty($this->rates)) {
            return;
        }

        $existing = ExchangeRate::whereIn('currency_code', array_keys($this->rates))->get();

        foreach ($existing as $rate) {
            $code = strtoupper($rate->currency_code);
            $value = $this->rates[$code] ?? null;

            if ($value === null) {
                continue;
            }

            $rate->update(['rate' => $value]);
        }
    }

    protected function updateGatewayCurrencies(): void
    {
        if (empty($this->rates)) {
            return;
        }

        $currencies = PaymentGatewayCurrency::whereIn('currency_code', array_keys($this->rates))
            ->with('gateway')
            ->get();

        foreach ($currencies as $currency) {
            $code = strtoupper($currency->currency_code);
            $value = $this->rates[$code] ?? null;

            if ($value === null || ($currency->gateway && (int) $currency->gateway->status !== 1)) {
                continue;
            }

            $currency->update(['rate' => $value]);
        }
    }
}
