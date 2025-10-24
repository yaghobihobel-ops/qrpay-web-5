<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default & Fallback Locales
    |--------------------------------------------------------------------------
    | These values determine which locales are treated as the application
    | defaults. They are leveraged by the locale manager to derive
    | country-specific defaults without mutating Laravel's core config.
    */
    'default' => env('APP_LOCALE', 'en'),
    'fallback' => env('APP_FALLBACK_LOCALE', env('APP_LOCALE', 'en')),

    /*
    |--------------------------------------------------------------------------
    | Locale Catalog
    |--------------------------------------------------------------------------
    | Each locale entry contains metadata used for rendering RTL/LTR aware
    | layouts, formatting, and admin displays. Country modules reference
    | these codes when declaring supported locales.
    */
    'locales' => [
        'en' => [
            'name' => 'English',
            'native' => 'English',
            'dir' => 'ltr',
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'countries' => ['IR', 'CN', 'TR'],
        ],
        'fa' => [
            'name' => 'Persian (Farsi)',
            'native' => 'فارسی',
            'dir' => 'rtl',
            'decimal_separator' => '٫',
            'thousands_separator' => '٬',
            'countries' => ['IR'],
        ],
        'zh' => [
            'name' => 'Chinese (Simplified)',
            'native' => '简体中文',
            'dir' => 'ltr',
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'countries' => ['CN'],
        ],
        'ru' => [
            'name' => 'Russian',
            'native' => 'Русский',
            'dir' => 'ltr',
            'decimal_separator' => ',',
            'thousands_separator' => ' ',
            'countries' => ['RU'],
        ],
        'tr' => [
            'name' => 'Turkish',
            'native' => 'Türkçe',
            'dir' => 'ltr',
            'decimal_separator' => ',',
            'thousands_separator' => '.',
            'countries' => ['TR'],
        ],
        'ar' => [
            'name' => 'Arabic',
            'native' => 'العربية',
            'dir' => 'rtl',
            'decimal_separator' => '٫',
            'thousands_separator' => '٬',
            'countries' => ['AE', 'SA'],
        ],
        'ps' => [
            'name' => 'Pashto',
            'native' => 'پښتو',
            'dir' => 'rtl',
            'decimal_separator' => '٫',
            'thousands_separator' => '٬',
            'countries' => ['AF'],
        ],
    ],
];
