<?php

return [
    'providers' => [
        'paypal' => [
            'class' => App\Services\Payments\PaypalPaymentProvider::class,
            'config' => [
                'timeout' => env('PAYPAL_TIMEOUT', 30),
                'base_uri' => env('PAYPAL_BASE_URI'),
            ],
        ],
        'stripe' => [
            'class' => App\Services\Payments\StripePaymentProvider::class,
            'config' => [
                'timeout' => env('STRIPE_TIMEOUT', 30),
                'base_uri' => env('STRIPE_BASE_URI'),
            ],
        ],
        'manual' => [
            'class' => App\Services\Payments\ManualPaymentProvider::class,
            'config' => [],
        ],
        'flutterwave' => [
            'class' => App\Services\Payments\FlutterwavePaymentProvider::class,
            'config' => [
                'timeout' => env('FLUTTERWAVE_TIMEOUT', 30),
                'base_uri' => env('FLUTTERWAVE_BASE_URI'),
            ],
        ],
        'razorpay' => [
            'class' => App\Services\Payments\RazorpayPaymentProvider::class,
            'config' => [
                'timeout' => env('RAZORPAY_TIMEOUT', 30),
                'base_uri' => env('RAZORPAY_BASE_URI'),
            ],
        ],
        'pagadito' => [
            'class' => App\Services\Payments\PagaditoPaymentProvider::class,
            'config' => [
                'timeout' => env('PAGADITO_TIMEOUT', 30),
                'base_uri' => env('PAGADITO_BASE_URI'),
            ],
        ],
        'sslcommerz' => [
            'class' => App\Services\Payments\SslcommerzPaymentProvider::class,
            'config' => [
                'timeout' => env('SSLCOMMERZ_TIMEOUT', 30),
                'base_uri' => env('SSLCOMMERZ_BASE_URI'),
            ],
        ],
        'coingate' => [
            'class' => App\Services\Payments\CoingatePaymentProvider::class,
            'config' => [
                'timeout' => env('COINGATE_TIMEOUT', 30),
                'base_uri' => env('COINGATE_BASE_URI'),
            ],
        ],
        'tatum' => [
            'class' => App\Services\Payments\TatumPaymentProvider::class,
            'config' => [
                'timeout' => env('TATUM_TIMEOUT', 30),
                'base_uri' => env('TATUM_BASE_URI'),
            ],
        ],
        'perfect-money' => [
            'class' => App\Services\Payments\PerfectMoneyPaymentProvider::class,
            'config' => [
                'timeout' => env('PERFECT_MONEY_TIMEOUT', 30),
                'base_uri' => env('PERFECT_MONEY_BASE_URI'),
            ],
        ],
        'paystack' => [
            'class' => App\Services\Payments\PaystackPaymentProvider::class,
            'config' => [
                'timeout' => env('PAYSTACK_TIMEOUT', 30),
                'base_uri' => env('PAYSTACK_BASE_URI'),
            ],
        ],
    ],
];
