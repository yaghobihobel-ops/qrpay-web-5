<?php

namespace OpenTelemetry\API\Metrics;

class MeterProvider
{
    public function getMeter(string $name, ?string $version = null): Meter
    {
        return new Meter($name, $version);
    }
}
