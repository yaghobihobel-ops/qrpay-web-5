<?php

namespace App\Support\Routing;

class RoutingContext
{
    public function __construct(
        protected string $sourceCountry,
        protected string $destinationCountry,
        protected string $sendCurrency,
        protected string $receiveCurrency,
        protected float $amount,
        protected string $priority = 'speed',
        protected array $metadata = [],
    ) {
    }

    public function sourceCountry(): string
    {
        return $this->sourceCountry;
    }

    public function destinationCountry(): string
    {
        return $this->destinationCountry;
    }

    public function sendCurrency(): string
    {
        return $this->sendCurrency;
    }

    public function receiveCurrency(): string
    {
        return $this->receiveCurrency;
    }

    public function amount(): float
    {
        return $this->amount;
    }

    public function priority(): string
    {
        return $this->priority;
    }

    public function metadata(): array
    {
        return $this->metadata;
    }
}
