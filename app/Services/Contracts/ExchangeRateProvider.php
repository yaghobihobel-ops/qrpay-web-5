<?php

namespace App\Services\Contracts;

interface ExchangeRateProvider
{
    public function getLiveExchangeRates(): array;

    public function apiCurrencyList(): array;
}
