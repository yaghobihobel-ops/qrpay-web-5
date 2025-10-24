<?php

namespace App\Providers;

use App\Support\Routing\SmartRoutingEngine;
use Illuminate\Support\ServiceProvider;

class RoutingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SmartRoutingEngine::class, function ($app) {
            $strategyClasses = config('routing.strategies', []);

            $strategies = array_map(static function (string $class) use ($app) {
                return $app->make($class);
            }, $strategyClasses);

            return new SmartRoutingEngine($strategies);
        });
    }
}
