<?php

namespace OpenTelemetry\API\Metrics;

class Histogram
{
    public function __construct(private string $name)
    {
    }

    public function record(float|int $value, array $attributes = []): void
    {
        // no-op stub
    }
}
