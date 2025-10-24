<?php

namespace App\Support\Countries;

use App\Contracts\Countries\CountryModuleInterface;
use Illuminate\Contracts\Container\Container;

class CountryModuleRegistry
{
    /**
     * @var array<string, CountryModuleInterface>
     */
    protected array $instances = [];

    /**
     * @param array<string, array<string, mixed>> $definitions
     */
    public function __construct(
        protected Container $container,
        protected array $definitions = [],
    ) {
    }

    /**
     * Return the raw module definitions from configuration.
     *
     * @return array<string, array<string, mixed>>
     */
    public function definitions(): array
    {
        return $this->definitions;
    }

    /**
     * Retrieve an instantiated module by its country code.
     */
    public function resolve(string $code, bool $includeDisabled = false): ?CountryModuleInterface
    {
        $definition = $this->definitions[$code] ?? null;

        if (! $definition) {
            return null;
        }

        if (! $includeDisabled && empty($definition['enabled'])) {
            return null;
        }

        $class = $definition['class'] ?? null;

        if (! $class || ! class_exists($class)) {
            return null;
        }

        if (! isset($this->instances[$code])) {
            $module = $this->container->make($class);

            if (! $module instanceof CountryModuleInterface) {
                return null;
            }

            $this->instances[$code] = $module;
        }

        return $this->instances[$code];
    }

    /**
     * @return array<string, CountryModuleInterface>
     */
    public function enabled(): array
    {
        $modules = [];

        foreach ($this->definitions as $code => $definition) {
            if (empty($definition['enabled'])) {
                continue;
            }

            $module = $this->resolve($code, includeDisabled: true);

            if ($module instanceof CountryModuleInterface) {
                $modules[$code] = $module;
            }
        }

        return $modules;
    }

    /**
     * Aggregate provider bindings exposed by all enabled modules.
     *
     * @return array<class-string, class-string>
     */
    public function providerBindingMap(): array
    {
        $bindings = [];

        foreach ($this->enabled() as $module) {
            foreach ($module->defaultProviders() as $contract => $implementation) {
                if (! $implementation) {
                    continue;
                }

                $bindings[$contract] = $implementation;
            }
        }

        return $bindings;
    }

    /**
     * @return array<string, array<class-string, class-string>>
     */
    public function providerMapByCountry(): array
    {
        $map = [];

        foreach ($this->definitions as $code => $definition) {
            $module = $this->resolve($code, includeDisabled: true);

            if (! $module instanceof CountryModuleInterface) {
                continue;
            }

            $entries = [];

            foreach ($module->defaultProviders() as $contract => $implementation) {
                if (! $implementation) {
                    continue;
                }

                $entries[$contract] = $implementation;
            }

            $map[$code] = $entries;
        }

        return $map;
    }
}
