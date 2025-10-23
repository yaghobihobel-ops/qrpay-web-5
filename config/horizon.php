<?php

return [
    'domain' => env('HORIZON_DOMAIN'),

    'path' => env('HORIZON_PATH', 'horizon'),

    'prefix' => env('HORIZON_PREFIX', 'horizon:'),

    'middleware' => ['web'],

    'waits' => [
        'redis' => 60,
    ],

    'trim' => [
        'recent' => 60,
        'failed' => 10080,
    ],

    'metrics' => [
        'trim_snapshots' => 24,
    ],

    'defaults' => [
        'supervisor' => [
            'connection' => env('QUEUE_CONNECTION', 'redis'),
            'queue' => ['default'],
            'balance' => 'simple',
            'maxProcesses' => 1,
            'minProcesses' => 1,
            'tries' => 1,
        ],
    ],

    'environments' => [
        'production' => [
            'payments-supervisor' => [
                'connection' => env('QUEUE_PAYMENTS_CONNECTION', 'default'),
                'queue' => [env('QUEUE_PAYMENTS_NAME', 'payments')],
                'balance' => 'auto',
                'maxProcesses' => (int) env('HORIZON_PAYMENTS_MAX_PROCESSES', 10),
                'minProcesses' => (int) env('HORIZON_PAYMENTS_MIN_PROCESSES', 1),
            ],
            'exchange-supervisor' => [
                'connection' => env('QUEUE_EXCHANGE_CONNECTION', 'default'),
                'queue' => [env('QUEUE_EXCHANGE_NAME', 'exchange')],
                'balance' => 'auto',
                'maxProcesses' => (int) env('HORIZON_EXCHANGE_MAX_PROCESSES', 6),
                'minProcesses' => (int) env('HORIZON_EXCHANGE_MIN_PROCESSES', 1),
            ],
            'withdrawals-supervisor' => [
                'connection' => env('QUEUE_WITHDRAWALS_CONNECTION', 'default'),
                'queue' => [env('QUEUE_WITHDRAWALS_NAME', 'withdrawals')],
                'balance' => 'auto',
                'maxProcesses' => (int) env('HORIZON_WITHDRAWALS_MAX_PROCESSES', 6),
                'minProcesses' => (int) env('HORIZON_WITHDRAWALS_MIN_PROCESSES', 1),
            ],
        ],

        'local' => [
            'payments-supervisor' => [
                'connection' => env('QUEUE_PAYMENTS_CONNECTION', 'default'),
                'queue' => [env('QUEUE_PAYMENTS_NAME', 'payments')],
                'balance' => 'simple',
                'maxProcesses' => 2,
                'minProcesses' => 1,
            ],
            'exchange-supervisor' => [
                'connection' => env('QUEUE_EXCHANGE_CONNECTION', 'default'),
                'queue' => [env('QUEUE_EXCHANGE_NAME', 'exchange')],
                'balance' => 'simple',
                'maxProcesses' => 2,
                'minProcesses' => 1,
            ],
            'withdrawals-supervisor' => [
                'connection' => env('QUEUE_WITHDRAWALS_CONNECTION', 'default'),
                'queue' => [env('QUEUE_WITHDRAWALS_NAME', 'withdrawals')],
                'balance' => 'simple',
                'maxProcesses' => 2,
                'minProcesses' => 1,
            ],
        ],
    ],
];
