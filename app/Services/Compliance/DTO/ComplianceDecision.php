<?php

namespace App\Services\Compliance\DTO;

use Illuminate\Contracts\Support\Arrayable;

class ComplianceDecision implements Arrayable
{
    public function __construct(
        protected string $status,
        protected int $riskScore,
        protected string $region,
        protected array $triggeredRules = [],
        protected array $recommendations = []
    ) {
    }

    public function status(): string
    {
        return $this->status;
    }

    public function riskScore(): int
    {
        return $this->riskScore;
    }

    public function region(): string
    {
        return $this->region;
    }

    public function triggeredRules(): array
    {
        return $this->triggeredRules;
    }

    public function recommendations(): array
    {
        return $this->recommendations;
    }

    public function requiresEnhancedDueDiligence(): bool
    {
        return collect($this->triggeredRules)->contains(function (array $rule) {
            return ($rule['resolution'] ?? null) === 'enhanced_due_diligence';
        });
    }

    public function requiresManualReview(): bool
    {
        return $this->status === 'review' || $this->status === 'escalate';
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status(),
            'risk_score' => $this->riskScore(),
            'region' => $this->region(),
            'triggered_rules' => $this->triggeredRules(),
            'recommendations' => $this->recommendations(),
        ];
    }
}
