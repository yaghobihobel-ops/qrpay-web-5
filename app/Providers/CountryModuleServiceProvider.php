<?php

namespace App\Providers;

use App\Support\Countries\CountryModuleRegistry;
use Illuminate\Support\ServiceProvider;

class CountryModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CountryModuleRegistry::class, function ($app) {
            $definitions = $app['config']->get('countries.modules', []);

            return new CountryModuleRegistry($app, $definitions);
        });
    }
}
