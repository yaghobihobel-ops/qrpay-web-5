<?php

namespace OpenTelemetry\API\Trace;

class Tracer
{
    public function __construct(private string $name, private ?string $version = null)
    {
    }

    public function spanBuilder(string $spanName): SpanBuilder
    {
        return new SpanBuilder($spanName);
    }
}
