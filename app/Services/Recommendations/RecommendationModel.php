<?php

namespace App\Services\Recommendations;

class RecommendationModel
{
    /**
     * @param  array<string, mixed>  $features
     * @return array{route: string|null, score: float, currency: string|null}
     */
    public function ruleBased(array $features): array
    {
        $dominantType = $features['dominant_type'] ?? null;
        $dominantCurrency = $features['dominant_currency'] ?? null;
        $loyalty = (float) ($features['loyalty_score'] ?? 0.0);

        if ($dominantType === null) {
            return ['route' => null, 'score' => 0.0];
        }

        $score = 0.25;
        $score += min(0.5, $loyalty * 0.5);
        $score += min(0.25, (float) ($features['currency_concentration'] ?? 0.0) * 0.25);

        $route = match ($dominantType) {
            'MAKE-PAYMENT', 'MERCHANT-PAYMENT' => 'payment',
            'MONEY-EXCHANGE' => 'exchange',
            'MONEY-OUT' => 'withdrawal',
            default => 'payment',
        };

        if ($dominantCurrency === 'UNKNOWN') {
            $dominantCurrency = null;
        }

        return [
            'route' => $route,
            'score' => round($score, 4),
            'currency' => $dominantCurrency,
        ];
    }

    /**
     * @param  array<string, mixed>  $features
     * @return array{route: string|null, score: float, distribution: array<string, float>}
     */
    public function mlProbabilities(array $features): array
    {
        $loyalty = (float) ($features['loyalty_score'] ?? 0.0);
        $payment = (float) ($features['payment_ratio'] ?? 0.0);
        $exchange = (float) ($features['exchange_ratio'] ?? 0.0);
        $withdrawal = (float) ($features['withdrawal_ratio'] ?? 0.0);
        $averageTicket = (float) ($features['average_ticket'] ?? 0.0);

        $weights = [
            'payment' => [
                'bias' => -0.25,
                'loyalty' => 1.2,
                'ratio' => 1.6,
                'ticket' => 0.0004,
            ],
            'exchange' => [
                'bias' => -0.35,
                'loyalty' => 1.1,
                'ratio' => 1.8,
                'ticket' => 0.0003,
            ],
            'withdrawal' => [
                'bias' => -0.4,
                'loyalty' => 1.0,
                'ratio' => 1.7,
                'ticket' => 0.0002,
            ],
        ];

        $signals = [
            'payment' => $this->sigmoid($weights['payment']['bias'] + ($weights['payment']['loyalty'] * $loyalty)
                + ($weights['payment']['ratio'] * $payment) + ($weights['payment']['ticket'] * $averageTicket)),
            'exchange' => $this->sigmoid($weights['exchange']['bias'] + ($weights['exchange']['loyalty'] * $loyalty)
                + ($weights['exchange']['ratio'] * $exchange) + ($weights['exchange']['ticket'] * $averageTicket)),
            'withdrawal' => $this->sigmoid($weights['withdrawal']['bias'] + ($weights['withdrawal']['loyalty'] * $loyalty)
                + ($weights['withdrawal']['ratio'] * $withdrawal) + ($weights['withdrawal']['ticket'] * $averageTicket)),
        ];

        $sum = array_sum($signals) ?: 1;
        $distribution = array_map(fn ($value) => round($value / $sum, 4), $signals);

        arsort($distribution);
        $route = array_key_first($distribution);

        return [
            'route' => $route,
            'score' => $distribution[$route] ?? 0.0,
            'distribution' => $distribution,
        ];
    }

    protected function sigmoid(float $value): float
    {
        return 1 / (1 + exp(-$value));
    }
}
