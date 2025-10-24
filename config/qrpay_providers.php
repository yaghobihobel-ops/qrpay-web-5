<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Provider Binding Map
    |--------------------------------------------------------------------------
    | This configuration enables binding provider interfaces to concrete
    | implementations without modifying the application core. Each entry should
    | contain a fully-qualified class name that will be resolved via the Laravel
    | service container. If an entry is null it will be skipped, allowing
    | country modules to register their own bindings dynamically.
    */
    'bindings' => [
        App\Contracts\Providers\PaymentProviderInterface::class => null,
        App\Contracts\Providers\TopUpProviderInterface::class => null,
        App\Contracts\Providers\KYCProviderInterface::class => null,
        App\Contracts\Providers\FXProviderInterface::class => null,
        App\Contracts\Providers\CardIssuerInterface::class => null,
        App\Contracts\Providers\CryptoBridgeInterface::class => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Country Specific Provider Overrides
    |--------------------------------------------------------------------------
    | These definitions allow a country module or future admin configuration to
    | point an interface at a country-specific implementation. The resolver will
    | look here first when attempting to locate a provider for a particular
    | country code before falling back to the module defaults or global binding.
    */
    'countries' => [
        // 'IR' => [
        //     App\Contracts\Providers\PaymentProviderInterface::class => Modules\Country\IR\Providers\MockIrPaymentProvider::class,
        // ],
    ],
];
