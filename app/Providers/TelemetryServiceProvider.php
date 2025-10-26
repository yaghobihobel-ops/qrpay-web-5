<?php

namespace App\Providers;

use App\Services\Telemetry\TelemetryManager;
use Illuminate\Support\ServiceProvider;

class TelemetryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TelemetryManager::class, function ($app) {
            return new TelemetryManager($app['config']->get('telemetry', []));
        });
    }

    public function boot(TelemetryManager $telemetry): void
    {
        $telemetry->boot();
    }
}
