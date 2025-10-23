<?php

return [
    'credentials' => [
        'api_key'    => env('TOPUP_API_KEY'),
        'api_secret' => env('TOPUP_API_SECRET'),
        'provider'   => env('TOPUP_PROVIDER', 'reloadly'),
        'base_uri'   => env('TOPUP_BASE_URI'),
    ],

    'timeout' => [
        'connect'  => env('TOPUP_CONNECT_TIMEOUT', 10),
        'response' => env('TOPUP_RESPONSE_TIMEOUT', 30),
    ],

    'feature_flags' => [
        'enabled'         => env('TOPUP_FEATURE_ENABLED', true),
        'allow_manual'    => env('TOPUP_ALLOW_MANUAL', true),
        'allow_automatic' => env('TOPUP_ALLOW_AUTOMATIC', true),
    ],

    'rate_limit' => [
        'max_attempts'  => env('TOPUP_RATE_LIMIT_ATTEMPTS', 60),
        'decay_seconds' => env('TOPUP_RATE_LIMIT_DECAY', 60),
    ],

    'security' => [
        'failure_alert_threshold' => env('TOPUP_SECURITY_ALERT_THRESHOLD', 5),
        'failure_decay_seconds'   => env('TOPUP_SECURITY_ALERT_DECAY', 900),
        'alert_recipient'         => env('TOPUP_SECURITY_ALERT_RECIPIENT'),
        'alert_cooldown_seconds'  => env('TOPUP_SECURITY_ALERT_COOLDOWN', 900),
    ],

    'metrics' => [
        'enabled'   => env('TOPUP_METRICS_ENABLED', true),
        'namespace' => env('TOPUP_METRICS_NAMESPACE', 'qrpay_topup'),
    ],
];
