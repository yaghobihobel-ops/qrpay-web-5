<?php

namespace App\Http\Middleware;

use App\Services\Deployment\FeatureToggle;
use Closure;
use Illuminate\Http\Request;

class EnsureFeatureIsEnabled
{
    public function __construct(private FeatureToggle $featureToggle)
    {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $feature)
    {
        $user = $request->user();

        if (!$this->featureToggle->isEnabled($feature, $user, $request)) {
            abort(404);
        }

        return $next($request);
    }
}
