<?php

namespace App\DataTransferObjects;

use Illuminate\Contracts\Support\Arrayable;

class RouteRecommendation implements Arrayable
{
    /**
     * @param  string  $route
     * @param  string|null  $currency
     * @param  float  $confidence
     * @param  string|null  $label
     * @param  string|null  $rationale
     * @param  array<int, array<string, mixed>>  $alternatives
     */
    public function __construct(
        protected string $route,
        protected ?string $currency,
        protected float $confidence,
        protected ?string $label = null,
        protected ?string $rationale = null,
        protected array $alternatives = []
    ) {
    }

    public function route(): string
    {
        return $this->route;
    }

    public function currency(): ?string
    {
        return $this->currency;
    }

    public function confidence(): float
    {
        return $this->confidence;
    }

    public function label(): ?string
    {
        return $this->label;
    }

    public function rationale(): ?string
    {
        return $this->rationale;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function alternatives(): array
    {
        return $this->alternatives;
    }

    /**
     * @return array{route: string, currency: ?string, confidence: float, label: ?string, rationale: ?string, alternatives: array<int, array<string, mixed>>}
     */
    public function toArray(): array
    {
        return [
            'route' => $this->route,
            'currency' => $this->currency,
            'confidence' => $this->confidence,
            'label' => $this->label,
            'rationale' => $this->rationale,
            'alternatives' => $this->alternatives,
        ];
    }
}
