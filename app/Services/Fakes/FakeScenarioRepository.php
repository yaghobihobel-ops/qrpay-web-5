<?php

namespace App\Services\Fakes;

use Illuminate\Filesystem\Filesystem;

class FakeScenarioRepository
{
    protected string $path;

    protected Filesystem $filesystem;

    public function __construct(?string $path = null, ?Filesystem $filesystem = null)
    {
        $this->filesystem = $filesystem ?? new Filesystem();
        $configuredPath = config('app_env.fakes.repository_path');
        $this->path = $path ?? ($configuredPath ?: storage_path('app/sandbox/fake_providers.json'));
    }

    public function load(): array
    {
        if (!$this->filesystem->exists($this->path)) {
            return $this->defaultData();
        }

        $contents = $this->filesystem->get($this->path);
        $data = json_decode($contents, true);

        if (!is_array($data)) {
            return $this->defaultData();
        }

        return array_replace_recursive($this->defaultData(), $data);
    }

    public function store(array $data): void
    {
        $directory = dirname($this->path);
        if (!$this->filesystem->exists($directory)) {
            $this->filesystem->makeDirectory($directory, 0755, true);
        }

        $payload = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $this->filesystem->put($this->path, $payload);
    }

    public function defaultData(): array
    {
        return [
            'payments' => [
                [
                    'reference' => 'PAY-SANDBOX-001',
                    'status' => 'success',
                    'amount' => 125.75,
                    'currency' => 'USD',
                    'channel' => 'card',
                ],
                [
                    'reference' => 'PAY-SANDBOX-FAILED',
                    'status' => 'failed',
                    'amount' => 42.00,
                    'currency' => 'USD',
                    'channel' => 'card',
                    'reason' => 'Insufficient funds',
                ],
            ],
            'exchange_rates' => [
                'USDUSD' => 1.0,
                'USDEUR' => 0.91,
                'USDGBP' => 0.78,
            ],
            'currencies' => [
                'USD' => 'United States Dollar',
                'EUR' => 'Euro',
                'GBP' => 'British Pound',
            ],
            'billers' => [
                'content' => [
                    [
                        'id' => 101,
                        'name' => 'Sandbox Electricity',
                        'minLocalTransactionAmount' => 10,
                        'maxLocalTransactionAmount' => 500,
                        'localAmountSupported' => true,
                        'localTransactionCurrencyCode' => 'USD',
                        'senderFee' => 1.25,
                    ],
                    [
                        'id' => 202,
                        'name' => 'Sandbox Internet',
                        'minLocalTransactionAmount' => 15,
                        'maxLocalTransactionAmount' => 300,
                        'localAmountSupported' => true,
                        'localTransactionCurrencyCode' => 'USD',
                        'senderFee' => 1.10,
                    ],
                ],
            ],
            'bill_transactions' => [
                'PAY-SANDBOX-UTILITY' => [
                    'status' => 'SUCCESSFUL',
                    'transactionId' => 'UTL-1001',
                ],
            ],
            'airtime' => [
                'countries' => [
                    [
                        'isoName' => 'United States',
                        'iso' => 'US',
                        'name' => 'United States',
                        'currencyCode' => 'USD',
                    ],
                    [
                        'isoName' => 'Canada',
                        'iso' => 'CA',
                        'name' => 'Canada',
                        'currencyCode' => 'CAD',
                    ],
                ],
                'operators' => [
                    'US' => [
                        [
                            'id' => 301,
                            'name' => 'Sandbox Mobile US',
                            'denominationType' => 'RANGE',
                            'localMinAmount' => 5,
                            'localMaxAmount' => 100,
                            'supportsLocalAmounts' => true,
                            'destinationCurrencyCode' => 'USD',
                            'senderCurrencyCode' => 'USD',
                            'fx' => ['rate' => 1],
                        ],
                    ],
                ],
                'topups' => [
                    [
                        'transactionId' => 'AIR-SUCCESS-001',
                        'status' => 'SUCCESSFUL',
                        'deliveredAmount' => 20,
                        'requestedAmount' => 20,
                        'operatorId' => 301,
                    ],
                ],
            ],
            'gift_cards' => [
                'products' => [
                    [
                        'productId' => 401,
                        'productName' => 'Sandbox Store Gift Card',
                        'countryCode' => 'US',
                        'senderCurrencyCode' => 'USD',
                        'recipientCurrencyCode' => 'USD',
                        'minAmount' => 10,
                        'maxAmount' => 250,
                        'fixedRecipientDenominations' => [25, 50, 100],
                        'logoUrls' => ['https://example.com/logo.png'],
                    ],
                ],
                'orders' => [
                    'GC-ORDER-1' => [
                        'status' => 'SUCCESSFUL',
                        'transactionId' => 'GC-TX-0001',
                        'customIdentifier' => 'GC-ORDER-1|GIFT_CARD',
                        'cards' => [
                            [
                                'code' => 'SANDBOX-CARD-001',
                                'pin' => '123456',
                                'expiresAt' => '2030-01-01',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
