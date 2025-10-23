<?php

namespace App\Services\Fakes;

use App\Services\Contracts\AirtimeProvider;
use Illuminate\Support\Str;

class FakeAirtimeProvider implements AirtimeProvider
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

    public function getCountries(?string $iso = null): array
    {
        $countries = $this->scenario()['airtime']['countries'] ?? [];
        if ($iso) {
            $filtered = array_values(array_filter($countries, function ($country) use ($iso) {
                return strtoupper($country['iso']) === strtoupper($iso);
            }));
            return $filtered;
        }
        return $countries;
    }

    public function autoDetectOperator(string $phone, string $iso)
    {
        $operators = $this->scenario()['airtime']['operators'][strtoupper($iso)] ?? [];
        if (empty($operators)) {
            return [
                'status' => false,
                'message' => 'No operators configured for the requested ISO in sandbox data.',
            ];
        }

        $operator = $operators[0];
        $operator['status'] = 'SUCCESSFUL';
        $operator['phone'] = $phone;
        $operator['countryIso'] = strtoupper($iso);

        return $operator;
    }

    public function makeTopUp(array $data): array
    {
        $amount = $data['amount'] ?? 0;
        $operatorId = $data['operatorId'] ?? null;

        if ($amount <= 0) {
            return [
                'status' => false,
                'message' => 'Invalid top-up amount for sandbox request.',
            ];
        }

        if (str_contains(strtoupper((string) ($data['recipientPhone'] ?? '')), 'FAIL')) {
            return [
                'status' => false,
                'message' => 'Sandbox airtime top-up failed intentionally.',
            ];
        }

        $transactionId = 'AIR-' . Str::upper(Str::random(6));

        return [
            'status' => 'SUCCESSFUL',
            'transactionId' => $transactionId,
            'operatorId' => $operatorId,
            'requestedAmount' => $amount,
            'deliveredAmount' => $amount,
        ];
    }
}
