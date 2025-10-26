<?php

namespace App\Services\Orchestration\Providers;

class AlipayAdapter extends AbstractPaymentProviderAdapter
{
    public function __construct()
    {
        parent::__construct(
            'Alipay',
            [
                'uptime' => 99.95,
                'latency' => 180,
                'response_time_variance' => 15,
            ],
            [
                'success_rate' => 0.985,
                'throughput' => 240,
            ]
        );
    }
}
