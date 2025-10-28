<?php

namespace OpenTelemetry\API\Metrics;

class Meter
{
    public function __construct(private string $name, private ?string $version = null)
    {
    }

    public function createCounter(string $name): Counter
    {
        return new Counter($name);
    }

    public function createHistogram(string $name): Histogram
    {
        return new Histogram($name);
    }
}
