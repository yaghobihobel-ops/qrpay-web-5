<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Headers
    |--------------------------------------------------------------------------
    | These headers are used to extract webhook metadata when provider specific
    | overrides are not supplied. They can be customised per provider in the
    | configuration map below.
    */
    'signature_header' => env('WEBHOOK_SIGNATURE_HEADER', 'X-Signature'),
    'timestamp_header' => env('WEBHOOK_TIMESTAMP_HEADER', 'X-Signature-Timestamp'),
    'idempotency_header' => env('WEBHOOK_IDEMPOTENCY_HEADER', 'X-Idempotency-Key'),
    'default_algorithm' => env('WEBHOOK_DEFAULT_ALGORITHM', 'sha256'),

    /*
    |--------------------------------------------------------------------------
    | Provider Specific Secrets
    |--------------------------------------------------------------------------
    | Define HMAC secrets and optional header overrides for each country,
    | channel and provider key. Secrets should be stored in the environment.
    */
    'providers' => [
        'IR' => [
            'payment' => [
                'ir-mock-payment' => [
                    'secret' => env('WEBHOOK_SECRET_IR_PAYMENT'),
                ],
            ],
        ],
        'CN' => [
            'fx' => [
                'cn-mock-fx' => [
                    'secret' => env('WEBHOOK_SECRET_CN_FX'),
                ],
            ],
        ],
        'TR' => [
            'topup' => [
                'tr-mock-topup' => [
                    'secret' => env('WEBHOOK_SECRET_TR_TOPUP'),
                ],
            ],
        ],
    ],
];
