<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class SessionHardening
{
    /**
     * Map of guard names to their login route names.
     */
    protected array $guardLoginRoutes = [
        'admin' => 'admin.login',
        'agent' => 'agent.login',
        'merchant' => 'merchant.login',
        'web' => 'user.login',
    ];

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (! $request->hasSession()) {
            return $response;
        }

        $session = $request->session();
        $now = Carbon::now();
        $activeGuard = $this->resolveGuard($request);

        if ($session->has('last_activity_at')) {
            $last = Carbon::parse($session->get('last_activity_at'));
            $timeout = (int) config('security.session.timeout', 15);
            if ($timeout > 0 && $now->diffInMinutes($last) >= $timeout) {
                if ($activeGuard) {
                    Auth::guard($activeGuard)->logout();
                } else {
                    Auth::logout();
                }
                $session->invalidate();
                $session->regenerateToken();

                return redirect()->route($this->resolveLoginRoute($activeGuard))->withErrors([
                    'session' => __('Your session expired due to inactivity.'),
                ]);
            }
        }

        $session->put('last_activity_at', $now->toIso8601String());

        $rotateInterval = (int) config('security.session.rotate_interval', 30);
        $rotatedAt = $session->get('session_rotated_at');
        if ($rotateInterval > 0 && ($rotatedAt === null || $now->diffInMinutes(Carbon::parse($rotatedAt)) >= $rotateInterval)) {
            $session->migrate(true);
            $session->put('session_rotated_at', $now->toIso8601String());
        }

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'no-referrer');

        return $response;
    }

    protected function resolveGuard(Request $request): ?string
    {
        $guards = collect(config('auth.guards', []))
            ->filter(fn ($config) => ($config['driver'] ?? null) === 'session')
            ->keys();

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                return $guard;
            }
        }

        $path = trim($request->path(), '/');

        if ($path === 'admin' || str_starts_with($path, 'admin/')) {
            return 'admin';
        }

        if ($path === 'merchant' || str_starts_with($path, 'merchant/')) {
            return 'merchant';
        }

        if ($path === 'agent' || str_starts_with($path, 'agent/')) {
            return 'agent';
        }

        return 'web';
    }

    protected function resolveLoginRoute(?string $guard): string
    {
        if ($guard && isset($this->guardLoginRoutes[$guard])) {
            return $this->guardLoginRoutes[$guard];
        }

        return $this->guardLoginRoutes['web'];
    }
}
