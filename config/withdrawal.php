<?php

return [
    'credentials' => [
        'public_key'  => env('WITHDRAWAL_PUBLIC_KEY'),
        'secret_key'  => env('WITHDRAWAL_SECRET_KEY'),
        'merchant_id' => env('WITHDRAWAL_MERCHANT_ID'),
        'provider'    => env('WITHDRAWAL_PROVIDER', 'flutterwave'),
        'base_uri'    => env('WITHDRAWAL_BASE_URI'),
    ],

    'timeout' => [
        'connect'  => env('WITHDRAWAL_CONNECT_TIMEOUT', 10),
        'response' => env('WITHDRAWAL_RESPONSE_TIMEOUT', 45),
    ],

    'feature_flags' => [
        'enabled'       => env('WITHDRAWAL_FEATURE_ENABLED', true),
        'allow_manual'  => env('WITHDRAWAL_ALLOW_MANUAL', true),
        'allow_auto'    => env('WITHDRAWAL_ALLOW_AUTOMATIC', true),
    ],

    'rate_limit' => [
        'max_attempts'  => env('WITHDRAWAL_RATE_LIMIT_ATTEMPTS', 60),
        'decay_seconds' => env('WITHDRAWAL_RATE_LIMIT_DECAY', 120),
    ],

    'security' => [
        'failure_alert_threshold' => env('WITHDRAWAL_SECURITY_ALERT_THRESHOLD', 5),
        'failure_decay_seconds'   => env('WITHDRAWAL_SECURITY_ALERT_DECAY', 900),
        'alert_recipient'         => env('WITHDRAWAL_SECURITY_ALERT_RECIPIENT'),
        'alert_cooldown_seconds'  => env('WITHDRAWAL_SECURITY_ALERT_COOLDOWN', 900),
    ],

    'metrics' => [
        'enabled'   => env('WITHDRAWAL_METRICS_ENABLED', true),
        'namespace' => env('WITHDRAWAL_METRICS_NAMESPACE', 'qrpay_withdrawal'),
    ],
];
