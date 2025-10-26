<?php

return [
    'currency_service' => env('FEATURE_CURRENCY_SERVICE', false),
    'withdrawal_service' => env('FEATURE_WITHDRAWAL_SERVICE', false),
    'exchange_service' => env('FEATURE_EXCHANGE_SERVICE', false),
];
