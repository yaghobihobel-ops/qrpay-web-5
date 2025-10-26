<?php

namespace App\Services\Payments\Regional;

class CnyGateway extends AbstractRegionalPaymentProvider
{
    protected string $currencyCode = 'CNY';

    protected string $name = 'Mainland China Gateway';

    protected function meta(array $payload): array
    {
        return array_merge(parent::meta($payload), [
            'clearing_bank' => $this->config['connector_bank'] ?? null,
        ]);
    }
}
