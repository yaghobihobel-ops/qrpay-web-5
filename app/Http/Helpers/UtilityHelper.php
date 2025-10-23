<?php

namespace App\Http\Helpers;

use App\Services\Contracts\BillPaymentProvider;

class UtilityHelper
{
    public const BILLERS_CACHE_KEY = 'utilities_biller_api_{provider}_{env}';

    protected BillPaymentProvider $provider;

    public function __construct(?BillPaymentProvider $provider = null)
    {
        $this->provider = $provider ?? app(BillPaymentProvider::class);
    }

    public function getBillers(array $params = [], bool $cache = false): array
    {
        return $this->provider->getBillers($params, $cache);
    }

    public function getSingleBiller($id): array
    {
        return $this->provider->getSingleBiller($id);
    }

    public function payUtilityBill(array $data): array
    {
        return $this->provider->payUtilityBill($data);
    }

    public function getTransaction($id): array
    {
        return $this->provider->getTransaction($id);
    }
}
