<?php

namespace App\Services\Orchestration;

use App\Models\PaymentRoute;
use App\Models\User;
use App\Services\Orchestration\Contracts\PaymentProviderAdapterInterface;
use App\Services\Orchestration\DTO\PaymentRouteResult;
use App\Services\Orchestration\Exceptions\NoAvailablePaymentRouteException;
use App\Services\Pricing\FeeEngine;
use App\Services\Pricing\Exceptions\PricingRuleNotFoundException;
use Illuminate\Support\Collection;

class PaymentRouter
{
    /** @var array<string, PaymentProviderAdapterInterface> */
    private array $providers = [];

    private ?FeeEngine $feeEngine = null;

    /**
     * @param iterable<PaymentProviderAdapterInterface> $providers
     */
    public function __construct(iterable $providers = [], ?FeeEngine $feeEngine = null)
    {
        foreach ($providers as $provider) {
            $this->registerProvider($provider);
        }

        $this->feeEngine = $feeEngine;
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
        array $slaPolicies = [],
        array $pricingContext = []
    ): PaymentRouteResult {
        $currency = strtoupper($currency);
        $destinationCountry = strtoupper($destinationCountry);
        $transactionType = $pricingContext['transaction_type'] ?? '*';
        $userLevel = $pricingContext['user_level'] ?? 'standard';

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

            $feeQuote = $this->buildFeeQuote($route, $currency, $amount, $transactionType, $userLevel);

            return new PaymentRouteResult($provider, $route, $slaProfile, $kpiMetrics, $feeQuote);
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

    private function buildFeeQuote(
        PaymentRoute $route,
        string $currency,
        float $amount,
        string $transactionType,
        string $userLevel
    ) {
        if (! $this->feeEngine) {
            return null;
        }

        try {
            return $this->feeEngine->quote(
                currency: $currency,
                provider: $route->provider,
                transactionType: $transactionType,
                userLevel: $userLevel,
                amount: $amount
            );
        } catch (PricingRuleNotFoundException $exception) {
            return null;
        }
    }
}
