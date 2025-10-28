<?php

namespace OpenTelemetry\API\Trace;

use OpenTelemetry\Context\Scope;
use Throwable;

class Span
{
    public function __construct(private string $name)
    {
    }

    public function setAttribute(string $key, mixed $value): void
    {
        // no-op stub
    }

    public function setStatus(int $status, ?string $description = null): void
    {
        // no-op stub
    }

    public function recordException(Throwable $exception): void
    {
        // no-op stub
    }

    public function activate(): Scope
    {
        return new Scope();
    }

    public function end(): void
    {
        // no-op stub
    }
}
