<?php

namespace App\Services\Payments\Regional;

use App\Contracts\RegionalPaymentProviderInterface;
use App\Services\Payments\InternalWalletService;

class RegionalPaymentManager
{
    protected InternalWalletService $walletService;

    /** @var array<int, array<string, mixed>> */
    protected array $providersConfig;

    /** @var array<string, RegionalPaymentProviderInterface> */
    protected array $resolved = [];

    public function __construct(InternalWalletService $walletService, array $providersConfig = [])
    {
        $this->walletService = $walletService;
        $this->providersConfig = $providersConfig;
    }

    public function resolveByCurrency(string $currency): ?RegionalPaymentProviderInterface
    {
        foreach ($this->providersConfig as $name => $config) {
            $provider = $this->resolveProvider($name, $config);
            if ($provider->supportsCurrency($currency)) {
                return $provider;
            }
        }

        return null;
    }

    public function supports(string $currency): bool
    {
        return $this->resolveByCurrency($currency) !== null;
    }

    /**
     * @return array<int, RegionalPaymentProviderInterface>
     */
    public function all(): array
    {
        $providers = [];
        foreach ($this->providersConfig as $name => $config) {
            $providers[] = $this->resolveProvider($name, $config);
        }

        return $providers;
    }

    protected function resolveProvider(string $name, array $config): RegionalPaymentProviderInterface
    {
        if (isset($this->resolved[$name])) {
            return $this->resolved[$name];
        }

        $class = $config['class'] ?? null;
        if (!$class || !class_exists($class)) {
            throw new \RuntimeException(sprintf('Regional payment provider [%s] is not configured correctly.', $name));
        }

        $this->resolved[$name] = new $class($this->walletService, $config);

        return $this->resolved[$name];
    }
}
