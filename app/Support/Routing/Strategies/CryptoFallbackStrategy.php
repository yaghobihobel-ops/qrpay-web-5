<?php

namespace App\Support\Routing\Strategies;

use App\Contracts\Providers\CryptoBridgeInterface;
use App\Contracts\Routing\RouteStrategyInterface;
use App\Support\Providers\CountryProviderResolver;
use App\Support\Routing\RouteOption;
use App\Support\Routing\RoutingContext;

class CryptoFallbackStrategy implements RouteStrategyInterface
{
    public function __construct(
        protected CountryProviderResolver $providers,
    ) {
    }

    public function evaluate(RoutingContext $context): ?RouteOption
    {
        $cryptoBridge = $this->providers->classFor(CryptoBridgeInterface::class, $context->sourceCountry())
            ?? $this->providers->classFor(CryptoBridgeInterface::class, $context->destinationCountry())
            ?? $this->providers->classFor(CryptoBridgeInterface::class);

        if (! $cryptoBridge) {
            return null;
        }

        return new RouteOption(
            routeType: 'CRYPTO',
            providerClass: $cryptoBridge,
            estimatedFee: $context->metadata()['crypto_fee'] ?? null,
            estimatedEtaMinutes: $context->metadata()['crypto_eta'] ?? null,
            metadata: [
                'bridge_provider' => $cryptoBridge,
            ],
        );
    }
}
