<?php

namespace App\Services\Recommendations;

use App\DataTransferObjects\RouteRecommendation;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class RouteRecommendationEngine
{
    public function __construct(
        protected RecommendationFeatureExtractor $extractor,
        protected RecommendationModel $model
    ) {
    }

    public function recommendFor(User $user): ?RouteRecommendation
    {
        $cacheKey = sprintf('recommendations.route.%s', $user->id);
        $ttl = now()->addSeconds((int) config('recommendations.cache_ttl', 600));

        return Cache::remember($cacheKey, $ttl, function () use ($user) {
            $features = $this->extractor->forUser($user);
            $loyalty = (float) ($features['loyalty_score'] ?? 0.0);

            if ($loyalty < (float) config('recommendations.loyalty_threshold', 0.35)) {
                return null;
            }

            $rule = $this->model->ruleBased($features);
            $ml = $this->model->mlProbabilities($features);

            $ruleWeight = (float) config('recommendations.rule_weight', 0.55);
            $mlWeight = (float) config('recommendations.ml_weight', 0.45);

            $route = $rule['route'] ?? $ml['route'];
            $currency = $rule['currency'] ?? null;

            if ($ml['score'] > $rule['score']) {
                $route = $ml['route'];
            }

            $confidence = round((($rule['score'] * $ruleWeight) + ($ml['score'] * $mlWeight)), 4);

            $metadata = config('recommendations.supported_routes');
            $label = $metadata[$route]['label'] ?? Str::title(str_replace('-', ' ', (string) $route));

            $rationale = $this->composeRationale($route, $features, $rule, $ml);

            $alternatives = collect($ml['distribution'] ?? [])
                ->reject(fn ($score, $key) => $key === $route)
                ->sortDesc()
                ->take(2)
                ->map(fn ($score, $key) => [
                    'route' => $key,
                    'label' => $metadata[$key]['label'] ?? Str::title($key),
                    'score' => $score,
                ])
                ->values()
                ->all();

            return new RouteRecommendation($route, $currency, $confidence, $label, $rationale, $alternatives);
        });
    }

    /**
     * @param  array<string, mixed>  $features
     * @param  array<string, mixed>  $rule
     * @param  array<string, mixed>  $ml
     */
    protected function composeRationale(string $route, array $features, array $rule, array $ml): string
    {
        $loyalty = round(($features['loyalty_score'] ?? 0.0) * 100);
        $avgTicket = number_format((float) ($features['average_ticket'] ?? 0.0), 2);
        $dominantCurrency = $features['dominant_currency'] ?? null;

        $phrases = [
            "Loyalty score {$loyalty}% indicates strong repeat usage",
            "Avg. ticket {$avgTicket}",
        ];

        if ($dominantCurrency && $dominantCurrency !== 'UNKNOWN') {
            $phrases[] = "Preferred currency {$dominantCurrency}";
        }

        $ratioKey = match ($route) {
            'exchange' => 'exchange_ratio',
            'withdrawal' => 'withdrawal_ratio',
            default => 'payment_ratio',
        };

        $ratio = round((float) ($features[$ratioKey] ?? 0.0) * 100);
        $phrases[] = "Historical share {$ratio}%";

        return implode(' · ', array_filter($phrases));
    }
}
