<?php

namespace App\Exceptions;

use RuntimeException;

class CircuitBreakerOpenException extends RuntimeException
{
    protected string $service;
    protected string $circuit;
    protected int $retryAfter;

    public function __construct(string $service, string $circuit, int $retryAfter)
    {
        $this->service = $service;
        $this->circuit = $circuit;
        $this->retryAfter = $retryAfter;

        parent::__construct(
            sprintf(
                'Circuit breaker [%s] for service [%s] is open. Retry after %d seconds.',
                $circuit,
                $service,
                $retryAfter
            )
        );
    }

    public function getService(): string
    {
        return $this->service;
    }

    public function getCircuit(): string
    {
        return $this->circuit;
    }

    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}
