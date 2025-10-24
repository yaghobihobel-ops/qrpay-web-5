<?php

namespace App\Support\Routing\Strategies;

use App\Contracts\Providers\FXProviderInterface;
use App\Contracts\Routing\RouteStrategyInterface;
use App\Support\Providers\CountryProviderResolver;
use App\Support\Routing\RouteOption;
use App\Support\Routing\RoutingContext;

class CorrespondentRouteStrategy implements RouteStrategyInterface
{
    public function __construct(
        protected CountryProviderResolver $providers,
    ) {
    }

    public function evaluate(RoutingContext $context): ?RouteOption
    {
        $fxProvider = $this->providers->classFor(FXProviderInterface::class, $context->sourceCountry())
            ?? $this->providers->classFor(FXProviderInterface::class, $context->destinationCountry())
            ?? $this->providers->classFor(FXProviderInterface::class);

        if (! $fxProvider) {
            return null;
        }

        return new RouteOption(
            routeType: 'CORRESPONDENT',
            providerClass: $fxProvider,
            estimatedFee: $context->metadata()['correspondent_fee'] ?? null,
            estimatedEtaMinutes: $context->metadata()['correspondent_eta'] ?? null,
            metadata: [
                'fx_provider' => $fxProvider,
            ],
        );
    }
}
