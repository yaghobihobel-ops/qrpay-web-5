<?php

namespace OpenTelemetry\API\Trace;

class SpanBuilder
{
    public function __construct(private string $name)
    {
    }

    public function setSpanKind(int $kind): self
    {
        return $this;
    }

    public function startSpan(): Span
    {
        return new Span($this->name);
    }
}
