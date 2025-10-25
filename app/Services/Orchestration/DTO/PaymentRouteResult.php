<?php

namespace App\Services\Orchestration\DTO;

use App\Models\PaymentRoute;
use App\Services\Orchestration\Contracts\PaymentProviderAdapterInterface;

class PaymentRouteResult
{
    public function __construct(
        protected PaymentProviderAdapterInterface $provider,
        protected PaymentRoute $route,
        protected array $sla,
        protected array $kpi
    ) {
    }

    public function getProvider(): PaymentProviderAdapterInterface
    {
        return $this->provider;
    }

    public function getRoute(): PaymentRoute
    {
        return $this->route;
    }

    public function getSla(): array
    {
        return $this->sla;
    }

    public function getKpi(): array
    {
        return $this->kpi;
    }
}
