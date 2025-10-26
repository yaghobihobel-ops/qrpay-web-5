<?php

namespace App\Services\Exchange;

interface ExchangeRateProviderInterface
{
    /**
     * Fetch exchange rates for the given list of symbols.
     *
     * @param  array<int, string>  $symbols
     * @return array<string, float>
     */
    public function fetchRates(array $symbols): array;

    /**
     * Convert an amount from one currency into another.
     */
    public function convert(float $amount, string $from, string $to): float;
}
