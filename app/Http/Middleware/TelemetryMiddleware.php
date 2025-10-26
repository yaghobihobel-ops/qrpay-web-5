<?php

namespace App\Http\Middleware;

use App\Services\Telemetry\TelemetryManager;
use Closure;
use Illuminate\Http\Request;

class TelemetryMiddleware
{
    public function __construct(private TelemetryManager $telemetry)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        if (! $this->telemetry->isEnabled()) {
            return $next($request);
        }

        return $this->telemetry->traceRequest($request, $next);
    }
}
