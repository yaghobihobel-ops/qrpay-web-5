<?php

namespace App\Services\Risk;

class OperationalDecisionEngine
{
    public function __construct(
        private FraudPredictor $fraudPredictor,
        private FxVolatilityPredictor $fxPredictor
    ) {
    }

    /**
     * @param array<string, mixed> $transaction
     * @return array<string, mixed>
     */
    public function evaluate(array $transaction): array
    {
        $fraudRisk = $this->fraudPredictor->predict($transaction);
        $fxRisk = $this->fxPredictor->predict($transaction);

        $decision = 'approve';

        if ($fraudRisk >= 0.8 || $fxRisk >= 0.15) {
            $decision = 'manual_review';
        }

        if ($fraudRisk >= 0.95) {
            $decision = 'decline';
        }

        return [
            'decision' => $decision,
            'fraud_score' => round($fraudRisk, 4),
            'fx_volatility' => round($fxRisk, 4),
        ];
    }
}
