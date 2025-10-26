<?php

return [
    'feature_flags' => [
        'alipay' => filter_var(env('FEATURE_PAYMENTS_ALIPAY', false), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
        'yoomonea' => filter_var(env('FEATURE_PAYMENTS_YOOMONEA', false), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
        'sandbox' => filter_var(env('FEATURE_PAYMENTS_SANDBOX', true), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true,
    ],

return [
    'regional_providers' => [
        'iran_crypto' => [
            'name' => 'Iran Crypto Gateway',
            'class' => IranCryptoGateway::class,
            'currency' => 'IRR',
            'api_url' => env('PAY_IRAN_CRYPTO_API_URL', 'https://api.example.ir/payments'),
            'api_key' => env('PAY_IRAN_CRYPTO_API_KEY'),
            'merchant_id' => env('PAY_IRAN_CRYPTO_MERCHANT_ID'),
            'network' => env('PAY_IRAN_CRYPTO_NETWORK', 'mainnet'),
            'settlement_account' => env('PAY_IRAN_CRYPTO_SETTLEMENT_ACCOUNT'),
            'callback_url' => env('PAY_IRAN_CRYPTO_CALLBACK_URL'),
        ],
        'china_mainland' => [
            'name' => 'Mainland China Gateway',
            'class' => CnyGateway::class,
            'currency' => 'CNY',
            'api_url' => env('PAY_CNY_API_URL', 'https://cn.example.com/api'),
            'api_key' => env('PAY_CNY_API_KEY'),
            'merchant_id' => env('PAY_CNY_MERCHANT_ID'),
            'connector_bank' => env('PAY_CNY_CONNECTOR_BANK'),
            'callback_url' => env('PAY_CNY_CALLBACK_URL'),
        ],
        'turkey_bank' => [
            'name' => 'Turkey Banking Gateway',
            'class' => TryGateway::class,
            'currency' => 'TRY',
            'api_url' => env('PAY_TRY_API_URL', 'https://tr.example.com/api'),
            'api_key' => env('PAY_TRY_API_KEY'),
            'merchant_id' => env('PAY_TRY_MERCHANT_ID'),
            'network' => env('PAY_TRY_INTERBANK_NETWORK', 'eft'),
            'connector_bank' => env('PAY_TRY_CORRESPONDENT_BANK'),
            'callback_url' => env('PAY_TRY_CALLBACK_URL'),
        ],
        'russia_settlement' => [
            'name' => 'Russia Settlement Gateway',
            'class' => RubGateway::class,
            'currency' => 'RUB',
            'api_url' => env('PAY_RUB_API_URL', 'https://ru.example.com/api'),
            'api_key' => env('PAY_RUB_API_KEY'),
            'merchant_id' => env('PAY_RUB_MERCHANT_ID'),
            'connector_bank' => env('PAY_RUB_CORRESPONDENT_BANK'),
            'callback_url' => env('PAY_RUB_CALLBACK_URL'),
        ],
    ],

    'sandbox' => [
        'allow_mock_signatures' => filter_var(env('PAYMENTS_SANDBOX_ALLOW_SIGNATURES', true), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true,
        'default_response_delay' => env('PAYMENTS_SANDBOX_DELAY_MS', 150),
    ],
];
