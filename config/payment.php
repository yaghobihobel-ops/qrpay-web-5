<?php

return [
    'credentials' => [
        'public_key'  => env('PAYMENT_PUBLIC_KEY'),
        'secret_key'  => env('PAYMENT_SECRET_KEY'),
        'merchant_id' => env('PAYMENT_MERCHANT_ID'),
        'provider'    => env('PAYMENT_PROVIDER', 'stripe'),
        'base_uri'    => env('PAYMENT_BASE_URI'),
    ],

    'timeout' => [
        'connect'  => env('PAYMENT_CONNECT_TIMEOUT', 10),
        'response' => env('PAYMENT_RESPONSE_TIMEOUT', 45),
    ],

    'feature_flags' => [
        'enabled'       => env('PAYMENT_FEATURE_ENABLED', true),
        'allow_qr'      => env('PAYMENT_ALLOW_QR', true),
        'allow_invoice' => env('PAYMENT_ALLOW_INVOICE', true),
    ],

    'rate_limit' => [
        'max_attempts'  => env('PAYMENT_RATE_LIMIT_ATTEMPTS', 120),
        'decay_seconds' => env('PAYMENT_RATE_LIMIT_DECAY', 60),
    ],

    'security' => [
        'failure_alert_threshold' => env('PAYMENT_SECURITY_ALERT_THRESHOLD', 5),
        'failure_decay_seconds'   => env('PAYMENT_SECURITY_ALERT_DECAY', 900),
        'alert_recipient'         => env('PAYMENT_SECURITY_ALERT_RECIPIENT'),
        'alert_cooldown_seconds'  => env('PAYMENT_SECURITY_ALERT_COOLDOWN', 900),
    ],

    'metrics' => [
        'enabled'   => env('PAYMENT_METRICS_ENABLED', true),
        'namespace' => env('PAYMENT_METRICS_NAMESPACE', 'qrpay_payment'),
    ],
];
