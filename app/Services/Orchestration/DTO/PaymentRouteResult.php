<?php

namespace App\Services\Orchestration\DTO;

use App\Models\PaymentRoute;
use App\Services\Orchestration\Contracts\PaymentProviderAdapter;
use App\Services\Pricing\DTO\FeeQuote;

class PaymentRouteResult
{
    public function __construct(
        protected PaymentProviderAdapter $provider,
        protected PaymentRoute $route,
        protected array $sla,
        protected array $kpi,
        protected ?FeeQuote $feeQuote = null
    ) {
    }

    public function getProvider(): PaymentProviderAdapter
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

    public function getFeeQuote(): ?FeeQuote
    {
        return $this->feeQuote;
    }
}
