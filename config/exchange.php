<?php

use App\Services\Exchange\ChinaFxService;
use App\Services\Exchange\IranExchangeService;
use App\Services\Exchange\RussiaFxService;
use App\Services\Exchange\TurkeyFxService;

return [
    'cache' => true,
    'cache_ttl' => env('EXCHANGE_CACHE_TTL', 3600),
    'cache_store' => env('EXCHANGE_CACHE_STORE'),
    'default_base_currency' => env('EXCHANGE_BASE_CURRENCY', 'USD'),
    'default_symbols' => array_values(array_filter(array_map('trim', explode(',', env('EXCHANGE_DEFAULT_SYMBOLS', 'USD,EUR,IRR,CNY,TRY,RUB'))))),

    'fallback_order' => [
        'nima',
        'pboc',
        'tcmb',
        'cbr',
    ],

    'providers' => [
        'nima' => [
            'class' => IranExchangeService::class,
            'base_url' => env('NIMA_BASE_URL', 'https://api.nima.ir'),
            'endpoints' => [
                'rates' => env('NIMA_RATES_ENDPOINT', '/v1/rates'),
            ],
            'response_path' => env('NIMA_RESPONSE_PATH', 'data.rates'),
            'market' => env('NIMA_MARKET', 'nima'),
        ],
        'pboc' => [
            'class' => ChinaFxService::class,
            'base_url' => env('PBOC_BASE_URL', 'https://api.pboc.cn'),
            'endpoints' => [
                'rates' => env('PBOC_RATES_ENDPOINT', '/v1/exchange'),
            ],
            'response_path' => env('PBOC_RESPONSE_PATH', 'data.items'),
            'token' => env('PBOC_TOKEN'),
        ],
        'tcmb' => [
            'class' => TurkeyFxService::class,
            'base_url' => env('TCMB_BASE_URL', 'https://api.tcmb.gov.tr'),
            'endpoints' => [
                'rates' => env('TCMB_RATES_ENDPOINT', '/v1/rates'),
            ],
            'response_path' => env('TCMB_RESPONSE_PATH', 'data'),
        ],
        'cbr' => [
            'class' => RussiaFxService::class,
            'base_url' => env('CBR_BASE_URL', 'https://www.cbr.ru'),
            'endpoints' => [
                'rates' => env('CBR_RATES_ENDPOINT', '/v1/rates'),
            ],
            'response_path' => env('CBR_RESPONSE_PATH', 'rates'),
        ],
    ],
];
