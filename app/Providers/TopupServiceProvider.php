<?php

namespace App\Providers;

use App\Contracts\TopupProviderInterface;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class TopupServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(TopupProviderInterface::class, function ($app) {
            $default = config('topup.default');
            $providers = config('topup.providers', []);

            $implementation = $providers[$default] ?? null;

            if (!$implementation) {
                throw new InvalidArgumentException("Unsupported topup provider [{$default}].");
            }

            return $app->make($implementation);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
