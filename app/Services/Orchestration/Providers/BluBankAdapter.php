<?php

namespace App\Services\Orchestration\Providers;

class BluBankAdapter extends AbstractPaymentProviderAdapter
{
    public function __construct()
    {
        parent::__construct(
            'BluBank',
            [
                'uptime' => 99.8,
                'latency' => 220,
                'response_time_variance' => 25,
            ],
            [
                'success_rate' => 0.972,
                'throughput' => 180,
            ]
        );
    }
}
