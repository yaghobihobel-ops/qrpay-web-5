<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware(['system.maintenance.api','api'])
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware(['system.maintenance.api','api'])
            ->prefix('merchant-api')
                ->group(base_path('routes/api/merchant_api.php'));

            Route::middleware(['system.maintenance.api','api'])
                ->prefix('agent-api')
                ->group(base_path('routes/api/agent_api.php'));


            Route::middleware(['web','system.maintenance'])
                ->group(base_path('routes/web.php'));

            Route::middleware(['web','auth','verification.guard','user.google.two.factor','system.maintenance'])
                ->group(base_path('routes/user.php'));

            Route::middleware(['web', 'auth:admin', 'app.mode', 'admin.role.guard',"admin.google.two.factor", 'admin.audit'])
                ->group(base_path('routes/admin.php'));

            Route::middleware(['web','auth:merchant','verification.guard.merchant','merchant.google.two.factor','system.maintenance'])
            ->group(base_path('routes/merchant.php'));

            Route::middleware(['web','auth:agent','verification.guard.agent','agent.google.two.factor','system.maintenance'])
            ->group(base_path('routes/agent.php'));

            Route::middleware(['web','system.maintenance'])
                ->group(base_path('routes/auth.php'));

            Route::middleware(['web','system.maintenance'])
                ->group(base_path('routes/global.php'));

            Route::middleware('api')
                ->group(base_path('routes/payment-gateway/qr_pay/v1/routes.php'));

            //demo checkout
            Route::middleware(['web','system.maintenance'])
            ->group(base_path('routes/payment-gateway/qr_pay/v1/checkout.php'));

            $this->mapInstallerRoute();
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('user-login', function (Request $request) {
            $maxAttempts = (int) config('auth.rate_limits.user_login.max_attempts', 5);
            $decaySeconds = (int) config('auth.rate_limits.user_login.decay_seconds', 60);
            $minutes = max(1, (int) ceil($decaySeconds / 60));

            return Limit::perMinutes($minutes, $maxAttempts)->by(
                Str::lower($request->input('email', '')) . '|' . $request->ip()
            );
        });
    }

    /**
     * Configure/Place installer routes.
     *
     * @return void
     */
    protected function mapInstallerRoute() {
        if(file_exists(base_path('resources/installer/src/routes/web.php'))) {
            Route::middleware('web')
                ->group(base_path('resources/installer/src/routes/web.php'));
        }
    }
}
