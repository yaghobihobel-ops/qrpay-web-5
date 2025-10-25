<?php

namespace App\Providers;

use App\Constants\ExtensionConst;
use App\Providers\Admin\ExtensionProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

ini_set('memory_limit','-1');
ini_set('serialize_precision','-1');
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(InternalWalletService::class, fn () => new InternalWalletService());

        $this->app->singleton(RegionalPaymentManager::class, function ($app) {
            return new RegionalPaymentManager(
                $app->make(InternalWalletService::class),
                config('payments.regional_providers', [])
            );
        });

        $this->app->alias(RegionalPaymentManager::class, 'regional.payment.manager');
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrapFive();
        Schema::defaultStringLength(191);
        if(config('app.force_https') && $this->app->environment('production') && !app()->runningInConsole()) {
            URL::forceScheme('https');
        }

        //laravel extend validation rules
        $this->extendValidationRule();
    }

    protected function registerResponseMacros(): void
    {
        ResponseFacade::macro('success', function (string $message, mixed $details = null, int $status = 200, int $code = 0) {
            return ResponseFacade::json([
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ], $status);
        });

        ResponseFacade::macro('error', function (string $message, ApiErrorCode|int $code = ApiErrorCode::UNKNOWN, mixed $details = null, int $status = 400) {
            $code = $code instanceof ApiErrorCode ? $code->value : $code;

            return ResponseFacade::json([
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ], $status);
        });

        ResponseFacade::macro('paginated', function (LengthAwarePaginator $paginator, string $message = 'Fetched successfully.', int $status = 200, int $code = 0) {
            return ResponseFacade::json([
                'code' => $code,
                'message' => $message,
                'details' => [
                    'data' => $paginator->items(),
                    'meta' => [
                        'current_page' => $paginator->currentPage(),
                        'per_page' => $paginator->perPage(),
                        'total' => $paginator->total(),
                        'last_page' => $paginator->lastPage(),
                    ],
                ],
            ], $status);
        });
    }

    /**
     * extend laravel validation rules
     */
    public function extendValidationRule()
    {
        Validator::extend('g_recaptcha_verify', function($attribute, $value, $parameters, $validator) {
            $extension = ExtensionProvider::get()->where('slug', ExtensionConst::GOOGLE_RECAPTCHA_SLUG)->first();
            if(!$extension) return false;
            $secret_key = $extension->shortcode->secret_key->value ?? "";

            $response   =   Http::asForm()->post("https://www.google.com/recaptcha/api/siteverify",[
                'secret' => $secret_key,
                'response' => $value,
            ])->json();
            if(isset($response['success']) && $response['success'] == false) return false;
            return true;

        },":message");
    }
}
