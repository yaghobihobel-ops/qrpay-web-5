<?php

namespace App\Services\Orchestration;

use App\Models\PaymentRoute;
use App\Models\User;
use App\Services\Orchestration\Contracts\PaymentProviderAdapter;
use App\Services\Orchestration\DTO\PaymentRouteResult;
use App\Services\Orchestration\Exceptions\NoAvailablePaymentRouteException;
use App\Services\Pricing\DTO\FeeQuote;
use App\Services\Pricing\Exceptions\PricingRuleNotFoundException;
use App\Services\Pricing\FeeEngine;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class PaymentRouter
{
    /**
     * @var array<string, PaymentProviderAdapter>
     */
    protected array $adapters = [];

    public function __construct(iterable $adapters = [], protected ?FeeEngine $feeEngine = null)
    {
        foreach ($adapters as $adapter) {
            if ($adapter instanceof PaymentProviderAdapter) {
                $this->adapters[strtolower($adapter->getName())] = $adapter;
            }
        }
    }

    public function registerAdapter(PaymentProviderAdapter $adapter): void
    {
        $this->adapters[strtolower($adapter->getName())] = $adapter;
    }

    public function setFeeEngine(?FeeEngine $feeEngine): void
    {
        $this->feeEngine = $feeEngine;
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>|null
     */
    public function selectBestRoute(array $context): ?array
    {
        $currency = strtoupper((string) ($context['currency'] ?? ''));
        $destinationCountry = strtoupper((string) ($context['destination_country'] ?? ''));
        $amount = $this->normalizeAmount($context['amount'] ?? null);
        $slaPolicies = (array) ($context['sla'] ?? []);
        $preferred = array_map('strtolower', (array) ($context['preferred_providers'] ?? []));
        $excluded = array_map('strtolower', (array) ($context['excluded_providers'] ?? []));

        if ($currency === '') {
            return null;
        }

        $routes = PaymentRoute::query()
            ->where('currency', $currency)
            ->when($destinationCountry !== '', function ($query) use ($destinationCountry) {
                $query->where(function ($inner) use ($destinationCountry) {
                    $inner->whereNull('destination_country')
                        ->orWhere('destination_country', $destinationCountry);
                });
            })
            ->where('is_active', true)
            ->orderBy('priority')
            ->get();

        $routes = $routes->filter(function (PaymentRoute $route) use ($amount, $excluded) {
            if (in_array(strtolower($route->provider), $excluded, true)) {
                return false;
            }

            if ($amount !== null && $route->max_amount !== null && (float) $route->max_amount < $amount) {
                return false;
            }

            return isset($this->adapters[strtolower($route->provider)]);
        })->values();

        if ($routes->isEmpty()) {
            return null;
        }

        $routes = $this->sortRoutes($routes, $preferred);

        foreach ($routes as $route) {
            $adapter = $this->adapters[strtolower($route->provider)] ?? null;

            if (! $adapter) {
                continue;
            }

            if (! $adapter->supports($currency, $destinationCountry)) {
                continue;
            }

            if (! $adapter->isAvailable($context)) {
                continue;
            }

            if (! $this->satisfiesSlaPolicy($adapter, $slaPolicies)) {
                continue;
            }

            if (! $this->passesRouteThresholds($route, $adapter)) {
                continue;
            }

            $slaProfile = array_merge(['score' => $adapter->getSlaScore()], $adapter->getKpiMetrics());
            $kpiMetrics = $adapter->getKpiMetrics();

        return null;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function getFailoverRoute(array $context, string $failedProvider): ?array
    {
        $context['excluded_providers'] = array_merge(
            (array) ($context['excluded_providers'] ?? []),
            [$failedProvider]
        );

    protected function formatDecision(PaymentRoute $route, PaymentProviderAdapter $adapter): array
    {
        return [
            'route_id' => $route->id,
            'provider' => $route->provider,
            'priority' => (int) $route->priority,
            'fee' => (float) $route->fee,
            'currency' => $route->currency,
            'destination_country' => $route->destination_country,
            'sla' => [
                'score' => $adapter->getSlaScore(),
                'kpi' => $adapter->getKpiMetrics(),
            ],
        ];
    }

    /**
     * @param Collection<int, PaymentRoute> $routes
     * @param array<int, string> $preferred
     * @return Collection<int, PaymentRoute>
     */
    protected function sortRoutes(Collection $routes, array $preferred): Collection
    {
        $preferred = array_values($preferred);

        return $routes->sortBy(function (PaymentRoute $route) use ($preferred) {
            $provider = strtolower($route->provider);
            $preferredIndex = array_search($provider, $preferred, true);

            if ($preferredIndex === false) {
                $preferredIndex = count($preferred);
            }

            return [$preferredIndex, $route->priority];
        })->values();
    }

    /**
     * @param array<string, mixed> $policies
     */
    protected function satisfiesSlaPolicy(PaymentProviderAdapter $adapter, array $policies): bool
    {
        if ($policies === []) {
            return true;
        }

        $score = $adapter->getSlaScore();
        $metrics = $adapter->getKpiMetrics();

        $minScore = Arr::get($policies, 'min_sla_score');
        if (is_numeric($minScore) && $score < (float) $minScore) {
            return false;
        }

        $maxLatency = Arr::get($policies, 'max_latency_ms');
        if (is_numeric($maxLatency) && isset($metrics['latency_ms']) && (float) $metrics['latency_ms'] > (float) $maxLatency) {
            return false;
        }

        $minSuccessRate = Arr::get($policies, 'min_success_rate');
        if (is_numeric($minSuccessRate) && isset($metrics['success_rate']) && (float) $metrics['success_rate'] < (float) $minSuccessRate) {
            return false;
        }

        return true;
    }

    protected function passesRouteThresholds(PaymentRoute $route, PaymentProviderAdapter $adapter): bool
    {
        $thresholds = $route->sla_thresholds;

        if (! is_array($thresholds) || $thresholds === []) {
            return true;
        }

        $metrics = $adapter->getKpiMetrics();
        $checks = [
            ...($thresholds['sla'] ?? []),
            ...($thresholds['kpi'] ?? []),
        ];

        foreach ($checks as $metric => $expected) {
            if (! $this->compareMetric($metric, $metrics[$metric] ?? null, $expected)) {
                return false;
            }
        }

        return true;
    }

    protected function compareMetric(string $metric, $actual, $expected): bool
    {
        if ($actual === null || $expected === null) {
            return false;
        }

        $actual = (float) $actual;
        $expected = (float) $expected;

        return match (true) {
            str_contains($metric, 'latency') || str_contains($metric, 'error') => $actual <= $expected,
            default => $actual >= $expected,
        };
    }

    protected function normalizeAmount($amount): ?float
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        if (is_numeric($amount)) {
            return (float) $amount;
        }

        return null;
    }

    protected function formatDecision(PaymentRoute $route, PaymentProviderAdapter $adapter): array
    {
        return [
            'provider' => $adapter->getName(),
            'route_id' => $route->id,
            'priority' => (int) $route->priority,
            'fee' => (float) ($route->fee ?? 0),
            'sla' => [
                'score' => $adapter->getSlaScore(),
                'kpi' => $adapter->getKpiMetrics(),
            ],
        ];
    }

    private function normalizeAmount(mixed $amount): ?float
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        if (is_numeric($amount)) {
            return (float) $amount;
        }

        return null;
    }
}
