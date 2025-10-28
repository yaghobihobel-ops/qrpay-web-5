<?php

namespace OpenTelemetry\API\Metrics;

class Counter
{
    public function __construct(private string $name)
    {
    }

    public function add(float|int $amount, array $attributes = []): void
    {
        // no-op stub
    }
}
