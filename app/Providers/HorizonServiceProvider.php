<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class HorizonServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        if (! class_exists(\Laravel\Horizon\Horizon::class)) {
            return;
        }

        \Laravel\Horizon\Horizon::auth(function ($request) {
            $user = $request->user();

            if ($user && method_exists($user, 'isAdmin')) {
                return $user->isAdmin();
            }

            return $user !== null;
        });

        \Laravel\Horizon\Horizon::tag(function ($job) {
            if (method_exists($job, 'getQueue')) {
                return [$job->getQueue()];
            }

            return [];
        });
    }
}
