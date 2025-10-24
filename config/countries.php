<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Country Module Registry
    |--------------------------------------------------------------------------
    | Configure available country modules and whether they are enabled by
    | default. Each module maps to a class implementing the
    | CountryModuleInterface. Flags can be overridden through environment
    | variables or future admin settings without touching core code.
    */
    'modules' => [
        'IR' => [
            'enabled' => env('COUNTRY_IR_ENABLED', false),
            'class' => Modules\Country\IR\IRCountryModule::class,
        ],
        'CN' => [
            'enabled' => env('COUNTRY_CN_ENABLED', false),
            'class' => Modules\Country\CN\CNCountryModule::class,
        ],
        'TR' => [
            'enabled' => env('COUNTRY_TR_ENABLED', false),
            'class' => Modules\Country\TR\TRCountryModule::class,
        ],
    ],
];
