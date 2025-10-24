<?php

namespace App\Support\Routing\Strategies;

use App\Contracts\Providers\PaymentProviderInterface;
use App\Contracts\Providers\TopUpProviderInterface;
use App\Contracts\Routing\RouteStrategyInterface;
use App\Support\Providers\CountryProviderResolver;
use App\Support\Routing\RouteOption;
use App\Support\Routing\RoutingContext;

class DirectRouteStrategy implements RouteStrategyInterface
{
    public function __construct(
        protected CountryProviderResolver $providers,
    ) {
    }

    public function evaluate(RoutingContext $context): ?RouteOption
    {
        $payment = $this->providers->classFor(PaymentProviderInterface::class, $context->sourceCountry());
        $destinationTopUp = $this->providers->classFor(TopUpProviderInterface::class, $context->destinationCountry());

        if (! $payment || ! $destinationTopUp) {
            return null;
        }

        return new RouteOption(
            routeType: 'DIRECT',
            providerClass: $payment,
            estimatedFee: $context->metadata()['estimated_fee'] ?? null,
            estimatedEtaMinutes: $context->metadata()['direct_eta'] ?? null,
            metadata: [
                'source_payment_provider' => $payment,
                'destination_topup_provider' => $destinationTopUp,
            ],
        );
    }
}
