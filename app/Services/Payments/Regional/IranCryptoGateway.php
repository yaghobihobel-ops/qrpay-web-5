<?php

namespace App\Services\Payments\Regional;

class IranCryptoGateway extends AbstractRegionalPaymentProvider
{
    protected string $currencyCode = 'IRR';

    protected string $name = 'Iran Crypto Gateway';

    protected function meta(array $payload): array
    {
        return array_merge(parent::meta($payload), [
            'blockchain_network' => $this->config['network'] ?? null,
            'settlement_wallet' => $this->config['settlement_account'] ?? null,
        ]);
    }
}
