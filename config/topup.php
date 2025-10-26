<?php

return [
    'default' => env('TOPUP_PROVIDER', 'reloadly'),

    'providers' => [
        'reloadly' => App\Services\Topup\ReloadlyService::class,
    ],
];
