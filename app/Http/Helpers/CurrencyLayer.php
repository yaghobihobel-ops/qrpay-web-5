<?php

namespace App\Http\Helpers;

use App\Services\Contracts\ExchangeRateProvider;

class CurrencyLayer
{
    protected ExchangeRateProvider $provider;

    public function __construct(?ExchangeRateProvider $provider = null)
    {
        $this->provider = $provider ?? app(ExchangeRateProvider::class);
    }

    public function getLiveExchangeRates(): array
    {
        return $this->provider->getLiveExchangeRates();
    }

    public function apiCurrencyList(): array
    {
        return $this->provider->apiCurrencyList();
    }
}
