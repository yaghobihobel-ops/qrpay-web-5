<?php

return [
    'driver' => env('EVENT_STREAM_DRIVER', 'log'),

    'schema_version' => env('EVENT_STREAM_SCHEMA_VERSION', '1.0.0'),

    'default_destination' => env('EVENT_STREAM_DEFAULT_DESTINATION', 'qrpay.transactions'),

    'async' => filter_var(env('EVENT_STREAM_ASYNC', true), FILTER_VALIDATE_BOOLEAN),

    'queue' => env('EVENT_STREAM_QUEUE', 'event-stream'),

    'kafka' => [
        'brokers' => env('EVENT_STREAM_KAFKA_BROKERS', 'localhost:9092'),
        'security_protocol' => env('EVENT_STREAM_KAFKA_SECURITY_PROTOCOL', 'PLAINTEXT'),
        'sasl' => [
            'username' => env('EVENT_STREAM_KAFKA_SASL_USERNAME'),
            'password' => env('EVENT_STREAM_KAFKA_SASL_PASSWORD'),
            'mechanism' => env('EVENT_STREAM_KAFKA_SASL_MECHANISM'),
        ],
        'options' => [
            'acks' => env('EVENT_STREAM_KAFKA_ACKS', 'all'),
            'compression.type' => env('EVENT_STREAM_KAFKA_COMPRESSION', 'gzip'),
        ],
    ],

    'nats' => [
        'url' => env('EVENT_STREAM_NATS_URL', 'nats://127.0.0.1:4222'),
        'user' => env('EVENT_STREAM_NATS_USER'),
        'pass' => env('EVENT_STREAM_NATS_PASS'),
        'options' => [
            'timeout' => (int) env('EVENT_STREAM_NATS_TIMEOUT', 2),
            'max_reconnect' => (int) env('EVENT_STREAM_NATS_MAX_RECONNECT', 10),
        ],
    ],

    'log' => [
        'channel' => env('EVENT_STREAM_LOG_CHANNEL', 'stack'),
        'path' => env('EVENT_STREAM_LOG_PATH', 'event-stream'),
        'disk' => env('EVENT_STREAM_LOG_DISK', 'local'),
    ],

    'events' => [
        'transactions.payment.completed' => [
            'destination' => env('EVENT_STREAM_PAYMENTS_TOPIC', 'qrpay.transactions.payments'),
        ],
        'transactions.exchange.completed' => [
            'destination' => env('EVENT_STREAM_EXCHANGES_TOPIC', 'qrpay.transactions.exchanges'),
        ],
        'transactions.withdrawal.completed' => [
            'destination' => env('EVENT_STREAM_WITHDRAWALS_TOPIC', 'qrpay.transactions.withdrawals'),
        ],
    ],
];
