<?php

namespace App\Services\Deployment;

use Illuminate\Http\Request;

class CanaryReleaseManager
{
    public function __construct(private FeatureToggle $featureToggle)
    {
    }

    /**
     * Determine if the request should hit the canary path for the feature.
     */
    public function shouldUseCanary(string $feature, $user = null, ?Request $request = null): bool
    {
        return $this->featureToggle->isEnabled($feature, $user, $request);
    }

    /**
     * Returns the rollout percentage for observability.
     */
    public function rolloutPercentage(string $feature): int
    {
        return $this->featureToggle->percentage($feature);
    }
}
