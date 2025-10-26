<?php

namespace App\Services\Payments\Recommendation;

use Illuminate\Support\Collection;

class PaymentRouteRecommender
{
    /**
     * @param Collection<int, array<string, mixed>> $routes
     * @return array<string, mixed>|null
     */
    public function recommend(Collection $routes, array $preferences = []): ?array
    {
        if ($routes->isEmpty()) {
            return null;
        }

        $weights = $this->resolveWeights($preferences);

        return $routes
            ->map(fn (array $route) => $this->scoreRoute($route, $weights))
            ->sortByDesc('score')
            ->first();
    }

    /**
     * @param array<string, mixed> $route
     * @param array<string, float> $weights
     * @return array<string, mixed>
     */
    private function scoreRoute(array $route, array $weights): array
    {
        $normalizedFee = 1 - min(1, max(0, ($route['fee'] ?? 0) / ($weights['max_fee'] ?: 1)));
        $normalizedSpeed = min(1, max(0, ($route['expected_settlement_minutes'] ?? 0) / ($weights['max_speed_minutes'] ?: 1)));
        $normalizedReliability = min(1, max(0, ($route['reliability'] ?? 0)));

        $score = (
            $normalizedFee * $weights['fee'] +
            (1 - $normalizedSpeed) * $weights['speed'] +
            $normalizedReliability * $weights['reliability']
        );

        $route['score'] = round($score, 3);

        return $route;
    }

    /**
     * @param array<string, mixed> $preferences
     * @return array<string, float>
     */
    private function resolveWeights(array $preferences): array
    {
        $feeWeight = (float) ($preferences['fee_weight'] ?? 0.5);
        $speedWeight = (float) ($preferences['speed_weight'] ?? 0.3);
        $reliabilityWeight = (float) ($preferences['reliability_weight'] ?? 0.2);
        $scale = max($feeWeight + $speedWeight + $reliabilityWeight, 1);

        return [
            'fee' => $feeWeight / $scale,
            'speed' => $speedWeight / $scale,
            'reliability' => $reliabilityWeight / $scale,
            'max_fee' => (float) ($preferences['max_fee'] ?? 5.0),
            'max_speed_minutes' => (float) ($preferences['max_speed_minutes'] ?? 120),
        ];
    }
}
