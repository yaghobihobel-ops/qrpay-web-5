<?php

namespace Modules\Country\CN;

use App\Contracts\Countries\CountryModuleInterface;
use App\Contracts\Providers\CardIssuerInterface;
use App\Contracts\Providers\CryptoBridgeInterface;
use App\Contracts\Providers\FXProviderInterface;
use App\Contracts\Providers\KYCProviderInterface;
use App\Contracts\Providers\PaymentProviderInterface;
use App\Contracts\Providers\TopUpProviderInterface;
use Modules\Country\CN\Providers\MockCnCardIssuer;
use Modules\Country\CN\Providers\MockCnFxProvider;

class CNCountryModule implements CountryModuleInterface
{
    public function code(): string
    {
        return 'CN';
    }

    public function name(): string
    {
        return 'China';
    }

    public function currencies(): array
    {
        return ['CNY'];
    }

    public function defaultProviders(): array
    {
        return [
            PaymentProviderInterface::class => null,
            TopUpProviderInterface::class => null,
            FXProviderInterface::class => MockCnFxProvider::class,
            KYCProviderInterface::class => null,
            CardIssuerInterface::class => MockCnCardIssuer::class,
            CryptoBridgeInterface::class => null,
        ];
    }

    public function metadata(): array
    {
        return [
            'timezone' => 'Asia/Shanghai',
            'default_locale' => 'zh',
            'supported_locales' => ['zh', 'en'],
        ];
    }
}
