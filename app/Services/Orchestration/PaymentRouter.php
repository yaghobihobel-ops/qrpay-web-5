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
            $this->registerAdapter($adapter);
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
     * Analyze the given context and select the best payment route available.
     *
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>|null
     */
    public function selectBestRoute(array $context): ?array
    {
        $currency = strtoupper((string) ($context['currency'] ?? ''));
        $destinationCountry = strtoupper((string) ($context['destination_country'] ?? ''));
        $amount = $this->normalizeAmount($context['amount'] ?? null);
        $slaPolicies = (array) ($context['sla'] ?? []);
        $excluded = array_map('strtolower', (array) ($context['excluded_providers'] ?? []));
        $preferred = array_map('strtolower', (array) ($context['preferred_providers'] ?? []));

        if ($currency === '' || $destinationCountry === '') {
            return null;
        }

        /** @var Collection<int, PaymentRoute> $routes */
        $routes = PaymentRoute::query()
            ->where('currency', $currency)
            ->where('destination_country', $destinationCountry)
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

            if (!$adapter) {
                continue;
            }

            if (!$adapter->supports($currency, $destinationCountry)) {
                continue;
            }

            if (!$adapter->isAvailable($context)) {
                continue;
            }

            $slaProfile = array_merge(['score' => $adapter->getSlaScore()], $adapter->getKpiMetrics());
            $kpiMetrics = $adapter->getKpiMetrics();

            if (!$this->passesRouteThresholds($route, $slaProfile, $kpiMetrics)) {
                continue;
            }

            if (!$this->satisfiesSlaPolicy($adapter, $slaPolicies)) {
                continue;
            }

            return $this->formatDecision($route, $adapter);
        }

        return null;
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
     * @param array<string, mixed> $slaPolicies
     */
    protected function satisfiesSlaPolicy(PaymentProviderAdapter $adapter, array $slaPolicies): bool
    {
        $slaScore = $adapter->getSlaScore();
        $kpi = $adapter->getKpiMetrics();

        $minScore = Arr::get($slaPolicies, 'min_sla_score');
        if (is_numeric($minScore) && $slaScore < (float) $minScore) {
            return false;
        }

        $maxLatency = Arr::get($slaPolicies, 'max_latency_ms');
        if (is_numeric($maxLatency) && isset($kpi['latency_ms']) && $kpi['latency_ms'] > (float) $maxLatency) {
            return false;
        }

        $minSuccessRate = Arr::get($slaPolicies, 'min_success_rate');
        if (is_numeric($minSuccessRate) && isset($kpi['success_rate']) && $kpi['success_rate'] < (float) $minSuccessRate) {
            return false;
        }

        return true;
    }

    private function passesRouteThresholds(PaymentRoute $route, array $slaProfile, array $kpiMetrics): bool
    {
        $thresholds = $route->sla_thresholds;

        if (!is_array($thresholds) || $thresholds === []) {
            return true;
        }

        $normalised = $this->normaliseThresholds($thresholds);

        foreach ($normalised['sla'] as $metric => $threshold) {
            if (!array_key_exists($metric, $slaProfile)) {
                return false;
            }

            if (!$this->compareMetric($metric, (float) $slaProfile[$metric], $threshold)) {
                return false;
            }
        }

        foreach ($normalised['kpi'] as $metric => $threshold) {
            if (!array_key_exists($metric, $kpiMetrics)) {
                return false;
            }

            if (!$this->compareMetric($metric, (float) $kpiMetrics[$metric], $threshold)) {
                return false;
            }
        }

        return true;
    }

    private function normaliseThresholds(array $thresholds): array
    {
        $normalised = [
            'sla' => [],
            'kpi' => [],
        ];

        if (isset($thresholds['sla']) || isset($thresholds['kpi'])) {
            $normalised['sla'] = is_array($thresholds['sla'] ?? null) ? $this->flattenNumericValues($thresholds['sla']) : [];
            $normalised['kpi'] = is_array($thresholds['kpi'] ?? null) ? $this->flattenNumericValues($thresholds['kpi']) : [];

            return $normalised;
        }

        foreach ($this->flattenNumericValues($thresholds) as $metric => $value) {
            if ($this->isKpiMetric($metric)) {
                $normalised['kpi'][$metric] = $value;
                continue;
            }

            $normalised['sla'][$metric] = $value;
        }

        return $normalised;
    }

    private function flattenNumericValues(array $values): array
    {
        $flattened = [];

        foreach ($values as $metric => $value) {
            if (is_array($value)) {
                continue;
            }

            if (!is_numeric($value)) {
                continue;
            }

            $flattened[$this->normaliseMetricName((string) $metric)] = (float) $value;
        }

        return $flattened;
    }

    private function compareMetric(string $metric, float $actual, float $threshold): bool
    {
        if ($this->isLowerBetterMetric($metric)) {
            return $actual <= $threshold;
        }

        return $actual >= $threshold;
    }

    private function normaliseMetricName(string $metric): string
    {
        return strtolower(trim($metric));
    }

    private function isLowerBetterMetric(string $metric): bool
    {
        $metric = $this->normaliseMetricName($metric);

        return str_contains($metric, 'latency')
            || str_contains($metric, 'response')
            || str_contains($metric, 'delay')
            || str_contains($metric, 'error_rate');
    }

    private function isKpiMetric(string $metric): bool
    {
        $metric = $this->normaliseMetricName($metric);

        return str_contains($metric, 'success')
            || str_contains($metric, 'throughput')
            || str_contains($metric, 'conversion')
            || str_contains($metric, 'completion')
            || str_contains($metric, 'error_rate');
    }

    private function normalizeAmount(mixed $amount): ?float
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        if (is_numeric($amount)) {
            return (float) $amount;
        }

        if (is_string($amount)) {
            $normalized = preg_replace('/[^0-9.\-]/', '', $amount);

            return is_numeric($normalized) ? (float) $normalized : null;
        }

        return null;
    }

    protected function formatDecision(PaymentRoute $route, PaymentProviderAdapter $adapter): array
    {
        return [
            'route_id' => $route->id,
            'provider' => $route->provider,
            'priority' => $route->priority,
            'fee' => $route->fee,
            'max_amount' => $route->max_amount,
            'currency' => $route->currency,
            'destination_country' => $route->destination_country,
            'sla' => [
                'score' => $adapter->getSlaScore(),
                'kpi' => $adapter->getKpiMetrics(),
            ],
        ];
    }
}
