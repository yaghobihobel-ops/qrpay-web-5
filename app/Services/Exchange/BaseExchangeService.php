<?php

namespace App\Services\Exchange;

use App\Services\Exchange\Exceptions\ExchangeRateException;

use Illuminate\Support\Arr;

abstract class BaseExchangeService implements ExchangeRateProviderInterface
{
    public function convert(float $amount, string $from, string $to): float
    {
        $symbols = array_unique([$from, $to]);
        $rates = $this->fetchRates($symbols);

        if (!isset($rates[$from])) {
            throw ExchangeRateException::missingRate($from, static::class);
        }

        if (!isset($rates[$to])) {
            throw ExchangeRateException::missingRate($to, static::class);
        }

        $fromRate = (float) $rates[$from];
        $toRate = (float) $rates[$to];

        if ($fromRate <= 0 || $toRate <= 0) {
            throw ExchangeRateException::invalidRate(static::class);
        }

        return ($amount / $fromRate) * $toRate;
    }

    /**
     * @param  array<int, mixed>|array<string, mixed>  $entries
     * @return array<string, float>
     */
    protected function normalizeRates($entries): array
    {
        $normalized = [];

        if (!is_array($entries)) {
            return $normalized;
        }

        if (Arr::isAssoc($entries)) {
            foreach ($entries as $code => $value) {
                $normalized[strtoupper((string) $code)] = (float) $value;
            }

            return $normalized;
        }

        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $code = Arr::get($entry, 'code') ?? Arr::get($entry, 'symbol') ?? Arr::get($entry, 'currency');
            $value = Arr::get($entry, 'rate') ?? Arr::get($entry, 'value');

            if ($code === null || $value === null) {
                continue;
            }

            $normalized[strtoupper((string) $code)] = (float) $value;
        }

        return $normalized;
    }

    protected function filterRates(array $rates, array $symbols): array
    {
        $symbols = array_map('strtoupper', $symbols);

        return array_intersect_key($rates, array_flip($symbols));
    }
}
