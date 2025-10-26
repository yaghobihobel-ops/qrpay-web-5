<?php

namespace App\Services\Orchestration\Adapters;

class GenericProviderAdapter extends AbstractProviderAdapter
{
    /**
     * @param array<int, string> $supportedCurrencies
     * @param array<int, string> $supportedCountries
     * @param array<string, float|int> $kpiMetrics
     */
    public function __construct(
        string $name,
        float $slaScore = 0.96,
        array $kpiMetrics = [
            'latency_ms' => 320,
            'success_rate' => 0.97,
        ],
        bool $available = true,
        array $supportedCurrencies = [],
        array $supportedCountries = [],
    ) {
        parent::__construct(
            $name,
            slaScore: $slaScore,
            kpiMetrics: $kpiMetrics,
            available: $available,
            supportedCurrencies: $supportedCurrencies,
            supportedCountries: $supportedCountries
        );
    }
}
