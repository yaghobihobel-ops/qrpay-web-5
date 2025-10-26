<?php

namespace App\Services\Orchestration\Adapters;

class BluBankAdapter extends AbstractProviderAdapter
{
    public function __construct(bool $available = true)
    {
        parent::__construct(
            'BluBank',
            slaScore: 0.982,
            kpiMetrics: [
                'latency_ms' => 210,
                'success_rate' => 0.989,
            ],
            available: $available,
            supportedCurrencies: ['USD', 'EUR', 'GBP'],
            supportedCountries: ['US', 'GB', 'DE', 'FR', 'CN']
        );
    }
}
