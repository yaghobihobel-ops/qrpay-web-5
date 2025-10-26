<?php

return [
    'rate_limits' => [
        'services' => [
            'default' => [
                'per_user' => [
                    'max_attempts' => (int) env('API_RATE_LIMIT_PER_USER', 120),
                    'decay_minutes' => (int) env('API_RATE_LIMIT_PER_USER_DECAY', 1),
                ],
                'per_ip' => [
                    'max_attempts' => (int) env('API_RATE_LIMIT_PER_IP', 240),
                    'decay_minutes' => (int) env('API_RATE_LIMIT_PER_IP_DECAY', 1),
                ],
            ],
            'payment' => [
                'per_user' => [
                    'max_attempts' => (int) env('API_RATE_LIMIT_PAYMENT_PER_USER', 60),
                    'decay_minutes' => (int) env('API_RATE_LIMIT_PAYMENT_PER_USER_DECAY', 1),
                ],
                'per_ip' => [
                    'max_attempts' => (int) env('API_RATE_LIMIT_PAYMENT_PER_IP', 120),
                    'decay_minutes' => (int) env('API_RATE_LIMIT_PAYMENT_PER_IP_DECAY', 1),
                ],
            ],
            'withdrawal' => [
                'per_user' => [
                    'max_attempts' => (int) env('API_RATE_LIMIT_WITHDRAWAL_PER_USER', 45),
                    'decay_minutes' => (int) env('API_RATE_LIMIT_WITHDRAWAL_PER_USER_DECAY', 1),
                ],
                'per_ip' => [
                    'max_attempts' => (int) env('API_RATE_LIMIT_WITHDRAWAL_PER_IP', 90),
                    'decay_minutes' => (int) env('API_RATE_LIMIT_WITHDRAWAL_PER_IP_DECAY', 1),
                ],
            ],
            'exchange' => [
                'per_user' => [
                    'max_attempts' => (int) env('API_RATE_LIMIT_EXCHANGE_PER_USER', 75),
                    'decay_minutes' => (int) env('API_RATE_LIMIT_EXCHANGE_PER_USER_DECAY', 1),
                ],
                'per_ip' => [
                    'max_attempts' => (int) env('API_RATE_LIMIT_EXCHANGE_PER_IP', 150),
                    'decay_minutes' => (int) env('API_RATE_LIMIT_EXCHANGE_PER_IP_DECAY', 1),
                ],
            ],
        ],
    ],

    'service_circuits' => [
        'payment' => 'psp',
        'withdrawal' => 'psp',
        'exchange' => 'kyc',
    ],

    'circuit_breaker' => [
        'default' => [
            'failure_threshold' => (int) env('CIRCUIT_BREAKER_FAILURE_THRESHOLD', 5),
            'retry_timeout' => (int) env('CIRCUIT_BREAKER_RETRY_TIMEOUT', 60),
            'decay_seconds' => (int) env('CIRCUIT_BREAKER_DECAY_SECONDS', 300),
        ],
        'psp' => [
            'failure_threshold' => (int) env('CIRCUIT_BREAKER_PSP_FAILURE_THRESHOLD', 5),
            'retry_timeout' => (int) env('CIRCUIT_BREAKER_PSP_RETRY_TIMEOUT', 60),
            'decay_seconds' => (int) env('CIRCUIT_BREAKER_PSP_DECAY_SECONDS', 300),
        ],
        'kyc' => [
            'failure_threshold' => (int) env('CIRCUIT_BREAKER_KYC_FAILURE_THRESHOLD', 3),
            'retry_timeout' => (int) env('CIRCUIT_BREAKER_KYC_RETRY_TIMEOUT', 120),
            'decay_seconds' => (int) env('CIRCUIT_BREAKER_KYC_DECAY_SECONDS', 600),
        ],
    ],

    'alerts' => [
        'log_channel' => env('THROTTLE_ALERT_LOG_CHANNEL', env('LOG_CHANNEL', 'stack')),
        'slack_webhook' => env('THROTTLE_SLACK_WEBHOOK'),
        'email' => [
            'recipients' => array_filter(array_map('trim', explode(',', env('THROTTLE_ALERT_EMAILS', '')))),
        ],
    ],
];
