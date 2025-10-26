<?php

return [
    'enabled' => filter_var(env('LARAVEL_TELEMETRY_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
    'service_name' => env('OTEL_SERVICE_NAME', env('APP_NAME', 'qrpay-backend')),
    'otel_endpoint' => env('OTEL_EXPORTER_OTLP_ENDPOINT', 'http://localhost:4318'),
    'otel_protocol' => env('OTEL_EXPORTER_OTLP_PROTOCOL', 'http/protobuf'),
    'otel_headers' => env('OTEL_EXPORTER_OTLP_HEADERS', null),
    'metrics_interval' => (int) env('OTEL_METRICS_EXPORT_INTERVAL', 30000),
    'environment' => env('APP_ENV', 'production'),
];
