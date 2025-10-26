<?php


return [
    'header' => env('PARTNER_SECURITY_HEADER', 'X-QRPay-Service'),

    'services' => [
        'alipay' => [
            'enabled' => filter_var(env('ALIPAY_SECURITY_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
            'mutual_tls' => [
                'required' => filter_var(env('ALIPAY_MTLS_REQUIRED', true), FILTER_VALIDATE_BOOLEAN),
                'allowed_subjects' => array_filter(array_map('trim', explode(',', (string) env('ALIPAY_MTLS_SUBJECTS')))),
            ],
            'ip_allowlist' => array_filter(array_map('trim', explode(',', (string) env('ALIPAY_ALLOWED_IPS')))),
            'signing' => [
                'enabled' => filter_var(env('ALIPAY_REQUEST_SIGNING', true), FILTER_VALIDATE_BOOLEAN),
                'algorithm' => env('ALIPAY_SIGNING_ALGO', 'sha256'),
                'header' => env('ALIPAY_SIGNATURE_HEADER', 'X-Alipay-Signature'),
                'timestamp_header' => env('ALIPAY_TIMESTAMP_HEADER', 'X-Alipay-Timestamp'),
                'leeway' => env('ALIPAY_SIGNATURE_LEEWAY', 120),
                'secret_field' => env('ALIPAY_SIGNING_SECRET_FIELD', 'signing_secret'),
            ],
        ],
        'blubank' => [
            'enabled' => filter_var(env('BLUBANK_SECURITY_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
            'mutual_tls' => [
                'required' => filter_var(env('BLUBANK_MTLS_REQUIRED', true), FILTER_VALIDATE_BOOLEAN),
                'allowed_subjects' => array_filter(array_map('trim', explode(',', (string) env('BLUBANK_MTLS_SUBJECTS')))),
            ],
            'ip_allowlist' => array_filter(array_map('trim', explode(',', (string) env('BLUBANK_ALLOWED_IPS')))),
            'signing' => [
                'enabled' => filter_var(env('BLUBANK_REQUEST_SIGNING', true), FILTER_VALIDATE_BOOLEAN),
                'algorithm' => env('BLUBANK_SIGNING_ALGO', 'sha256'),
                'header' => env('BLUBANK_SIGNATURE_HEADER', 'X-BluBank-Signature'),
                'timestamp_header' => env('BLUBANK_TIMESTAMP_HEADER', 'X-BluBank-Timestamp'),
                'leeway' => env('BLUBANK_SIGNATURE_LEEWAY', 300),
                'secret_field' => env('BLUBANK_SIGNING_SECRET_FIELD', 'signing_secret'),
            ],
        ],
        'yoomonea' => [
            'enabled' => filter_var(env('YOOMONEA_SECURITY_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
            'mutual_tls' => [
                'required' => filter_var(env('YOOMONEA_MTLS_REQUIRED', true), FILTER_VALIDATE_BOOLEAN),
                'allowed_subjects' => array_filter(array_map('trim', explode(',', (string) env('YOOMONEA_MTLS_SUBJECTS')))),
            ],
            'ip_allowlist' => array_filter(array_map('trim', explode(',', (string) env('YOOMONEA_ALLOWED_IPS')))),
            'signing' => [
                'enabled' => filter_var(env('YOOMONEA_REQUEST_SIGNING', true), FILTER_VALIDATE_BOOLEAN),
                'algorithm' => env('YOOMONEA_SIGNING_ALGO', 'sha256'),
                'header' => env('YOOMONEA_SIGNATURE_HEADER', 'X-Yoomonea-Signature'),
                'timestamp_header' => env('YOOMONEA_TIMESTAMP_HEADER', 'X-Yoomonea-Timestamp'),
                'leeway' => env('YOOMONEA_SIGNATURE_LEEWAY', 300),
                'secret_field' => env('YOOMONEA_SIGNING_SECRET_FIELD', 'signing_secret'),
            ],
        ],
    ],
];
