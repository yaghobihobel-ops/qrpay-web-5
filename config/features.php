<?php

return [
    'features' => [
        'alipay_onboarding' => [
            'enabled' => env('FEATURE_ALIPAY_ONBOARDING', false),
            'canary' => [
                'enabled' => env('CANARY_ALIPAY_ONBOARDING', false),
                'percentage' => env('CANARY_ALIPAY_ONBOARDING_PERCENT', 10),
                'identifier' => env('CANARY_ALIPAY_IDENTIFIER', 'id'),
            ],
        ],
        'blubank_payouts' => [
            'enabled' => env('FEATURE_BLUBANK_PAYOUTS', false),
            'canary' => [
                'enabled' => env('CANARY_BLUBANK_PAYOUTS', false),
                'percentage' => env('CANARY_BLUBANK_PAYOUTS_PERCENT', 5),
                'identifier' => env('CANARY_BLUBANK_IDENTIFIER', 'id'),
            ],
        ],
        'yoomonea_collections' => [
            'enabled' => env('FEATURE_YOOMONEA_COLLECTIONS', false),
            'canary' => [
                'enabled' => env('CANARY_YOOMONEA_COLLECTIONS', false),
                'percentage' => env('CANARY_YOOMONEA_COLLECTIONS_PERCENT', 5),
                'identifier' => env('CANARY_YOOMONEA_IDENTIFIER', 'id'),
            ],
        ],
    ],
];
