<?php

namespace App\Services\Payments\Regional;

class TryGateway extends AbstractRegionalPaymentProvider
{
    protected string $currencyCode = 'TRY';

    protected string $name = 'Turkey Banking Gateway';

    protected function meta(array $payload): array
    {
        return array_merge(parent::meta($payload), [
            'interbank_network' => $this->config['network'] ?? null,
        ]);
    }
}
