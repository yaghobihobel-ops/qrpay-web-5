<?php

return [
    'http' => [
        'timeout' => env('EXCHANGE_HTTP_TIMEOUT', 10),
        'retry_times' => env('EXCHANGE_HTTP_RETRY_TIMES', 3),
        'retry_sleep' => env('EXCHANGE_HTTP_RETRY_SLEEP', 500),
    ],
    'cache' => [
        'ttl' => env('EXCHANGE_CACHE_TTL', 3600),
        'latest_rates_key' => 'exchange:rates:latest',
        'fallback_rates_key' => 'exchange:rates:currency-layer:last-success',
        'supported_currencies_key' => 'exchange:rates:currency-layer:supported-currencies',
    ],
];
