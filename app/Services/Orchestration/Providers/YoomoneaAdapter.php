<?php

namespace App\Services\Orchestration\Providers;

class YoomoneaAdapter extends AbstractPaymentProviderAdapter
{
    public function __construct()
    {
        parent::__construct(
            'Yoomonea',
            [
                'uptime' => 99.6,
                'latency' => 210,
                'response_time_variance' => 30,
            ],
            [
                'success_rate' => 0.963,
                'throughput' => 160,
            ]
        );
    }
}
