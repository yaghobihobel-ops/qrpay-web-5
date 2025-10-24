<?php

namespace Modules\Country\TR;

use App\Contracts\Countries\CountryModuleInterface;
use App\Contracts\Providers\CardIssuerInterface;
use App\Contracts\Providers\CryptoBridgeInterface;
use App\Contracts\Providers\FXProviderInterface;
use App\Contracts\Providers\KYCProviderInterface;
use App\Contracts\Providers\PaymentProviderInterface;
use App\Contracts\Providers\TopUpProviderInterface;
use Modules\Country\TR\Providers\MockTrCardIssuer;
use Modules\Country\TR\Providers\MockTrTopUpProvider;

class TRCountryModule implements CountryModuleInterface
{
    public function code(): string
    {
        return 'TR';
    }

    public function name(): string
    {
        return 'Turkey';
    }

    public function currencies(): array
    {
        return ['TRY'];
    }

    public function defaultProviders(): array
    {
        return [
            PaymentProviderInterface::class => null,
            TopUpProviderInterface::class => MockTrTopUpProvider::class,
            FXProviderInterface::class => null,
            KYCProviderInterface::class => null,
            CardIssuerInterface::class => MockTrCardIssuer::class,
            CryptoBridgeInterface::class => null,
        ];
    }

    public function metadata(): array
    {
        return [
            'timezone' => 'Europe/Istanbul',
            'default_locale' => 'tr',
            'supported_locales' => ['tr', 'en'],
        ];
    }
}
