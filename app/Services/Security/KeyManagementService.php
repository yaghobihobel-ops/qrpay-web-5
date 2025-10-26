<?php

namespace App\Services\Security;

use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class KeyManagementService
{
    public function getSecret(string $service, ?string $field = null): mixed
    {
        $config = $this->getServiceConfig($service);

        if (!($config['enabled'] ?? true)) {
            throw new RuntimeException("Key management disabled for service [{$service}].");
        }

        $cacheKey = "key-management.secrets." . $service;
        $ttl = (int) config('key_management.cache_ttl', 300);

        $payload = Cache::remember($cacheKey, now()->addSeconds(max($ttl, 60)), function () use ($config, $service) {
            return $this->fetchSecret($service, $config);
        });

        if ($field === null) {
            return $payload;
        }

        return Arr::get($payload, $field);
    }

    public function getSigningKey(string $service): string
    {
        $config = $this->getServiceConfig($service);
        $field = Arr::get($config, 'fields.signing_secret', 'signing_secret');
        $secret = $this->getSecret($service, $field);

        if (!is_string($secret) || $secret === '') {
            throw new RuntimeException("Signing secret missing for service [{$service}].");
        }

        return $secret;
    }

    public function getClientCertificate(string $service): ?array
    {
        $config = $this->getServiceConfig($service);
        $certField = Arr::get($config, 'fields.client_cert');
        $keyField = Arr::get($config, 'fields.client_key');

        if (!$certField || !$keyField) {
            return null;
        }

        $certificate = $this->getSecret($service, $certField);
        $key = $this->getSecret($service, $keyField);

        if (!$certificate || !$key) {
            return null;
        }

        return ['cert' => $certificate, 'key' => $key];
    }

    public function rotate(string $service, bool $force = false): bool
    {
        $config = $this->getServiceConfig($service);

        if (!$this->supportsRotation($service)) {
            return false;
        }

        if (!$force && !$this->shouldRotate($service, $config)) {
            return false;
        }

        $driver = $config['driver'] ?? config('key_management.driver', 'vault');

        switch ($driver) {
            case 'vault':
                $this->rotateVaultKey($service, $config);
                break;
            default:
                throw new RuntimeException("Unsupported key rotation driver [{$driver}] for service [{$service}].");
        }

        Cache::forget("key-management.secrets." . $service);
        Cache::put($this->rotationCacheKey($service), now()->toIso8601String());

        return true;
    }

    public function supportsRotation(string $service): bool
    {
        $config = $this->getServiceConfig($service);

        return (bool) Arr::get($config, 'rotation.enabled') && Arr::has($config, 'rotation_endpoint');
    }

    public function listServices(): array
    {
        return array_keys(config('key_management.services', []));
    }

    protected function shouldRotate(string $service, array $config): bool
    {
        $intervalHours = (int) Arr::get($config, 'rotation.interval_hours', 24);
        $lastRotation = Cache::get($this->rotationCacheKey($service));

        if (!$lastRotation) {
            return true;
        }

        $last = CarbonImmutable::parse($lastRotation);

        return $last->addHours($intervalHours)->isPast();
    }

    protected function rotationCacheKey(string $service): string
    {
        return "key-management.rotation." . $service;
    }

    protected function fetchSecret(string $service, array $config): array
    {
        $driver = $config['driver'] ?? config('key_management.driver', 'vault');

        return match ($driver) {
            'vault' => $this->fetchVaultSecret($service, $config),
            default => throw new RuntimeException("Unsupported key management driver [{$driver}] for service [{$service}]."),
        };
    }

    protected function fetchVaultSecret(string $service, array $config): array
    {
        $vault = config('key_management.vault');
        $path = ltrim((string) Arr::get($config, 'secret_path'), '/');

        if (!$vault['base_uri'] || !$vault['token'] || !$path) {
            throw new RuntimeException("Vault configuration incomplete for service [{$service}].");
        }

        $request = Http::withHeaders([
            'X-Vault-Token' => $vault['token'],
        ])->timeout((int) ($vault['timeout'] ?? 5));

        if (!empty($vault['namespace'])) {
            $request->withHeaders(['X-Vault-Namespace' => $vault['namespace']]);
        }

        if (!empty($vault['ca_cert'])) {
            $request->withOptions(['verify' => $vault['ca_cert']]);
        }

        $response = $request->get(rtrim($vault['base_uri'], '/') . '/v1/' . $path);

        if ($response->failed()) {
            Log::error('Failed to fetch secret from Vault', [
                'service' => $service,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException("Unable to fetch secrets for service [{$service}] from Vault.");
        }

        $payload = $response->json();
        $dataPath = Arr::get($config, 'data_path');

        if ($dataPath) {
            $payload = Arr::get($payload, $dataPath, []);
        }

        if (!is_array($payload)) {
            throw new RuntimeException("Unexpected payload format while fetching secrets for service [{$service}].");
        }

        return $payload;
    }

    protected function rotateVaultKey(string $service, array $config): void
    {
        $vault = config('key_management.vault');
        $endpoint = ltrim((string) Arr::get($config, 'rotation_endpoint'), '/');

        if (!$vault['base_uri'] || !$vault['token'] || !$endpoint) {
            throw new RuntimeException("Vault rotation configuration incomplete for service [{$service}].");
        }

        $request = Http::withHeaders([
            'X-Vault-Token' => $vault['token'],
        ])->timeout((int) ($vault['timeout'] ?? 5));

        if (!empty($vault['namespace'])) {
            $request->withHeaders(['X-Vault-Namespace' => $vault['namespace']]);
        }

        if (!empty($vault['ca_cert'])) {
            $request->withOptions(['verify' => $vault['ca_cert']]);
        }

        $response = $request->post(rtrim($vault['base_uri'], '/') . '/v1/' . $endpoint);

        if ($response->failed()) {
            Log::error('Failed to rotate secret in Vault', [
                'service' => $service,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException("Vault rotation failed for service [{$service}].");
        }
    }

    protected function getServiceConfig(string $service): array
    {
        $config = config("key_management.services.{$service}");

        if (!$config) {
            throw new RuntimeException("Key management configuration missing for service [{$service}].");
        }

        return $config;
    }
}
