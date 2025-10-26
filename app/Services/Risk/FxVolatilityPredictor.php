<?php

namespace App\Services\Risk;

use Illuminate\Support\Arr;

class FxVolatilityPredictor
{
    public function __construct(private RiskModelRepository $repository)
    {
    }

    /**
     * @param array<string, mixed> $features
     */
    public function predict(array $features): float
    {
        $model = $this->repository->getModel('fx_volatility_model');

        if (empty($model)) {
            return 0.0;
        }

        $weights = $model['weights'] ?? [];
        $bias = (float) ($model['bias'] ?? 0.0);
        $score = $bias;

        foreach ($weights as $feature => $weight) {
            $score += (float) $weight * (float) Arr::get($features, $feature, 0.0);
        }

        return $score;
    }
}
