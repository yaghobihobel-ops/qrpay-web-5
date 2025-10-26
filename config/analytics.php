<?php

return [
    'default_connection' => env('ANALYTICS_CONNECTION', 'bigquery'),

    'connections' => [
        'bigquery' => [
            'enabled' => env('ANALYTICS_BIGQUERY_ENABLED', true),
            'endpoint' => env('ANALYTICS_BIGQUERY_ENDPOINT'),
            'project' => env('ANALYTICS_BIGQUERY_PROJECT'),
            'dataset' => env('ANALYTICS_BIGQUERY_DATASET'),
            'table' => env('ANALYTICS_BIGQUERY_TABLE'),
            'service_account_path' => env('ANALYTICS_BIGQUERY_SERVICE_ACCOUNT'),
        ],

        'clickhouse' => [
            'enabled' => env('ANALYTICS_CLICKHOUSE_ENABLED', false),
            'host' => env('ANALYTICS_CLICKHOUSE_HOST'),
            'port' => env('ANALYTICS_CLICKHOUSE_PORT', 8123),
            'username' => env('ANALYTICS_CLICKHOUSE_USERNAME'),
            'password' => env('ANALYTICS_CLICKHOUSE_PASSWORD'),
            'database' => env('ANALYTICS_CLICKHOUSE_DATABASE'),
            'table' => env('ANALYTICS_CLICKHOUSE_TABLE'),
        ],
    ],

    'buffer_path' => storage_path('app/analytics-buffer.ndjson'),
];
