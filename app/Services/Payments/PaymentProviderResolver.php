<?php

namespace App\Services\Payments;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

class PaymentProviderResolver
{
    public function __construct(private readonly Container $container)
    {
    }

    public function resolve(string $serviceId): PaymentProviderInterface
    {
        if (!class_exists($serviceId) && !$this->container->bound($serviceId)) {
            throw new InvalidArgumentException(sprintf('Payment provider [%s] is not bound in the container.', $serviceId));
        }

        $provider = $this->container->make($serviceId);

        if (!$provider instanceof PaymentProviderInterface) {
            throw new InvalidArgumentException(sprintf('Resolved payment provider [%s] must implement %s.', $serviceId, PaymentProviderInterface::class));
        }

        return $provider;
    }
}
