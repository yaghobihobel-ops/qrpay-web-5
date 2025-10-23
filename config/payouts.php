<?php

return [
    'feature_flags' => [
        'blubank' => env('FEATURE_PAYOUTS_BLUBANK', false),
        'sandbox' => env('FEATURE_PAYOUTS_SANDBOX', true),
    ],

    'providers' => [
        'blubank' => [
            'merchant_id' => env('BLUBANK_MERCHANT_ID', 'sandbox-merchant'),
            'secret_key' => env('BLUBANK_SECRET_KEY', ''),
            'default_currency' => env('BLUBANK_DEFAULT_CURRENCY', 'KES'),
            'exchange_rates' => [
                'KES' => 1.0,
                'USD' => env('BLUBANK_RATE_USD', 0.0071),
                'EUR' => env('BLUBANK_RATE_EUR', 0.0064),
            ],
            'endpoints' => [
                'wallet' => env('BLUBANK_WALLET_ENDPOINT', 'https://api.blubank.test/wallet'),
                'qr' => env('BLUBANK_QR_ENDPOINT', 'https://api.blubank.test/qr'),
                'bank' => env('BLUBANK_BANK_ENDPOINT', 'https://api.blubank.test/bank'),
                'status' => env('BLUBANK_STATUS_ENDPOINT', 'https://api.blubank.test/status'),
            ],
        ],
    ],

    'sandbox' => [
        'allow_mock_signatures' => env('PAYOUTS_SANDBOX_ALLOW_SIGNATURES', true),
        'default_response_delay' => env('PAYOUTS_SANDBOX_DELAY_MS', 200),
    ],
];
