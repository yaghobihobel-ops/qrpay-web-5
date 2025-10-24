<?php

namespace App\Support\Providers;

use App\Contracts\Providers\CardIssuerInterface;
use App\Contracts\Providers\CryptoBridgeInterface;
use App\Contracts\Providers\FXProviderInterface;
use App\Contracts\Providers\KYCProviderInterface;
use App\Contracts\Providers\PaymentProviderInterface;
use App\Contracts\Providers\TopUpProviderInterface;

class ProviderChannelMapper
{
    /**
     * @var array<string, class-string>
     */
    protected array $channelMap = [
        'payment' => PaymentProviderInterface::class,
        'topup' => TopUpProviderInterface::class,
        'top-up' => TopUpProviderInterface::class,
        'kyc' => KYCProviderInterface::class,
        'fx' => FXProviderInterface::class,
        'card' => CardIssuerInterface::class,
        'card-issuer' => CardIssuerInterface::class,
        'crypto' => CryptoBridgeInterface::class,
    ];

    public function contractFor(string $channel): ?string
    {
        return $this->channelMap[strtolower($channel)] ?? null;
    }
}
