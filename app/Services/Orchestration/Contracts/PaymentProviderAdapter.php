<?php

namespace App\Services\Orchestration\Contracts;

interface PaymentProviderAdapter
{
    /**
     * Name used by configuration entries and routing decisions.
     */
    public function getName(): string;

    /**
     * Determine whether the provider is available for routing based on the current context.
     *
     * @param array<string, mixed> $context
     */
    public function isAvailable(array $context = []): bool;

    /**
     * Determine whether the provider supports the given currency and destination country.
     */
    public function supports(string $currency, string $destinationCountry): bool;

    /**
     * SLA score (0-1) for the provider.
     */
    public function getSlaScore(): float;

    /**
     * Key performance indicators for the provider (latency, success rate, etc.).
     *
     * @return array<string, float|int>
     */
    public function getKpiMetrics(): array;
}
