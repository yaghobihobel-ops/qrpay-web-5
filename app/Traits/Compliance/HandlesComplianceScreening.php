<?php

namespace App\Traits\Compliance;

use App\Models\ComplianceScreening;
use App\Services\Compliance\RuleEngine;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

trait HandlesComplianceScreening
{
    protected function runComplianceScreening($user, array $kycPayload): void
    {
        /** @var \App\Services\Compliance\RuleEngine $engine */
        $engine = app(RuleEngine::class);
        $decision = $engine->evaluate($user, $kycPayload);

        ComplianceScreening::updateOrCreate(
            [
                'subject_type' => $user::class,
                'subject_id' => $user->getKey(),
            ],
            [
                'region' => $decision->region(),
                'status' => $decision->status(),
                'risk_score' => $decision->riskScore(),
                'triggered_rules' => $decision->triggeredRules(),
                'recommendations' => $decision->recommendations(),
            ]
        );

        if ($decision->requiresEnhancedDueDiligence() && Schema::hasColumn($user->getTable(), 'compliance_flags')) {
            $flags = Arr::wrap($user->compliance_flags ?? []);
            $flags = array_unique(array_merge($flags, ['enhanced_due_diligence']));
            $user->forceFill(['compliance_flags' => $flags])->save();
        }
    }
}
