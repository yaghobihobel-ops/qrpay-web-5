<?php

return [
    'geo' => [
        'default_region' => env('EDGE_DEFAULT_REGION', 'singapore'),
        'country_overrides' => [
            'IR' => 'istanbul',
            'TR' => 'istanbul',
            'AZ' => 'istanbul',
            'QA' => 'singapore',
            'AE' => 'singapore',
            'RU' => 'moscow',
            'KZ' => 'moscow',
            'CN' => 'singapore',
            'ID' => 'singapore',
            'MY' => 'singapore',
        ],
        'regions' => [
            'singapore' => [
                'code' => 'ap-southeast',
                'name' => 'Singapore Edge',
                'endpoint' => env('EDGE_REGION_SINGAPORE_URL', 'https://edge-sg.qrpay.local'),
                'coordinates' => ['lat' => 1.3521, 'lon' => 103.8198],
            ],
            'istanbul' => [
                'code' => 'eu-east',
                'name' => 'Istanbul Edge',
                'endpoint' => env('EDGE_REGION_ISTANBUL_URL', 'https://edge-ist.qrpay.local'),
                'coordinates' => ['lat' => 41.0082, 'lon' => 28.9784],
            ],
            'moscow' => [
                'code' => 'ru-central',
                'name' => 'Moscow Edge',
                'endpoint' => env('EDGE_REGION_MOSCOW_URL', 'https://edge-msk.qrpay.local'),
                'coordinates' => ['lat' => 55.7558, 'lon' => 37.6173],
            ],
        ],
        'country_header' => env('EDGE_COUNTRY_HEADER', 'X-User-Country'),
        'override_header' => env('EDGE_REGION_OVERRIDE_HEADER', 'X-Edge-Region'),
    ],
    'cache' => [
        'store' => env('EDGE_CACHE_STORE', env('CACHE_STORE', 'redis')),
        'prefix' => env('EDGE_CACHE_PREFIX', 'edge:qrpay'),
        'ttl' => [
            'banks' => (int) env('EDGE_CACHE_TTL_BANKS', 600),
            'rates' => (int) env('EDGE_CACHE_TTL_RATES', 300),
            'settings' => (int) env('EDGE_CACHE_TTL_SETTINGS', 900),
        ],
        'invalidation_channel' => env('EDGE_CACHE_INVALIDATION_CHANNEL', 'edge-cache:invalidate'),
    ],
];
