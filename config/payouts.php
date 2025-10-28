<?php

return [
    'feature_flags' => [
        'blubank' => filter_var(env('FEATURE_PAYOUTS_BLUBANK', false), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
        'sandbox' => filter_var(env('FEATURE_PAYOUTS_SANDBOX', true), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true,
    ],
    'countries' => [
        'CN' => [
            'currency' => 'CNY',
            'providers' => [
                'bank_transfer' => [
                    'class' => ChinaBankTransferService::class,
                    'fee' => 0.0125,
                    'limits' => [
                        'min' => 500,
                        'max' => 50000,
                    ],
                    'banks' => [
                        [
                            'name' => 'Industrial and Commercial Bank of China',
                            'code' => 'ICBC',
                            'swift' => 'ICBKCNBJ',
                        ],
                        [
                            'name' => 'Bank of China',
                            'code' => 'BOC',
                            'swift' => 'BKCHCNBJ',
                        ],
                        [
                            'name' => 'China Construction Bank',
                            'code' => 'CCB',
                            'swift' => 'PCBCCNBJ',
                        ],
                    ],
                ],
            ],
        ],
        'TR' => [
            'currency' => 'TRY',
            'providers' => [
                'bank_transfer' => [
                    'class' => TurkeyBankTransferService::class,
                    'fee' => 35.0,
                    'limits' => [
                        'min' => 100,
                        'max' => 250000,
                    ],
                    'banks' => [
                        [
                            'name' => 'Türkiye İş Bankası',
                            'iban_prefix' => 'TR33',
                            'code' => 'ISBKTRIS',
                        ],
                        [
                            'name' => 'Garanti BBVA',
                            'iban_prefix' => 'TR94',
                            'code' => 'TGBATRIS',
                        ],
                        [
                            'name' => 'Akbank',
                            'iban_prefix' => 'TR84',
                            'code' => 'AKBKTRIS',
                        ],
                    ],
                ],
            ],
        ],
        'RU' => [
            'currency' => 'RUB',
            'providers' => [
                'bank_transfer' => [
                    'class' => RussiaBankTransferService::class,
                    'fee' => 0.01,
                    'limits' => [
                        'min' => 1000,
                        'max' => 1000000,
                    ],
                    'banks' => [
                        [
                            'name' => 'Sberbank',
                            'code' => 'SABRRUMM',
                        ],
                        [
                            'name' => 'VTB Bank',
                            'code' => 'VTBRRUMM',
                        ],
                        [
                            'name' => 'Alfa Bank',
                            'code' => 'ALFARUMM',
                        ],
                    ],
                ],
            ],
        ],
        'IR' => [
            'currency' => 'IRR',
            'providers' => [
                'crypto' => [
                    'class' => IranCryptoPayoutService::class,
                    'fee' => 0.02,
                    'limits' => [
                        'min' => 50,
                        'max' => 25000,
                    ],
                    'networks' => [
                        'USDT' => [
                            'networks' => ['TRC20', 'ERC20'],
                            'fee' => 0.015,
                        ],
                        'USDC' => [
                            'networks' => ['TRC20'],
                            'fee' => 0.01,
                        ],
                        'BTC' => [
                            'networks' => ['BTC'],
                            'fee' => 0.0005,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'sandbox' => [
        'allow_mock_signatures' => filter_var(env('PAYOUTS_SANDBOX_ALLOW_SIGNATURES', true), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true,
        'default_response_delay' => env('PAYOUTS_SANDBOX_DELAY_MS', 200),
    ],
];
