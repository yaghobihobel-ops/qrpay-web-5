<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class HorizonServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (!class_exists(\Laravel\Horizon\Horizon::class)) {
            return;
        }

        \Laravel\Horizon\Horizon::auth(function ($request) {
            return (bool) $request->user('admin');
        });
    }
}
