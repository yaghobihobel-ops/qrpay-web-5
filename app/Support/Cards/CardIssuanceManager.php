<?php

namespace App\Support\Cards;

use App\Contracts\Providers\CardIssuerInterface;
use App\Support\Cards\Exceptions\CardIssuerNotConfiguredException;
use App\Support\Providers\CountryProviderResolver;
use InvalidArgumentException;

class CardIssuanceManager
{
    public function __construct(
        protected CountryProviderResolver $resolver
    ) {
    }

    public function issue(string $type, string $countryCode, array $payload): array
    {
        $issuer = $this->resolve($countryCode);

        return match (strtoupper($type)) {
            'VIRTUAL' => $issuer->issueVirtual($payload),
            'PHYSICAL' => $issuer->issuePhysical($payload),
            default => throw new InvalidArgumentException("Unsupported card type [{$type}]."),
        };
    }

    public function activate(string $countryCode, array $payload): array
    {
        return $this->resolve($countryCode)->activate($payload);
    }

    public function block(string $countryCode, array $payload): array
    {
        return $this->resolve($countryCode)->block($payload);
    }

    public function limits(string $countryCode, array $payload): array
    {
        return $this->resolve($countryCode)->limits($payload);
    }

    protected function resolve(string $countryCode): CardIssuerInterface
    {
        $issuer = $this->resolver->resolve(CardIssuerInterface::class, strtoupper($countryCode));

        if (! $issuer instanceof CardIssuerInterface) {
            throw new CardIssuerNotConfiguredException($countryCode);
        }

        return $issuer;
    }
}
