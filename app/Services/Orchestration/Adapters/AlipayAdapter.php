<?php

namespace App\Services\Orchestration\Adapters;

class AlipayAdapter extends AbstractProviderAdapter
{
    public function __construct(bool $available = true)
    {
        parent::__construct(
            'Alipay',
            slaScore: 0.995,
            kpiMetrics: [
                'latency_ms' => 120,
                'success_rate' => 0.997,
            ],
            available: $available,
            supportedCurrencies: ['CNY', 'USD', 'EUR'],
            supportedCountries: ['CN', 'SG', 'MY', 'US']
        );
    }
}
