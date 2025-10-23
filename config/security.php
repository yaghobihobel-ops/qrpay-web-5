<?php

return [
    'sensitive_user_roles' => [
        'SENIOR_MERCHANT',
        'COMPLIANCE_OFFICER',
    ],

    'password_policy' => [
        'min_length' => env('PASSWORD_MIN_LENGTH', 12),
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_number' => true,
        'require_symbol' => true,
    ],

    'session' => [
        'timeout' => env('SESSION_TIMEOUT_MINUTES', 15),
        'rotate_interval' => env('SESSION_ROTATE_MINUTES', 30),
        'same_site' => env('SESSION_SAME_SITE', 'strict'),
        'secure' => filter_var(env('SESSION_SECURE_COOKIE', true), FILTER_VALIDATE_BOOLEAN),
        'http_only' => true,
    ],

    'device_fingerprinting' => [
        'salt' => env('DEVICE_FINGERPRINT_SALT', ''),
        'max_trusted_devices' => env('DEVICE_FINGERPRINT_MAX', 5),
        'force_mfa_on_new_device' => filter_var(env('DEVICE_FORCE_MFA', true), FILTER_VALIDATE_BOOLEAN),
    ],

    'enforce_https' => filter_var(env('FORCE_HTTPS', true), FILTER_VALIDATE_BOOLEAN),

    'encrypted_api_attributes' => [
        'config',
        'credentials',
        'mail_config',
        'push_notification_config',
        'broadcast_config',
    ],
];
