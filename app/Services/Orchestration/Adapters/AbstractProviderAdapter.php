<?php

namespace App\Services\Orchestration\Adapters;

use App\Services\Orchestration\Contracts\PaymentProviderAdapter;

abstract class AbstractProviderAdapter implements PaymentProviderAdapter
{
    /**
     * @param array<int, string> $supportedCurrencies
     * @param array<int, string> $supportedCountries
     * @param array<string, float|int> $kpiMetrics
     */
    public function __construct(
        protected string $name,
        protected float $slaScore,
        protected array $kpiMetrics,
        protected bool $available = true,
        protected array $supportedCurrencies = [],
        protected array $supportedCountries = [],
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isAvailable(array $context = []): bool
    {
        return $this->available;
    }

    public function supports(string $currency, string $destinationCountry): bool
    {
        $currency = strtoupper($currency);
        $destinationCountry = strtoupper($destinationCountry);

        $supportsCurrency = empty($this->supportedCurrencies)
            || in_array($currency, array_map('strtoupper', $this->supportedCurrencies), true);

        $supportsCountry = empty($this->supportedCountries)
            || in_array($destinationCountry, array_map('strtoupper', $this->supportedCountries), true);

        return $supportsCurrency && $supportsCountry;
    }

    public function getSlaScore(): float
    {
        return $this->slaScore;
    }

    public function getKpiMetrics(): array
    {
        return $this->kpiMetrics;
    }

    public function setAvailability(bool $available): void
    {
        $this->available = $available;
    }
}
