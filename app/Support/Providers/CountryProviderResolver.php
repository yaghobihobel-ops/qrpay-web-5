<?php

namespace App\Support\Providers;

use App\Contracts\Countries\CountryModuleInterface;
use App\Support\Countries\CountryModuleRegistry;
use Illuminate\Contracts\Container\Container;

class CountryProviderResolver
{
    /**
     * @var array<string, array<class-string, object>>
     */
    protected array $resolved = [];

    /**
     * @param array<class-string, class-string|null> $globalBindings
     * @param array<string, array<class-string, class-string|null>> $countryOverrides
     */
    public function __construct(
        protected Container $container,
        protected CountryModuleRegistry $registry,
        protected array $globalBindings = [],
        protected array $countryOverrides = [],
    ) {
    }

    /**
     * Resolve a provider implementation for the given contract and optional
     * country code.
     */
    public function resolve(string $contract, ?string $countryCode = null): ?object
    {
        if ($countryCode) {
            $instance = $this->resolveForCountry($contract, $countryCode);

            if ($instance) {
                return $instance;
            }
        }

        return $this->resolveGlobal($contract);
    }

    /**
     * Determine the concrete class that should satisfy the given contract for a
     * specific country. Overrides take priority over module defaults.
     */
    public function classFor(string $contract, ?string $countryCode = null): ?string
    {
        if ($countryCode) {
            $class = $this->classForCountry($contract, $countryCode);

            if ($class) {
                return $class;
            }
        }

        $global = $this->globalBindings[$contract] ?? null;

        return $this->normalizeClass($global);
    }

    protected function resolveForCountry(string $contract, string $countryCode): ?object
    {
        if (isset($this->resolved[$countryCode][$contract])) {
            return $this->resolved[$countryCode][$contract];
        }

        $class = $this->classForCountry($contract, $countryCode);

        if (! $class) {
            return null;
        }

        $instance = $this->container->make($class);

        return $this->resolved[$countryCode][$contract] = $instance;
    }

    protected function resolveGlobal(string $contract): ?object
    {
        if (isset($this->resolved['global'][$contract])) {
            return $this->resolved['global'][$contract];
        }

        $class = $this->normalizeClass($this->globalBindings[$contract] ?? null);

        if (! $class) {
            return null;
        }

        $instance = $this->container->make($class);

        return $this->resolved['global'][$contract] = $instance;
    }

    protected function classForCountry(string $contract, string $countryCode): ?string
    {
        $override = $this->countryOverrides[$countryCode][$contract] ?? null;

        if ($class = $this->normalizeClass($override)) {
            return $class;
        }

        $module = $this->registry->resolve($countryCode);

        if (! $module instanceof CountryModuleInterface) {
            return null;
        }

        $default = $module->defaultProviders()[$contract] ?? null;

        return $this->normalizeClass($default);
    }

    protected function normalizeClass(?string $class): ?string
    {
        if (! $class) {
            return null;
        }

        return class_exists($class) ? $class : null;
    }
}
