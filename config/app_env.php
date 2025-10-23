<?php

return [
    'sandbox' => [
        'default' => (bool) env('APP_SANDBOX_MODE', false),
        'staging_uses_sandbox' => (bool) env('SANDBOX_IN_STAGING', true),
        'testing_uses_sandbox' => (bool) env('SANDBOX_IN_TESTING', true),
    ],

    'fakes' => [
        'enabled' => (bool) env('SANDBOX_USE_FAKES', in_array(env('APP_ENV'), ['testing', 'staging'], true)),
        'repository_path' => env('SANDBOX_SCENARIO_PATH', storage_path('app/sandbox/fake_providers.json')),
    ],
];
