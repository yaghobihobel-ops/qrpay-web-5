<?php

namespace App\Support\Routing;

class RouteOption
{
    public function __construct(
        protected string $routeType,
        protected ?string $providerClass,
        protected ?float $estimatedFee = null,
        protected ?float $estimatedEtaMinutes = null,
        protected array $metadata = [],
    ) {
    }

    public function routeType(): string
    {
        return $this->routeType;
    }

    public function providerClass(): ?string
    {
        return $this->providerClass;
    }

    public function estimatedFee(): ?float
    {
        return $this->estimatedFee;
    }

    public function estimatedEtaMinutes(): ?float
    {
        return $this->estimatedEtaMinutes;
    }

    public function metadata(): array
    {
        return $this->metadata;
    }

    public function toArray(): array
    {
        return [
            'route_type' => $this->routeType,
            'provider_class' => $this->providerClass,
            'estimated_fee' => $this->estimatedFee,
            'estimated_eta_minutes' => $this->estimatedEtaMinutes,
            'metadata' => $this->metadata,
        ];
    }
}
