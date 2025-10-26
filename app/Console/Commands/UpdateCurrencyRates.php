<?php

namespace App\Console\Commands;

use App\Constants\GlobalConst;
use App\Models\Admin\ExchangeRate;
use App\Models\Admin\PaymentGatewayCurrency;
use App\Models\LiveExchangeRateApiSetting;
use App\Models\ExchangeRateLog;
use App\Services\Exchange\ExchangeRateProviderInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Exception;

class UpdateCurrencyRates extends Command
{
    protected $signature = 'currency:update';
    protected $description = 'Update currency rates using CurrencyLayer API';

    public function __construct(protected ExchangeRateProviderInterface $exchangeRateProvider)
    {
        parent::__construct();
    }

    public function handle()
    {
        try {
            $response = $this->exchangeRateProvider->getLiveExchangeRates();

            ExchangeRateLog::create([
                'provider' => $this->exchangeRateProvider->getIdentifier(),
                'status' => $response['status'],
                'from_cache' => $response['from_cache'] ?? false,
                'message' => $response['message'] ?? null,
                'payload' => $response['data'] ?? [],
            ]);

            if (isset($response) && $response['status'] == false) {
                info($response['message'] ?? "Something went wrong! Please try again.");
                return;
            }

            $api_rates = $response['data'];

            Cache::put(
                config('exchange.cache.latest_rates_key', 'exchange:rates:latest'),
                $api_rates,
                now()->addSeconds((int) config('exchange.cache.ttl', 3600))
            );

            $provider = LiveExchangeRateApiSetting::where('slug', GlobalConst::CURRENCY_LAYER)->first();

            // For Setup Currency Rate Update
            if ($provider->currency_module == 1) {
                $currencies = ExchangeRate::active()->get();
                foreach ($currencies as $currency) {
                    if (array_key_exists($currency->currency_code, $api_rates)) {
                        $currency->rate = $api_rates[$currency->currency_code];
                        $currency->save();
                    }
                }
            }

            // For Gateway Currency Rate Update
            if ($provider->payment_gateway_module == 1) {
                $payment_gateways_currencies = PaymentGatewayCurrency::whereHas('gateway', function ($gateway) {
                    $gateway->where('status', 1);
                })->get();
                foreach ($payment_gateways_currencies as $currency) {
                    if (array_key_exists($currency->currency_code, $api_rates)) {
                        $currency->rate = $api_rates[$currency->currency_code];
                        $currency->save();
                    }
                }
            }

            info('Currency Rate Updated Successfully by Currency Layer.');

        } catch (Exception $e) {
            info($e->getMessage()??"Something went wrong! Please try again.");
        }
    }
}
