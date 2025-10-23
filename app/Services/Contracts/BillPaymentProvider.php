<?php

namespace App\Services\Contracts;

interface BillPaymentProvider
{
    public function getBillers(array $params = [], bool $cache = false): array;

    public function getSingleBiller($id): array;

    public function payUtilityBill(array $data): array;

    public function getTransaction($id): array;
}
