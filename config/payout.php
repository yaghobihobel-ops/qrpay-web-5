<?php

return [
    'providers' => [
        'flutterwave' => [
            'base_url' => env('FLUTTERWAVE_PAYOUT_BASE_URL', 'https://api.flutterwave.com/v3'),
            'secret_key' => env('FLUTTERWAVE_PAYOUT_SECRET'),
        ],
    ],
    'http' => [
        'timeout' => (int) env('PAYOUT_HTTP_TIMEOUT', 15),
        'retry' => [
            'times' => (int) env('PAYOUT_HTTP_RETRY_TIMES', 3),
            'sleep' => (int) env('PAYOUT_HTTP_RETRY_SLEEP', 200),
        ],
    ],
];
