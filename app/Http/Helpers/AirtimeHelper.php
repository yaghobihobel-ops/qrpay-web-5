<?php

namespace App\Http\Helpers;

use App\Services\Contracts\AirtimeProvider;

class AirtimeHelper
{
    public const AIRTIME_CACHE_KEY = 'airtime_api_{provider}_{env}';

    public const STATUS_SUCCESS = 'SUCCESSFUL';
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_PROCESSING = 'PROCESSING';
    public const STATUS_REFUNDED = 'REFUNDED';
    public const STATUS_FAILED = 'FAILED';

    public const PRICE_TYPES = [
        'FIXED' => 'FIXED',
        'RANGE' => 'RANGE',
    ];

    protected AirtimeProvider $provider;

    public function __construct(?AirtimeProvider $provider = null)
    {
        $this->provider = $provider ?? app(AirtimeProvider::class);
    }

    public function getCountries($iso = null): array
    {
        return $this->provider->getCountries($iso);
    }

    public function autoDetectOperator($phone, $iso)
    {
        return $this->provider->autoDetectOperator($phone, $iso);
    }

    public function makeTopUp(array $data): array
    {
        return $this->provider->makeTopUp($data);
    }
}
