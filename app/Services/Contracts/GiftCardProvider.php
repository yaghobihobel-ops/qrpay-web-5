<?php

namespace App\Services\Contracts;

interface GiftCardProvider
{
    public function getCountries(): array;

    public function getProducts(array $params = [], bool $cache = false): array;

    public function getProductInfo(int $productId): array;

    public function getProductInfoByIso(string $iso): array;

    public function createOrder(array $data): array;

    public function redeemCodes(string $trxId): array;

    public function webhookResponse(array $responseData);
}
