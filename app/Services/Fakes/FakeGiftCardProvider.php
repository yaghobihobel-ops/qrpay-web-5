<?php

namespace App\Services\Fakes;

use App\Services\Contracts\GiftCardProvider;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class FakeGiftCardProvider implements GiftCardProvider
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

    public function getCountries(): array
    {
        $products = $this->scenario()['gift_cards']['products'] ?? [];
        $countries = [];
        foreach ($products as $product) {
            $countries[$product['countryCode']] = [
                'isoName' => $product['countryCode'],
                'name' => $product['countryCode'],
                'currencyCode' => $product['recipientCurrencyCode'] ?? $product['senderCurrencyCode'],
            ];
        }

        return array_values($countries);
    }

    public function getProducts(array $params = [], bool $cache = false): array
    {
        $products = $this->scenario()['gift_cards']['products'] ?? [];

        if (isset($params['countryCode'])) {
            $products = array_values(array_filter($products, function ($product) use ($params) {
                return strtoupper($product['countryCode']) === strtoupper($params['countryCode']);
            }));
        }

        return [
            'content' => $products,
            'page' => 0,
            'size' => count($products),
            'totalPages' => 1,
        ];
    }

    public function getProductInfo(int $productId): array
    {
        $products = $this->scenario()['gift_cards']['products'] ?? [];
        foreach ($products as $product) {
            if ((int) $product['productId'] === (int) $productId) {
                return $product;
            }
        }

        return [];
    }

    public function getProductInfoByIso(string $iso): array
    {
        $products = $this->scenario()['gift_cards']['products'] ?? [];
        return array_values(array_filter($products, function ($product) use ($iso) {
            return strtoupper($product['countryCode']) === strtoupper($iso);
        }));
    }

    public function createOrder(array $data): array
    {
        $scenario = $this->scenario();
        $orders = $scenario['gift_cards']['orders'] ?? [];

        $reference = $data['customIdentifier'] ?? Str::uuid()->toString();
        $transactionId = 'GC-' . Str::upper(Str::random(8));

        $order = [
            'status' => 'SUCCESSFUL',
            'transactionId' => $transactionId,
            'customIdentifier' => $reference,
            'cards' => [
                [
                    'code' => 'GC-' . Str::upper(Str::random(10)),
                    'pin' => (string) random_int(100000, 999999),
                    'expiresAt' => now()->addYears(5)->toDateString(),
                ],
            ],
        ];

        $orders[$reference] = $order;
        $scenario['gift_cards']['orders'] = $orders;
        $this->repository->store($scenario);

        return array_merge($order, [
            'customIdentifier' => $reference,
        ]);
    }

    public function redeemCodes(string $trxId): array
    {
        $orders = $this->scenario()['gift_cards']['orders'] ?? [];
        $order = Arr::first($orders, function ($item) use ($trxId) {
            return ($item['transactionId'] ?? null) === $trxId || ($item['customIdentifier'] ?? null) === $trxId;
        });

        if (!$order) {
            return [
                'status' => false,
                'message' => 'Sandbox order not found for redemption.',
                'cards' => [],
            ];
        }

        return array_merge($order, ['status' => $order['status'] ?? 'SUCCESSFUL']);
    }

    public function webhookResponse(array $response_data)
    {
        return $response_data;
    }
}
