<?php

namespace App\Support\Localization;

use App\Support\Countries\CountryModuleRegistry;

/**
 * Aggregates locale metadata and country-specific localisation rules without
 * requiring mutations to Laravel's translation subsystem.
 */
class LocaleManager
{
    /**
     * @param array<string, array<string, mixed>> $locales
     */
    public function __construct(
        protected array $locales,
        protected string $default,
        protected string $fallback,
        protected CountryModuleRegistry $modules,
    ) {
    }

    /**
     * Return the configured locale catalog keyed by locale code.
     *
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->locales;
    }

    /**
     * Retrieve metadata for a specific locale code.
     *
     * @return array<string, mixed>|null
     */
    public function metadata(string $locale): ?array
    {
        return $this->locales[$locale] ?? null;
    }

    /**
     * Determine whether the locale is right-to-left.
     */
    public function isRtl(string $locale): bool
    {
        return ($this->metadata($locale)['dir'] ?? 'ltr') === 'rtl';
    }

    /**
     * Return configured RTL locales.
     *
     * @return array<int, string>
     */
    public function rtlLocales(): array
    {
        return array_keys(array_filter($this->locales, static function (array $meta): bool {
            return ($meta['dir'] ?? 'ltr') === 'rtl';
        }));
    }

    /**
     * Return the default locale code used when a country module provides no
     * explicit preference.
     */
    public function default(): string
    {
        return $this->default;
    }

    /**
     * Return the configured fallback locale.
     */
    public function fallback(): string
    {
        return $this->fallback;
    }

    /**
     * Determine the primary locale for a given country, considering module
     * metadata and global defaults.
     */
    public function defaultForCountry(string $countryCode): string
    {
        $module = $this->modules->resolve($countryCode, includeDisabled: true);

        if ($module) {
            $metadata = $module->metadata();
            $default = $metadata['default_locale'] ?? null;

            if ($default && isset($this->locales[$default])) {
                return $default;
            }
        }

        $supported = $this->supportedLocalesForCountry($countryCode);

        return $supported[0] ?? $this->default;
    }

    /**
     * Return all supported locales for a given country.
     *
     * @return array<int, string>
     */
    public function supportedLocalesForCountry(string $countryCode): array
    {
        $module = $this->modules->resolve($countryCode, includeDisabled: true);
        $locales = [];

        if ($module) {
            $metadata = $module->metadata();
            $locales = $metadata['supported_locales'] ?? [];

            if (empty($locales) && ! empty($metadata['default_locale'])) {
                $locales[] = $metadata['default_locale'];
            }
        }

        if (empty($locales)) {
            $locales[] = $this->default;
        }

        $locales = array_values(array_unique(array_filter($locales, function (string $code): bool {
            return isset($this->locales[$code]);
        })));

        return $locales ?: [$this->default];
    }

    /**
     * Build a map of supported locales keyed by country code.
     *
     * @return array<string, array<int, string>>
     */
    public function supportedLocalesMapByCountry(): array
    {
        $map = [];

        foreach (array_keys($this->modules->definitions()) as $countryCode) {
            $map[$countryCode] = $this->supportedLocalesForCountry($countryCode);
        }

        return $map;
    }

    /**
     * Build a map of default locales keyed by country code.
     *
     * @return array<string, string>
     */
    public function defaultLocalesByCountry(): array
    {
        $map = [];

        foreach (array_keys($this->modules->definitions()) as $countryCode) {
            $map[$countryCode] = $this->defaultForCountry($countryCode);
        }

        return $map;
    }
}
