<?php

namespace App\Services\View;

use App\Models\Admin\MerchantConfiguration;
use Illuminate\Support\Facades\Cache;

class QrPayGatewayViewModelService
{
    public const MERCHANT_CONFIGURATION_CACHE_KEY = 'qrpay.gateway.merchant_configuration';

    public function getMerchantConfiguration(): ?MerchantConfiguration
    {
        return Cache::rememberForever(self::MERCHANT_CONFIGURATION_CACHE_KEY, function () {
            return MerchantConfiguration::first();
        });
    }

    public function getPaymentGatewayImage(?MerchantConfiguration $configuration): string
    {
        if (!$configuration) {
            return '';
        }

        return get_image($configuration->image, 'merchant-config');
    }

    public static function forgetMerchantConfiguration(): void
    {
        Cache::forget(self::MERCHANT_CONFIGURATION_CACHE_KEY);
    }
}
