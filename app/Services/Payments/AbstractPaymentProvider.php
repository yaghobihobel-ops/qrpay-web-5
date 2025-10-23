<?php

namespace App\Services\Payments;

use App\Http\Helpers\PaymentGateway;
use BadMethodCallException;

abstract class AbstractPaymentProvider implements PaymentProviderInterface
{
    /**
     * Provider specific configuration loaded from config/payments.php.
     *
     * @var array<string, mixed>
     */
    protected array $config;
    protected string $defaultInitializeMethod = '';

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function initialize(PaymentGateway $gateway, array $context = [])
    {
        throw new BadMethodCallException(static::class . ' does not support initialization.');
    }

    public function capture(PaymentGateway $gateway, array $context = [])
    {
        return $this->callGatewayMethod($gateway, $context['method'] ?? null, $context['payload'] ?? null, 'capture');
    }

    public function refund(PaymentGateway $gateway, array $context = [])
    {
        return $this->callGatewayMethod($gateway, $context['method'] ?? null, $context['payload'] ?? null, 'refund');
    }

    /**
     * Proxy a dynamic method call to the PaymentGateway helper when available.
     */
    protected function callGatewayMethod(
        PaymentGateway $gateway,
        ?string $method,
        $payload,
        string $operation
    ) {
        if ($method && method_exists($gateway, $method)) {
            $arguments = [];

            if (is_array($payload) && array_key_exists('arguments', $payload)) {
                $arguments = $payload['arguments'];
            } elseif ($payload !== null) {
                $arguments = [$payload];
            }

            return $gateway->$method(...$arguments);
        }

        throw new BadMethodCallException(sprintf(
            '%s does not support %s via %s.',
            static::class,
            $operation,
            $method ?: 'an unspecified method'
        ));
    }

    public function defaultInitializeMethod(): string
    {
        return $this->defaultInitializeMethod;
    }
}
