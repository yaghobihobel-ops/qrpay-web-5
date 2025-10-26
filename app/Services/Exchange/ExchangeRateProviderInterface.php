<?php

namespace App\Services\Exchange;

interface ExchangeRateProviderInterface
{
    /**
     * Retrieve the latest exchange rates from the provider.
     *
     * @return array{
     *     status: bool,
     *     message: string,
     *     data: array<string, mixed>,
     *     from_cache?: bool
     * }
     */
    public function getLiveExchangeRates(): array;

    /**
     * Retrieve the list of supported currencies from the provider.
     *
     * @return array{
     *     status: bool,
     *     message: string,
     *     data: array<string, mixed>,
     *     from_cache?: bool
     * }
     */
    public function getSupportedCurrencies(): array;

    /**
     * Unique identifier of the provider (slug).
     */
    public function getIdentifier(): string;
}
