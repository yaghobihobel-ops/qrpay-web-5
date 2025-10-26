<?php

return [
    'credentials' => [
        'public_key'  => env('CARD_PUBLIC_KEY'),
        'secret_key'  => env('CARD_SECRET_KEY'),
        'provider'    => env('CARD_PROVIDER', 'virtual-card'),
        'base_uri'    => env('CARD_BASE_URI'),
    ],

    'timeout' => [
        'connect'  => env('CARD_CONNECT_TIMEOUT', 10),
        'response' => env('CARD_RESPONSE_TIMEOUT', 45),
    ],

    'feature_flags' => [
        'enabled'         => env('CARD_FEATURE_ENABLED', true),
        'allow_issue'     => env('CARD_ALLOW_ISSUE', true),
        'allow_topup'     => env('CARD_ALLOW_TOPUP', true),
    ],

    'rate_limit' => [
        'max_attempts'  => env('CARD_RATE_LIMIT_ATTEMPTS', 90),
        'decay_seconds' => env('CARD_RATE_LIMIT_DECAY', 60),
    ],

    'security' => [
        'failure_alert_threshold' => env('CARD_SECURITY_ALERT_THRESHOLD', 5),
        'failure_decay_seconds'   => env('CARD_SECURITY_ALERT_DECAY', 900),
        'alert_recipient'         => env('CARD_SECURITY_ALERT_RECIPIENT'),
        'alert_cooldown_seconds'  => env('CARD_SECURITY_ALERT_COOLDOWN', 900),
    ],

    'metrics' => [
        'enabled'   => env('CARD_METRICS_ENABLED', true),
        'namespace' => env('CARD_METRICS_NAMESPACE', 'qrpay_card'),
    ],
];
