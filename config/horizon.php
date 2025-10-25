<?php

return [
    'domain' => env('HORIZON_DOMAIN'),

    'path' => env('HORIZON_PATH', 'horizon'),

    'use' => 'redis',

    'prefix' => env('HORIZON_PREFIX', 'horizon'),

    'middleware' => ['web'],

    'queue_with_delayed_job_output' => true,

    'waits' => [
        'redis:bill-payments' => 120,
        'redis:notifications' => 90,
        'redis:reports' => 300,
    ],

    'trim' => [
        'recent' => 60,
        'pending' => 60,
        'completed' => 60,
        'recent_failed' => 10080,
        'failed' => 10080,
        'monitored' => 10080,
    ],

    'metrics' => [
        'trim_snapshots' => 24,
    ],

    'fast_termination' => false,

    'memory_limit' => 128,

    'environments' => [
        'production' => [
            'bill-payments' => [
                'connection' => 'redis',
                'queue' => ['bill-payments'],
                'balance' => 'auto',
                'minProcesses' => 1,
                'maxProcesses' => 10,
                'tries' => 3,
            ],
            'notifications' => [
                'connection' => 'redis',
                'queue' => ['notifications'],
                'balance' => 'auto',
                'minProcesses' => 1,
                'maxProcesses' => 5,
                'tries' => 3,
            ],
            'reports' => [
                'connection' => 'redis',
                'queue' => ['reports'],
                'balance' => 'auto',
                'minProcesses' => 1,
                'maxProcesses' => 2,
                'tries' => 3,
            ],
        ],

        'local' => [
            'bill-payments' => [
                'connection' => 'redis',
                'queue' => ['bill-payments'],
                'balance' => 'simple',
                'minProcesses' => 1,
                'maxProcesses' => 3,
                'tries' => 3,
            ],
            'notifications' => [
                'connection' => 'redis',
                'queue' => ['notifications'],
                'balance' => 'simple',
                'minProcesses' => 1,
                'maxProcesses' => 2,
                'tries' => 3,
            ],
            'reports' => [
                'connection' => 'redis',
                'queue' => ['reports'],
                'balance' => 'simple',
                'minProcesses' => 1,
                'maxProcesses' => 1,
                'tries' => 3,
            ],
        ],

        'testing' => [
            'bill-payments' => [
                'connection' => 'redis',
                'queue' => ['bill-payments'],
                'balance' => 'simple',
                'minProcesses' => 1,
                'maxProcesses' => 1,
                'tries' => 1,
            ],
            'notifications' => [
                'connection' => 'redis',
                'queue' => ['notifications'],
                'balance' => 'simple',
                'minProcesses' => 1,
                'maxProcesses' => 1,
                'tries' => 1,
            ],
            'reports' => [
                'connection' => 'redis',
                'queue' => ['reports'],
                'balance' => 'simple',
                'minProcesses' => 1,
                'maxProcesses' => 1,
                'tries' => 1,
            ],
        ],
    ],
];
