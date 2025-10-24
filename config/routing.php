<?php

return [
    'strategies' => [
        App\Support\Routing\Strategies\DirectRouteStrategy::class,
        App\Support\Routing\Strategies\CorrespondentRouteStrategy::class,
        App\Support\Routing\Strategies\CryptoFallbackStrategy::class,
    ],
];
