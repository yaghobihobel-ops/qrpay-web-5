<?php

return [
    'feature_flags' => [
        'alipay' => env('FEATURE_PAYMENTS_ALIPAY', false),
        'yoomonea' => env('FEATURE_PAYMENTS_YOOMONEA', false),
        'sandbox' => env('FEATURE_PAYMENTS_SANDBOX', true),
    ],

    'providers' => [
        'alipay' => [
            'app_id' => env('ALIPAY_APP_ID', 'sandbox-app'),
            'public_key' => env('ALIPAY_PUBLIC_KEY', ''),
            'private_key' => env('ALIPAY_PRIVATE_KEY', ''),
            'default_currency' => env('ALIPAY_DEFAULT_CURRENCY', 'CNY'),
            'exchange_rates' => [
                'USD' => env('ALIPAY_RATE_USD', 7.1),
                'EUR' => env('ALIPAY_RATE_EUR', 7.6),
                'CNY' => 1.0,
            ],
            'endpoints' => [
                'wallet' => env('ALIPAY_WALLET_ENDPOINT', 'https://openapi.alipay.com/wallet'),
                'qr' => env('ALIPAY_QR_ENDPOINT', 'https://openapi.alipay.com/qr'),
                'bank' => env('ALIPAY_BANK_ENDPOINT', 'https://openapi.alipay.com/bank'),
            ],
        ],

        'yoomonea' => [
            'client_id' => env('YOOMONEA_CLIENT_ID', 'sandbox-client'),
            'client_secret' => env('YOOMONEA_CLIENT_SECRET', ''),
            'default_currency' => env('YOOMONEA_DEFAULT_CURRENCY', 'XOF'),
            'exchange_rates' => [
                'XOF' => 1.0,
                'USD' => env('YOOMONEA_RATE_USD', 0.0016),
                'EUR' => env('YOOMONEA_RATE_EUR', 0.0015),
            ],
            'endpoints' => [
                'wallet' => env('YOOMONEA_WALLET_ENDPOINT', 'https://api.yoomonea.test/wallet'),
                'qr' => env('YOOMONEA_QR_ENDPOINT', 'https://api.yoomonea.test/qr'),
                'bank' => env('YOOMONEA_BANK_ENDPOINT', 'https://api.yoomonea.test/bank'),
            ],
        ],
    ],

    'sandbox' => [
        'allow_mock_signatures' => env('PAYMENTS_SANDBOX_ALLOW_SIGNATURES', true),
        'default_response_delay' => env('PAYMENTS_SANDBOX_DELAY_MS', 150),
    ],
];
