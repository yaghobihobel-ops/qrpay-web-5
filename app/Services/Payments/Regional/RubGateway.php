<?php

namespace App\Services\Payments\Regional;

class RubGateway extends AbstractRegionalPaymentProvider
{
    protected string $currencyCode = 'RUB';

    protected string $name = 'Russia Settlement Gateway';

    protected function meta(array $payload): array
    {
        return array_merge(parent::meta($payload), [
            'correspondent_bank' => $this->config['connector_bank'] ?? null,
        ]);
    }
}
