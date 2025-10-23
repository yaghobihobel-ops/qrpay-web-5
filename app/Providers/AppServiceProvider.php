<?php

namespace App\Providers;

use App\Constants\ExtensionConst;
use App\Providers\Admin\ExtensionProvider;
use App\Services\Contracts\AirtimeProvider;
use App\Services\Contracts\BillPaymentProvider;
use App\Services\Contracts\ExchangeRateProvider;
use App\Services\Contracts\GiftCardProvider;
use App\Services\Contracts\PaymentProvider;
use App\Services\Fakes\FakeAirtimeProvider;
use App\Services\Fakes\FakeBillPaymentProvider;
use App\Services\Fakes\FakeExchangeProvider;
use App\Services\Fakes\FakeGiftCardProvider;
use App\Services\Fakes\FakePaymentProvider;
use App\Services\Providers\CurrencyLayerExchangeProvider;
use App\Services\Providers\NullPaymentProvider;
use App\Services\Providers\ReloadlyAirtimeProvider;
use App\Services\Providers\ReloadlyBillPaymentProvider;
use App\Services\Providers\ReloadlyGiftCardProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

ini_set('memory_limit', '-1');
ini_set('serialize_precision', '-1');
class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        $useFakes = config('app_env.fakes.enabled');

        $this->app->singleton(BillPaymentProvider::class, function ($app) use ($useFakes) {
            return $useFakes ? new FakeBillPaymentProvider() : new ReloadlyBillPaymentProvider();
        });

        $this->app->singleton(AirtimeProvider::class, function ($app) use ($useFakes) {
            return $useFakes ? new FakeAirtimeProvider() : new ReloadlyAirtimeProvider();
        });

        $this->app->singleton(GiftCardProvider::class, function ($app) use ($useFakes) {
            return $useFakes ? new FakeGiftCardProvider() : new ReloadlyGiftCardProvider();
        });

        $this->app->singleton(ExchangeRateProvider::class, function ($app) use ($useFakes) {
            return $useFakes ? new FakeExchangeProvider() : new CurrencyLayerExchangeProvider();
        });

        $this->app->singleton(PaymentProvider::class, function ($app) use ($useFakes) {
            return $useFakes ? new FakePaymentProvider() : new NullPaymentProvider();
        });
    }

    public function boot()
    {
        Paginator::useBootstrapFive();
        Schema::defaultStringLength(191);
        if (config('app.force_https') && $this->app->environment('production') && !app()->runningInConsole()) {
            URL::forceScheme('https');
        }

        $this->extendValidationRule();
    }

    public function extendValidationRule()
    {
        Validator::extend('g_recaptcha_verify', function ($attribute, $value, $parameters, $validator) {
            $extension = ExtensionProvider::get()->where('slug', ExtensionConst::GOOGLE_RECAPTCHA_SLUG)->first();
            if (!$extension) {
                return false;
            }
            $secret_key = $extension->shortcode->secret_key->value ?? '';

            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $secret_key,
                'response' => $value,
            ])->json();
            if (isset($response['success']) && $response['success'] == false) {
                return false;
            }
            return true;
        }, ':message');
    }
}
