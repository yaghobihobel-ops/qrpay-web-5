<?php

return [
    'fallback_locale' => config('app.fallback_locale', 'en'),
    'locale_map' => [
        'CN' => 'zh',
        'CHINA' => 'zh',
        'PEOPLESREPUBLICOFCHINA' => 'zh',
        'IR' => 'fa',
        'IRN' => 'fa',
        'IRAN' => 'fa',
        'ISLAMICREPUBLICOFIRAN' => 'fa',
        'RU' => 'ru',
        'RUS' => 'ru',
        'RUSSIA' => 'ru',
        'RUSSIANFEDERATION' => 'ru',
        'DEFAULT' => 'en',
    ],
    'country_aliases' => [
        'CHINA' => 'CN',
        'PEOPLESREPUBLICOFCHINA' => 'CN',
        'IRAN' => 'IR',
        'ISLAMICREPUBLICOFIRAN' => 'IR',
        'RUSSIANFEDERATION' => 'RU',
        'RUSSIA' => 'RU',
    ],
    'country_rules' => [
        'CN' => [
            'sms' => [
                'signature' => '【QRPay】',
                'suffix' => '遵循中国人民银行监管要求。',
                'quiet_hours' => '21:00-08:00 CST',
            ],
            'push' => [
                'suffix' => '遵循中国人民银行监管要求。',
                'legal_reference' => 'PBOC-compliant',
            ],
            'email' => [
                'footer' => '此通知符合中国《个人信息保护法》。',
            ],
        ],
        'IR' => [
            'sms' => [
                'signature' => 'QRPay',
                'suffix' => 'مطابق دستورالعمل‌های بانک مرکزی ایران.',
            ],
            'push' => [
                'suffix' => 'مطابق مقررات بانک مرکزی ایران.',
            ],
            'email' => [
                'footer' => 'این پیام مطابق با ضوابط بانک مرکزی ایران است.',
            ],
        ],
        'RU' => [
            'sms' => [
                'signature' => 'QRPay',
                'suffix' => 'Соблюдаем требования Банка России.',
            ],
            'push' => [
                'suffix' => 'Соблюдаем требования Банка России.',
            ],
            'email' => [
                'footer' => 'Сообщение соответствует требованиям Банка России.',
            ],
        ],
    ],
];
