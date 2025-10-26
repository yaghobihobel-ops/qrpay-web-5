<?php

namespace App\Services\Gateway;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Throwable;

class GeoRegionRouter
{
    /**
     * @param  ConfigRepository  $config
     */
    public function __construct(private readonly ConfigRepository $config)
    {
    }

    /**
     * Resolve the target edge region for the incoming request.
     */
    public function resolve(Request $request): array
    {
        $regions = $this->regions();

        if (empty($regions)) {
            return $this->formatRegion('default', [
                'code' => 'default',
                'name' => 'Default Edge',
                'endpoint' => $request->getSchemeAndHttpHost(),
            ], 'fallback');
        }

        $override = $this->resolveOverride($request, $regions);
        if ($override !== null) {
            return $override;
        }

        $country = $this->resolveCountry($request);
        $overrides = $this->countryOverrides();

        if ($country && isset($overrides[$country])) {
            $regionKey = $overrides[$country];
            if (isset($regions[$regionKey])) {
                return $this->formatRegion($regionKey, $regions[$regionKey], 'country-override', [
                    'country' => $country,
                ]);
            }
        }

        $location = $this->lookupLocation($request);
        $regionKey = $this->nearestRegionKey($location, $regions, $this->defaultRegionKey());

        return $this->formatRegion($regionKey, $regions[$regionKey] ?? reset($regions), 'geoip', [
            'country' => $country ?: ($location['iso_code'] ?? null),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function regions(): array
    {
        return $this->config->get('edge.geo.regions', []);
    }

    protected function defaultRegionKey(): string
    {
        $regions = $this->regions();
        $default = (string) $this->config->get('edge.geo.default_region', 'singapore');

        if (!isset($regions[$default])) {
            $default = array_key_first($regions) ?? 'default';
        }

        return $default;
    }

    /**
     * @return array<string, string>
     */
    protected function countryOverrides(): array
    {
        $overrides = $this->config->get('edge.geo.country_overrides', []);

        $normalized = [];
        foreach ($overrides as $country => $regionKey) {
            $normalized[strtoupper((string) $country)] = (string) $regionKey;
        }

        return $normalized;
    }

    protected function resolveCountry(Request $request): ?string
    {
        $header = $this->config->get('edge.geo.country_header', 'X-User-Country');
        $country = $request->headers->get($header) ?: $request->headers->get('X-Country');

        if (!$country) {
            $country = $request->query('country');
        }

        $country = $country ? strtoupper((string) $country) : null;

        return $country ?: null;
    }

    protected function resolveOverride(Request $request, array $regions): ?array
    {
        $header = $this->config->get('edge.geo.override_header', 'X-Edge-Region');
        $value = $request->headers->get($header);

        if ($value) {
            $normalized = Str::lower($value);
            if (isset($regions[$normalized])) {
                return $this->formatRegion($normalized, $regions[$normalized], 'header-override');
            }

            foreach ($regions as $key => $region) {
                if (($region['code'] ?? null) === $value) {
                    return $this->formatRegion($key, $region, 'header-override');
                }
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>|null  $location
     * @param  array<string, array<string, mixed>>  $regions
     */
    protected function nearestRegionKey(?array $location, array $regions, string $default): string
    {
        if (!$location || empty($regions)) {
            return $default;
        }

        $lat = Arr::get($location, 'lat', Arr::get($location, 'latitude'));
        $lon = Arr::get($location, 'lon', Arr::get($location, 'longitude'));

        if ($lat === null || $lon === null) {
            return $default;
        }

        $closestKey = $default;
        $closestDistance = INF;

        foreach ($regions as $key => $region) {
            $coordinates = $region['coordinates'] ?? null;
            if (!is_array($coordinates)) {
                continue;
            }

            $targetLat = $coordinates['lat'] ?? null;
            $targetLon = $coordinates['lon'] ?? null;

            if ($targetLat === null || $targetLon === null) {
                continue;
            }

            $distance = $this->haversineDistance((float) $lat, (float) $lon, (float) $targetLat, (float) $targetLon);
            if ($distance < $closestDistance) {
                $closestDistance = $distance;
                $closestKey = $key;
            }
        }

        return $closestKey;
    }

    /**
     * @return array<string, mixed>
     */
    protected function lookupLocation(Request $request): array
    {
        $ip = $request->getClientIp();

        if (!function_exists('geoip')) {
            return [];
        }

        try {
            $location = geoip()->getLocation($ip);
        } catch (Throwable) {
            return [];
        }

        return is_array($location) ? $location : $location?->toArray() ?? [];
    }

    protected function haversineDistance(float $latFrom, float $lonFrom, float $latTo, float $lonTo): float
    {
        $earthRadius = 6371; // Kilometers

        $latFromRad = deg2rad($latFrom);
        $lonFromRad = deg2rad($lonFrom);
        $latToRad = deg2rad($latTo);
        $lonToRad = deg2rad($lonTo);

        $latDelta = $latToRad - $latFromRad;
        $lonDelta = $lonToRad - $lonFromRad;

        $angle = 2 * asin(
            sqrt(
                pow(sin($latDelta / 2), 2) +
                cos($latFromRad) * cos($latToRad) * pow(sin($lonDelta / 2), 2)
            )
        );

        return $angle * $earthRadius;
    }

    /**
     * @param  array<string, mixed>|false  $region
     */
    protected function formatRegion(string $key, array|false $region, string $source, array $extras = []): array
    {
        $region = is_array($region) ? $region : [];

        return array_filter([
            'key' => $key,
            'code' => $region['code'] ?? $key,
            'name' => $region['name'] ?? Str::title(str_replace('-', ' ', $key)) . ' Edge',
            'endpoint' => $region['endpoint'] ?? null,
            'source' => $source,
            'meta' => $extras,
        ], fn ($value) => $value !== null);
    }
}
