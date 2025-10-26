<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;

class FeatureToggle
{
    public const FEATURE_CURRENCY_SERVICE = 'currency_service';
    public const FEATURE_WITHDRAWAL_SERVICE = 'withdrawal_service';
    public const FEATURE_EXCHANGE_SERVICE = 'exchange_service';

    /**
     * Determine if the feature is enabled.
     */
    public function isEnabled(string $feature): bool
    {
        return (bool) Arr::get($this->getFlags(), $feature, false);
    }

    /**
     * Determine if the feature is disabled.
     */
    public function isDisabled(string $feature): bool
    {
        return ! $this->isEnabled($feature);
    }

    /**
     * Return the list of feature flags with their current state.
     */
    public function all(): array
    {
        return $this->getFlags();
    }

    protected function getFlags(): array
    {
        return Config::get('feature-flags', []);
    }
}
