<?php

return [
    'providers' => [
        'alipay' => [
            'endpoint' => env('HEALTHCHECK_ALIPAY_ENDPOINT', 'https://status.qrpay.example/alipay'),
            'timeout' => env('HEALTHCHECK_ALIPAY_TIMEOUT', 5),
            'latency_warning' => env('HEALTHCHECK_ALIPAY_LATENCY_WARNING', 1200),
            'max_error_rate' => env('HEALTHCHECK_ALIPAY_MAX_ERROR_RATE', 2.5),
            'max_fee' => env('HEALTHCHECK_ALIPAY_MAX_FEE', 1.5),
        ],
        'blubank' => [
            'endpoint' => env('HEALTHCHECK_BLUBANK_ENDPOINT', 'https://status.qrpay.example/blubank'),
            'timeout' => env('HEALTHCHECK_BLUBANK_TIMEOUT', 5),
            'latency_warning' => env('HEALTHCHECK_BLUBANK_LATENCY_WARNING', 1500),
            'max_error_rate' => env('HEALTHCHECK_BLUBANK_MAX_ERROR_RATE', 3.0),
            'max_fee' => env('HEALTHCHECK_BLUBANK_MAX_FEE', 1.2),
        ],
        'yoomonea' => [
            'endpoint' => env('HEALTHCHECK_YOOMONEA_ENDPOINT', 'https://status.qrpay.example/yoomonea'),
            'timeout' => env('HEALTHCHECK_YOOMONEA_TIMEOUT', 5),
            'latency_warning' => env('HEALTHCHECK_YOOMONEA_LATENCY_WARNING', 1500),
            'max_error_rate' => env('HEALTHCHECK_YOOMONEA_MAX_ERROR_RATE', 2.0),
            'max_fee' => env('HEALTHCHECK_YOOMONEA_MAX_FEE', 1.0),
        ],
    ],

    'internal' => [
        'payments_api' => [
            'endpoint' => env('HEALTHCHECK_INTERNAL_PAYMENTS_ENDPOINT', 'https://internal.qrpay.example/payments/health'),
            'timeout' => env('HEALTHCHECK_INTERNAL_PAYMENTS_TIMEOUT', 3),
            'latency_warning' => env('HEALTHCHECK_INTERNAL_PAYMENTS_LATENCY_WARNING', 800),
        ],
        'ledger_service' => [
            'endpoint' => env('HEALTHCHECK_INTERNAL_LEDGER_ENDPOINT', 'https://internal.qrpay.example/ledger/health'),
            'timeout' => env('HEALTHCHECK_INTERNAL_LEDGER_TIMEOUT', 3),
            'latency_warning' => env('HEALTHCHECK_INTERNAL_LEDGER_LATENCY_WARNING', 800),
        ],
        'notification_bus' => [
            'endpoint' => env('HEALTHCHECK_INTERNAL_NOTIFICATION_ENDPOINT', 'https://internal.qrpay.example/notification/health'),
            'timeout' => env('HEALTHCHECK_INTERNAL_NOTIFICATION_TIMEOUT', 3),
            'latency_warning' => env('HEALTHCHECK_INTERNAL_NOTIFICATION_LATENCY_WARNING', 600),
        ],
    ],

    'alerts' => [
        'slack_webhook' => env('HEALTHCHECK_SLACK_WEBHOOK'),
        'emails' => env('HEALTHCHECK_ALERT_EMAILS'),
        'on_call' => env('HEALTHCHECK_ON_CALL_EMAILS'),
    ],
];
