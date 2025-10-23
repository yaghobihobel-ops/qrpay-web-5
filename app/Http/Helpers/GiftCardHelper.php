<?php

namespace App\Http\Helpers;

use App\Services\Contracts\GiftCardProvider;

class GiftCardHelper
{
    public const COUNTRIES_CACHE_KEY = 'gift_card_api_countries_{provider}_{env}';

    public const ALL_PRODUCTS_CACHE_KEY = 'gift_card_api_all_products';

    public const STATUS_SUCCESS = 'SUCCESSFUL';
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_PROCESSING = 'PROCESSING';
    public const STATUS_REFUNDED = 'REFUNDED';
    public const STATUS_FAILED = 'FAILED';

    public const PRICE_TYPES = [
        'FIXED' => 'FIXED',
        'RANGE' => 'RANGE',
    ];

    protected GiftCardProvider $provider;

    public function __construct(?GiftCardProvider $provider = null)
    {
        $this->provider = $provider ?? app(GiftCardProvider::class);
    }

    public function getCountries(): array
    {
        return $this->provider->getCountries();
    }

    public function getProducts(array $params = [], $cache = false): array
    {
        return $this->provider->getProducts($params, $cache);
    }

    public function getProductInfo(int $productId): array
    {
        return $this->provider->getProductInfo($productId);
    }

    public function getProductInfoByIso(string $iso): array
    {
        return $this->provider->getProductInfoByIso($iso);
    }

    public function createOrder(array $data): array
    {
        return $this->provider->createOrder($data);
    }

    public function redeemCodes(string $trx_id): array
    {
        return $this->provider->redeemCodes($trx_id);
    }

    public function webhookResponse(array $response_data)
    {
        return $this->provider->webhookResponse($response_data);
    }
}
