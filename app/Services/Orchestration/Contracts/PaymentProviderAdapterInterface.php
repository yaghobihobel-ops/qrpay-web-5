<?php

namespace App\Services\Orchestration\Contracts;

use App\Models\User;

interface PaymentProviderAdapterInterface
{
    public function getName(): string;

    public function isAvailable(?User $user, string $currency, string $destinationCountry): bool;

    public function getSlaProfile(?User $user, string $currency, string $destinationCountry): array;

    public function getKpiMetrics(?User $user, string $currency, string $destinationCountry): array;

    public function updateSlaProfile(array $profile): void;

    public function updateKpiMetrics(array $metrics): void;
}
