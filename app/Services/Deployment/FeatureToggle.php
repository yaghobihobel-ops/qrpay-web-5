<?php

namespace App\Services\Deployment;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class FeatureToggle
{
    /**
     * Determine whether the feature is enabled for the given context.
     */
    public function isEnabled(string $feature, $user = null, ?Request $request = null): bool
    {
        $definition = $this->definition($feature);

        if (!$definition) {
            return false;
        }

        if (Arr::get($definition, 'enabled', false)) {
            return true;
        }

        $canary = Arr::get($definition, 'canary', []);
        if (!Arr::get($canary, 'enabled', false)) {
            return false;
        }

        $percentage = (int) Arr::get($canary, 'percentage', 0);
        if ($percentage <= 0) {
            return false;
        }

        $identifier = $this->identifier($canary, $user, $request);
        if ($identifier === null) {
            return false;
        }

        $bucket = crc32($identifier) % 100;

        return $bucket < $percentage;
    }

    /**
     * Return the configuration for the given feature.
     */
    public function definition(string $feature): array
    {
        return config("features.features.{$feature}", []);
    }

    /**
     * Return the rollout percentage for the feature.
     */
    public function percentage(string $feature): int
    {
        $definition = $this->definition($feature);

        if (Arr::get($definition, 'enabled', false)) {
            return 100;
        }

        $canary = Arr::get($definition, 'canary', []);

        return (int) Arr::get($canary, 'percentage', 0);
    }

    /**
     * Resolve the identifier used to bucket users for canary releases.
     */
    protected function identifier(array $canary, $user = null, ?Request $request = null): ?string
    {
        $preferred = Arr::get($canary, 'identifier');

        if ($preferred && $user) {
            $value = data_get($user, $preferred);
            if ($value !== null) {
                return (string) $value;
            }
        }

        if ($user && data_get($user, 'id') !== null) {
            return (string) data_get($user, 'id');
        }

        if ($request) {
            return (string) $request->ip();
        }

        return null;
    }
}
