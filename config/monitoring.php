<?php

return [
    'defaults' => [
        'timeout' => env('HEALTH_CHECK_TIMEOUT', 5),
        'latency_threshold' => env('HEALTH_CHECK_DEFAULT_LATENCY', 1000),
        'history_limit' => env('HEALTH_CHECK_HISTORY_LIMIT', 50),
    ],

    'providers' => [
        // Example provider definition. Override in .env or config cache as needed.
        [
            'name' => 'Application',
            'slug' => 'app',
            'url' => env('APP_HEALTH_URL'),
            'method' => 'GET',
            'timeout' => env('APP_HEALTH_TIMEOUT', 5),
            'latency_threshold' => env('APP_HEALTH_LATENCY_THRESHOLD', 1000),
        ],
    ],

    'alerts' => [
        'mail' => [
            'enabled' => env('HEALTH_ALERT_MAIL_ENABLED', false),
            'recipients' => array_filter(array_map('trim', explode(',', (string) env('HEALTH_ALERT_MAIL_TO')))),
        ],
        'slack' => [
            'enabled' => env('HEALTH_ALERT_SLACK_ENABLED', false),
            'webhook_url' => env('HEALTH_ALERT_SLACK_WEBHOOK'),
        ],
        'webhook' => [
            'enabled' => env('HEALTH_ALERT_WEBHOOK_ENABLED', false),
            'url' => env('HEALTH_ALERT_WEBHOOK_URL'),
            'secret' => env('HEALTH_ALERT_WEBHOOK_SECRET'),
        ],
    ],
];
