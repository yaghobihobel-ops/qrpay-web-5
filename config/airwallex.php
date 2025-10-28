<?php

return [
    'base_url' => env('AIRWALLEX_BASE_URL', 'https://api-demo.airwallex.com'),
    'client_id' => env('AIRWALLEX_CLIENT_ID'),
    'api_key' => env('AIRWALLEX_API_KEY'),
    'authentication_path' => env('AIRWALLEX_AUTH_PATH', '/api/v1/authentication/login'),
    'cardholders_path' => env('AIRWALLEX_CARDHOLDERS_PATH', '/api/v1/issuing/cardholders'),
    'cardholder_create_path' => env('AIRWALLEX_CARDHOLDER_CREATE_PATH', '/api/v1/issuing/cardholders/create'),
    'timeout' => env('AIRWALLEX_TIMEOUT', 30),
];
