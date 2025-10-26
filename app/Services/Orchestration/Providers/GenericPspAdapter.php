<?php

namespace App\Services\Orchestration\Providers;

class GenericPspAdapter extends AbstractPaymentProviderAdapter
{
    public function __construct(string $name, array $slaProfile, array $kpiMetrics, bool $available = true)
    {
        parent::__construct($name, $slaProfile, $kpiMetrics, $available);
    }
}
