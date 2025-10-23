<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

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
                ->group(base_path('routes/api/api.php'));

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

            Route::middleware(['web', 'auth:admin', 'app.mode', 'admin.role.guard',"admin.google.two.factor"])
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
        $this->registerApiRateLimiter('api');
        $this->registerApiRateLimiter('merchant-api');
        $this->registerApiRateLimiter('agent-api');
    }

    protected function registerApiRateLimiter(string $name): void
    {
        RateLimiter::for($name, function (Request $request) {
            $service = $request->route()?->defaults['throttle_service'] ?? 'default';
            $limits = $this->buildLimits($service, $request);

            if (count($limits) === 0) {
                return Limit::none();
            }

            return $limits;
        });
    }

    protected function buildLimits(string $service, Request $request): array
    {
        $config = config('api.rate_limits.services', []);
        $serviceConfig = array_replace_recursive($config['default'] ?? [], $config[$service] ?? []);

        $limits = [];

        if (! empty($serviceConfig['per_user']['max_attempts'])) {
            $limits[] = $this->buildLimit(
                $serviceConfig['per_user'],
                $request->user()?->getAuthIdentifier() ?: 'guest:'.$request->ip()
            );
        }

        if (! empty($serviceConfig['per_ip']['max_attempts'])) {
            $limits[] = $this->buildLimit($serviceConfig['per_ip'], $request->ip());
        }

        return array_filter($limits);
    }

    protected function buildLimit(array $config, string $key): ?Limit
    {
        $maxAttempts = (int) ($config['max_attempts'] ?? 0);

        if ($maxAttempts <= 0) {
            return null;
        }

        $decayMinutes = max(1, (int) ($config['decay_minutes'] ?? 1));

        return Limit::perMinutes($decayMinutes, $maxAttempts)->by($key);
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
