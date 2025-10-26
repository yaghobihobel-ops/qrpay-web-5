<?php

return [
    'driver' => env('KEY_MANAGEMENT_DRIVER', 'vault'),

    'cache_ttl' => env('KEY_MANAGEMENT_CACHE_TTL', 300),

    'vault' => [
        'base_uri' => env('VAULT_BASE_URI'),
        'token' => env('VAULT_TOKEN'),
        'namespace' => env('VAULT_NAMESPACE'),
        'ca_cert' => env('VAULT_CA_CERT'),
        'timeout' => env('VAULT_TIMEOUT', 5),
    ],

    'services' => [
        'alipay' => [
            'enabled' => true,
            'driver' => env('ALIPAY_KEY_DRIVER', 'vault'),
            'secret_path' => env('VAULT_ALIPAY_SECRET_PATH', 'kv/data/payments/alipay'),
            'rotation_endpoint' => env('VAULT_ALIPAY_ROTATE_ENDPOINT', 'transit/keys/alipay/rotate'),
            'data_path' => env('VAULT_ALIPAY_DATA_PATH', 'data.data'),
            'fields' => [
                'api_key' => env('ALIPAY_API_KEY_FIELD', 'api_key'),
                'signing_secret' => env('ALIPAY_SIGNING_SECRET_FIELD', 'signing_secret'),
                'client_cert' => env('ALIPAY_CLIENT_CERT_FIELD', 'client_cert'),
                'client_key' => env('ALIPAY_CLIENT_KEY_FIELD', 'client_key'),
            ],
            'rotation' => [
                'enabled' => filter_var(env('ALIPAY_ROTATION_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
                'interval_hours' => env('ALIPAY_ROTATION_INTERVAL_HOURS', 12),
            ],
        ],
        'blubank' => [
            'enabled' => true,
            'driver' => env('BLUBANK_KEY_DRIVER', 'vault'),
            'secret_path' => env('VAULT_BLUBANK_SECRET_PATH', 'kv/data/payments/blubank'),
            'rotation_endpoint' => env('VAULT_BLUBANK_ROTATE_ENDPOINT', 'transit/keys/blubank/rotate'),
            'data_path' => env('VAULT_BLUBANK_DATA_PATH', 'data.data'),
            'fields' => [
                'api_key' => env('BLUBANK_API_KEY_FIELD', 'api_key'),
                'signing_secret' => env('BLUBANK_SIGNING_SECRET_FIELD', 'signing_secret'),
                'client_cert' => env('BLUBANK_CLIENT_CERT_FIELD', 'client_cert'),
                'client_key' => env('BLUBANK_CLIENT_KEY_FIELD', 'client_key'),
            ],
            'rotation' => [
                'enabled' => filter_var(env('BLUBANK_ROTATION_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
                'interval_hours' => env('BLUBANK_ROTATION_INTERVAL_HOURS', 24),
            ],
        ],
        'yoomonea' => [
            'enabled' => true,
            'driver' => env('YOOMONEA_KEY_DRIVER', 'vault'),
            'secret_path' => env('VAULT_YOOMONEA_SECRET_PATH', 'kv/data/payments/yoomonea'),
            'rotation_endpoint' => env('VAULT_YOOMONEA_ROTATE_ENDPOINT', 'transit/keys/yoomonea/rotate'),
            'data_path' => env('VAULT_YOOMONEA_DATA_PATH', 'data.data'),
            'fields' => [
                'api_key' => env('YOOMONEA_API_KEY_FIELD', 'api_key'),
                'signing_secret' => env('YOOMONEA_SIGNING_SECRET_FIELD', 'signing_secret'),
                'client_cert' => env('YOOMONEA_CLIENT_CERT_FIELD', 'client_cert'),
                'client_key' => env('YOOMONEA_CLIENT_KEY_FIELD', 'client_key'),
            ],
            'rotation' => [
                'enabled' => filter_var(env('YOOMONEA_ROTATION_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
                'interval_hours' => env('YOOMONEA_ROTATION_INTERVAL_HOURS', 24),
            ],
        ],
    ],

    'rotation' => [
        'cron' => env('KEY_ROTATION_CRON', '0 */6 * * *'),
        'max_retries' => env('KEY_ROTATION_RETRIES', 3),
        'retry_backoff' => env('KEY_ROTATION_BACKOFF_SECONDS', 30),
    ],
];
