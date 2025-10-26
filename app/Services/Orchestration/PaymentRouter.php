<?php

namespace App\Services\Orchestration;

use App\Models\PaymentRoute;
use App\Models\User;
use App\Services\Orchestration\Contracts\PaymentProviderAdapterInterface;
use App\Services\Orchestration\DTO\PaymentRouteResult;
use App\Services\Orchestration\Exceptions\NoAvailablePaymentRouteException;
use Illuminate\Support\Collection;

class PaymentRouter
{
    /** @var array<string, PaymentProviderAdapterInterface> */
    private array $providers = [];

    /**
     * @param iterable<PaymentProviderAdapterInterface> $providers
     */
    public function __construct(iterable $providers = [])
    {
        foreach ($providers as $provider) {
            $this->registerProvider($provider);
        }
    }

    public function registerProvider(PaymentProviderAdapterInterface $provider): void
    {
        $this->providers[$provider->getName()] = $provider;
    }

    /**
     * @return Collection<int, PaymentProviderAdapterInterface>
     */
    public function getProviders(): Collection
    {
        return collect($this->providers);
    }

    public function selectRoute(
        User $user,
        string $currency,
        float $amount,
        string $destinationCountry,
        array $slaPolicies = []
    ): PaymentRouteResult {
        $currency = strtoupper($currency);
        $destinationCountry = strtoupper($destinationCountry);

        $routes = PaymentRoute::query()
            ->where('currency', $currency)
            ->where('destination_country', $destinationCountry)
            ->where(function ($query) use ($amount) {
                $query->whereNull('max_amount')
                    ->orWhere('max_amount', '>=', $amount);
            })
            ->get()
            ->filter(function (PaymentRoute $route) {
                return isset($this->providers[$route->provider]);
            })
            ->sort(function (PaymentRoute $left, PaymentRoute $right) {
                return [$left->priority, (float) $left->fee] <=> [$right->priority, (float) $right->fee];
            })
            ->values();

        foreach ($routes as $route) {
            $provider = $this->providers[$route->provider];

            if (! $provider->isAvailable($user, $currency, $destinationCountry)) {
                continue;
            }

            $slaProfile = $provider->getSlaProfile($user, $currency, $destinationCountry);
            $kpiMetrics = $provider->getKpiMetrics($user, $currency, $destinationCountry);

            if (! $this->passesPolicies($provider, $route, $slaProfile, $kpiMetrics, $user, $amount, $currency, $destinationCountry, $slaPolicies)) {
                continue;
            }

            return new PaymentRouteResult($provider, $route, $slaProfile, $kpiMetrics);
        }

        throw new NoAvailablePaymentRouteException('No payment routes matched the requested criteria.');
    }

    private function passesPolicies(
        PaymentProviderAdapterInterface $provider,
        PaymentRoute $route,
        array $slaProfile,
        array $kpiMetrics,
        User $user,
        float $amount,
        string $currency,
        string $destinationCountry,
        array $slaPolicies
    ): bool {
        if (! $this->passesRouteThresholds($route, $slaProfile, $kpiMetrics)) {
            return false;
        }

        foreach ($slaPolicies as $policy) {
            if (!\is_callable($policy)) {
                continue;
            }

            $result = $policy($provider, $route, $slaProfile, $kpiMetrics, $user, $amount, $currency, $destinationCountry);

            if (! $result) {
                return false;
            }
        }

        return true;
    }

    private function passesRouteThresholds(PaymentRoute $route, array $slaProfile, array $kpiMetrics): bool
    {
        $thresholds = $route->sla_thresholds;

        if (!\is_array($thresholds) || $thresholds === []) {
            return true;
        }

        $normalised = $this->normaliseThresholds($thresholds);

        foreach ($normalised['sla'] as $metric => $threshold) {
            if (! array_key_exists($metric, $slaProfile)) {
                return false;
            }

            if (! $this->compareMetric($metric, $slaProfile[$metric], $threshold)) {
                return false;
            }
        }

        foreach ($normalised['kpi'] as $metric => $threshold) {
            if (! array_key_exists($metric, $kpiMetrics)) {
                return false;
            }

            if (! $this->compareMetric($metric, $kpiMetrics[$metric], $threshold)) {
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
            $normalised['sla'] = \is_array($thresholds['sla'] ?? null) ? $this->flattenNumericValues($thresholds['sla']) : [];
            $normalised['kpi'] = \is_array($thresholds['kpi'] ?? null) ? $this->flattenNumericValues($thresholds['kpi']) : [];

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
            if (\is_array($value)) {
                continue;
            }

            if (! \is_numeric($value)) {
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
}
