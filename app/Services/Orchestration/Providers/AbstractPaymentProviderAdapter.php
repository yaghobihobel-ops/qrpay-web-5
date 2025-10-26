<?php

namespace App\Services\Orchestration\Providers;

use App\Models\User;
use App\Services\Orchestration\Contracts\PaymentProviderAdapterInterface;

abstract class AbstractPaymentProviderAdapter implements PaymentProviderAdapterInterface
{
    protected bool $available;

    public function __construct(
        protected string $name,
        protected array $slaProfile,
        protected array $kpiMetrics,
        bool $available = true
    ) {
        $this->available = $available;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isAvailable(?User $user, string $currency, string $destinationCountry): bool
    {
        return $this->available;
    }

    public function setAvailability(bool $available): void
    {
        $this->available = $available;
    }

    public function getSlaProfile(?User $user, string $currency, string $destinationCountry): array
    {
        return $this->slaProfile;
    }

    public function getKpiMetrics(?User $user, string $currency, string $destinationCountry): array
    {
        return $this->kpiMetrics;
    }

    public function updateSlaProfile(array $profile): void
    {
        $this->slaProfile = array_merge($this->slaProfile, $profile);
    }

    public function updateKpiMetrics(array $metrics): void
    {
        $this->kpiMetrics = array_merge($this->kpiMetrics, $metrics);
    }
}
