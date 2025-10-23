<?php

namespace App\Services\Contracts;

interface AirtimeProvider
{
    public function getCountries(?string $iso = null): array;

    public function autoDetectOperator(string $phone, string $iso);

    public function makeTopUp(array $data): array;
}
