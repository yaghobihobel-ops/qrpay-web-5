<?php

namespace App\Contracts\Providers;

/**
 * Foreign exchange providers should implement this contract for quoting,
 * converting, and reporting settlement activity.
 */
interface FXProviderInterface
{
    /**
     * Request a quote for a currency pair and amount.
     */
    public function quote(string $pair, float $amount, string $side, array $context = []): array;

    /**
     * Execute a conversion using a previously accepted quote reference.
     */
    public function convert(array $payload): array;

    /**
     * Produce a settlement or reconciliation report.
     */
    public function settlementReport(array $payload): array;
}
