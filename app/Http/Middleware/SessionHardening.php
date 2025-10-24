<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class SessionHardening
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (! $request->hasSession()) {
            return $response;
        }

        $session = $request->session();
        $now = Carbon::now();

        if ($session->has('last_activity_at')) {
            $last = Carbon::parse($session->get('last_activity_at'));
            $timeout = (int) config('security.session.timeout', 15);
            if ($timeout > 0 && $now->diffInMinutes($last) >= $timeout) {
                Auth::logout();
                $session->invalidate();
                $session->regenerateToken();

                return redirect()->route('user.login')->withErrors([
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
}
