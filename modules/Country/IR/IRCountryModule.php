<?php

namespace Modules\Country\IR;

use App\Contracts\Countries\CountryModuleInterface;
use App\Contracts\Providers\CardIssuerInterface;
use App\Contracts\Providers\FXProviderInterface;
use App\Contracts\Providers\KYCProviderInterface;
use App\Contracts\Providers\PaymentProviderInterface;
use App\Contracts\Providers\TopUpProviderInterface;
use App\Contracts\Providers\CryptoBridgeInterface;
use Modules\Country\IR\Providers\MockIrCardIssuer;
use Modules\Country\IR\Providers\MockIrPaymentProvider;

class IRCountryModule implements CountryModuleInterface
{
    public function code(): string
    {
        return 'IR';
    }

    public function name(): string
    {
        return 'Iran';
    }

    public function currencies(): array
    {
        return ['IRR', 'IRT'];
    }

    public function defaultProviders(): array
    {
        return [
            PaymentProviderInterface::class => MockIrPaymentProvider::class,
            TopUpProviderInterface::class => null,
            FXProviderInterface::class => null,
            KYCProviderInterface::class => null,
            CardIssuerInterface::class => MockIrCardIssuer::class,
            CryptoBridgeInterface::class => null,
        ];
    }

    public function metadata(): array
    {
        return [
            'timezone' => 'Asia/Tehran',
            'bank_holidays' => [],
            'kyc_levels' => ['basic', 'enhanced'],
            'default_locale' => 'fa',
            'supported_locales' => ['fa', 'en'],
        ];
    }
}
