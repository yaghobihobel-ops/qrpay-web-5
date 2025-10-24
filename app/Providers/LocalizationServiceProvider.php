<?php

namespace App\Providers;

use App\Support\Countries\CountryModuleRegistry;
use App\Support\Localization\LocaleManager;
use Illuminate\Support\ServiceProvider;

class LocalizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LocaleManager::class, function ($app) {
            $config = $app['config']->get('localization', []);
            $locales = $config['locales'] ?? [];
            $default = $config['default'] ?? $app['config']->get('app.locale', 'en');
            $fallback = $config['fallback'] ?? $app['config']->get('app.fallback_locale', 'en');

            return new LocaleManager(
                $locales,
                $default,
                $fallback,
                $app->make(CountryModuleRegistry::class),
            );
        });
    }

    public function boot(): void
    {
        $manager = $this->app->make(LocaleManager::class);

        view()->share('supported_locale_catalog', $manager->all());
        view()->share('rtl_locales', $manager->rtlLocales());
        view()->share('country_supported_locales', $manager->supportedLocalesMapByCountry());
        view()->share('country_default_locales', $manager->defaultLocalesByCountry());
    }
}
