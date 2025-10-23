<?php

namespace App\Services\Fakes;

use App\Services\Contracts\ExchangeRateProvider;

class FakeExchangeProvider implements ExchangeRateProvider
{
    protected FakeScenarioRepository $repository;

    public function __construct(?FakeScenarioRepository $repository = null)
    {
        $this->repository = $repository ?? new FakeScenarioRepository();
    }

    protected function scenario(): array
    {
        return $this->repository->load();
    }

    public function getLiveExchangeRates(): array
    {
        $rates = $this->scenario()['exchange_rates'] ?? [];

        return [
            'status' => true,
            'message' => 'Sandbox exchange rates loaded.',
            'data' => $rates,
        ];
    }

    public function apiCurrencyList(): array
    {
        $currencies = $this->scenario()['currencies'] ?? [];

        return [
            'status' => true,
            'message' => 'Sandbox currency list loaded.',
            'data' => $currencies,
        ];
    }
}
