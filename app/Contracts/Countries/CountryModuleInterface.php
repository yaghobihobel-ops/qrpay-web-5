<?php

namespace App\Contracts\Countries;

use App\Contracts\Providers\PaymentProviderInterface;
use App\Contracts\Providers\TopUpProviderInterface;
use App\Contracts\Providers\KYCProviderInterface;
use App\Contracts\Providers\FXProviderInterface;
use App\Contracts\Providers\CardIssuerInterface;
use App\Contracts\Providers\CryptoBridgeInterface;

/**
 * Represents a country-specific module that can contribute provider bindings,
 * metadata, and localisation details without mutating the core application.
 */
interface CountryModuleInterface
{
    /**
     * ISO 3166-1 alpha-2 code for the country (e.g. IR, CN, TR).
     */
    public function code(): string;

    /**
     * Human readable country name for admin displays.
     */
    public function name(): string;

    /**
     * List of supported currency codes ordered by priority.
     *
     * @return array<int, string>
     */
    public function currencies(): array;

    /**
     * Provide default provider bindings contributed by the module.
     *
     * @return array<class-string, class-string|null>
     */
    public function defaultProviders(): array;

    /**
     * Arbitrary metadata describing local formats, holidays, fee rules, etc.
     * Expected keys include:
     *  - `default_locale` (string): preferred locale code for the module.
     *  - `supported_locales` (array<int, string>): additional locale codes
     *    supported for the module's UX.
     *
     * @return array<string, mixed>
     */
    public function metadata(): array;
}
