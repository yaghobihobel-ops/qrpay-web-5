<?php

namespace App\Services\Orchestration\Adapters;

class YoomoneaAdapter extends AbstractProviderAdapter
{
    public function __construct(bool $available = true)
    {
        parent::__construct(
            'Yoomonea',
            slaScore: 0.972,
            kpiMetrics: [
                'latency_ms' => 250,
                'success_rate' => 0.985,
            ],
            available: $available,
            supportedCurrencies: ['USD', 'EUR', 'AUD'],
            supportedCountries: ['AU', 'US', 'FR', 'CN']
        );
    }
}
