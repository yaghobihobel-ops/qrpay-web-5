<?php

namespace App\Services\Orchestration;

use App\Models\PaymentRoute;
use App\Services\Orchestration\Contracts\PaymentProviderAdapter;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class PaymentRouter
{
    /**
     * @var array<string, PaymentProviderAdapter>
     */
    protected array $adapters = [];

    /**
     * @param iterable<int, PaymentProviderAdapter> $adapters
     */
    public function __construct(iterable $adapters)
    {
        foreach ($adapters as $adapter) {
            $this->adapters[strtolower($adapter->getName())] = $adapter;
        }
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

        $routes = PaymentRoute::query()
            ->where('currency', $currency)
            ->where('destination_country', $destinationCountry)
            ->where('is_active', true)
            ->orderBy('priority')
            ->get();

        $routes = $routes->filter(function (PaymentRoute $route) use ($amount, $currency, $destinationCountry, $excluded) {
            if (in_array(strtolower($route->provider), $excluded, true)) {
                return false;
            }

            if ($amount !== null && $route->max_amount !== null && $route->max_amount < $amount) {
                return false;
            }

            $adapter = $this->adapters[strtolower($route->provider)] ?? null;

            if (!$adapter) {
                return false;
            }

            return $adapter->supports($currency, $destinationCountry);
        })->values();

        $routes = $this->sortRoutes($routes, $preferred);

        foreach ($routes as $route) {
            $adapter = $this->adapters[strtolower($route->provider)] ?? null;

            if (!$adapter) {
                continue;
            }

            if (!$adapter->isAvailable($context)) {
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
     * Provide the next best route excluding the failed provider.
     *
     * @param array<string, mixed> $context
     */
    public function getFailoverRoute(array $context, string $failedProvider): ?array
    {
        $excluded = array_map('strtolower', (array) ($context['excluded_providers'] ?? []));
        $excluded[] = strtolower($failedProvider);

        $context['excluded_providers'] = array_values(array_unique($excluded));

        return $this->selectBestRoute($context);
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

    protected function normalizeAmount(mixed $amount): ?float
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        if (is_string($amount)) {
            $amount = str_replace(',', '', $amount);
        }

        if (!is_numeric($amount)) {
            return null;
        }

        return (float) $amount;
    }

    protected function formatDecision(PaymentRoute $route, PaymentProviderAdapter $adapter): array
    {
        return [
            'provider' => $adapter->getName(),
            'route_id' => $route->getKey(),
            'priority' => $route->priority,
            'fee' => $route->fee,
            'max_amount' => $route->max_amount,
            'sla' => [
                'score' => $adapter->getSlaScore(),
                'kpi' => $adapter->getKpiMetrics(),
            ],
        ];
    }
}
