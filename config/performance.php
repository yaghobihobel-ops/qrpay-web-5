<?php

return [
    'cache' => [
        'exchange_rates_ttl' => (int) env('CACHE_TTL_EXCHANGE_RATES', 600),
        'provider_settings_ttl' => (int) env('CACHE_TTL_PROVIDER_SETTINGS', 1800),
        'account_summary_ttl' => (int) env('CACHE_TTL_ACCOUNT_SUMMARY', 300),
    ],
    'database' => [
        'reporting_connection' => env('DB_REPORTING_CONNECTION', 'mysql_reporting'),
        'slow_query_threshold_ms' => (int) env('DB_SLOW_QUERY_THRESHOLD', 250),
    ],
];
