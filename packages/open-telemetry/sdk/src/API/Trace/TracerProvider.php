<?php

namespace OpenTelemetry\API\Trace;

class TracerProvider
{
    public function getTracer(string $name, ?string $version = null): Tracer
    {
        return new Tracer($name, $version);
    }
}
