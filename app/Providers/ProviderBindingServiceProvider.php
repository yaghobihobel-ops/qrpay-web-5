<?php

namespace App\Providers;

use App\Support\Countries\CountryModuleRegistry;
use App\Support\Providers\CountryProviderResolver;
use Illuminate\Support\ServiceProvider;

class ProviderBindingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $bindings = config('qrpay_providers.bindings', []);
        $countryOverrides = config('qrpay_providers.countries', []);

        if ($this->app->bound(CountryModuleRegistry::class)) {
            $moduleBindings = $this->app->make(CountryModuleRegistry::class)->providerBindingMap();

            foreach ($moduleBindings as $contract => $implementation) {
                if (! isset($bindings[$contract]) || ! $bindings[$contract]) {
                    $bindings[$contract] = $implementation;
                }
            }
        }

        $this->app->singleton(CountryProviderResolver::class, function ($app) use ($bindings, $countryOverrides) {
            $registry = $app->make(CountryModuleRegistry::class);

            return new CountryProviderResolver($app, $registry, $bindings, $countryOverrides);
        });

        foreach ($bindings as $contract => $concrete) {
            if (! $concrete) {
                continue;
            }

            $this->app->bind($contract, $concrete);
        }
    }
}
