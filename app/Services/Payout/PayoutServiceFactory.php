<?php

namespace App\Services\Payout;

use App\Services\Payout\Regional\ChinaBankTransferService;
use App\Services\Payout\Regional\IranCryptoPayoutService;
use App\Services\Payout\Regional\RussiaBankTransferService;
use App\Services\Payout\Regional\TurkeyBankTransferService;
use InvalidArgumentException;

class PayoutServiceFactory
{
    public static function make(string $countryCode, string $channel): PayoutProviderInterface
    {
        $countryCode = strtoupper($countryCode);
        $channel = strtolower($channel);
        $config = config("payouts.countries.$countryCode.providers.$channel");

        if (!$config) {
            throw new InvalidArgumentException("Payout provider configuration missing for {$countryCode} ({$channel}).");
        }

        $class = $config['class'] ?? null;

        if (!$class || !class_exists($class)) {
            throw new InvalidArgumentException("Payout provider class is not defined for {$countryCode} ({$channel}).");
        }

        return new $class($config, $countryCode);
    }

    public static function supportedCountries(): array
    {
        return array_keys(config('payouts.countries', []));
    }
}
