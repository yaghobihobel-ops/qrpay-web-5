<?php

return [
    'rate_provider' => [
        'driver' => env('PRICING_RATE_DRIVER', 'exchangerate_host'),
        'base_url' => env('PRICING_RATE_BASE_URL', 'https://api.exchangerate.host/latest'),
        'api_key' => env('PRICING_RATE_API_KEY'),
        'api_key_parameter' => env('PRICING_RATE_API_KEY_PARAMETER', 'access_key'),
        'timeout' => env('PRICING_RATE_TIMEOUT', 5),
        'additional_parameters' => [
            'places' => env('PRICING_RATE_DECIMALS', 8),
        ],
    ],
    'scenario_rates' => [
        // 'IRR' => ['USD' => 0.000023],
    ],
    'cache_ttl' => env('PRICING_RATE_CACHE_TTL', 300),
];
