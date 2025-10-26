<?php

namespace App\Services\Monitoring;

class DomainOperationContext
{
    public string $domain;
    public string $operation;
    public string $provider;
    public array $attributes;
    public array $config;
    public string $correlationId;
    public float $startedAt;
    public ?float $finishedAt = null;

    public function __construct(string $domain, string $operation, string $provider, array $attributes, array $config)
    {
        $this->domain = $domain;
        $this->operation = $operation;
        $this->provider = $provider;
        $this->attributes = $attributes;
        $this->config = $config;
        $this->startedAt = microtime(true);
        $this->correlationId = (string) \Illuminate\Support\Str::uuid();
    }

    public function finish(array $extra = []): void
    {
        $this->finishedAt = microtime(true);
        $this->attributes = array_merge($this->attributes, $extra);
    }

    public function getDurationInMilliseconds(): ?float
    {
        if (is_null($this->finishedAt)) {
            return null;
        }

        return round(($this->finishedAt - $this->startedAt) * 1000, 2);
    }

    public function toLogPayload(): array
    {
        return array_merge([
            'correlation_id' => $this->correlationId,
            'domain'         => $this->domain,
            'operation'      => $this->operation,
            'provider'       => $this->provider,
            'duration_ms'    => $this->getDurationInMilliseconds(),
        ], $this->attributes);
    }
}
