<?php

namespace App\Services\Exchange\Exceptions;

use Exception;

class ExchangeRateException extends Exception
{
    public static function missingRate(string $currency, string $provider): self
    {
        return new self("{$provider} did not return a rate for {$currency}.");
    }

    public static function invalidRate(string $provider): self
    {
        return new self("{$provider} returned an invalid exchange rate.");
    }

    public static function providersFailed(): self
    {
        return new self('All exchange rate providers failed to return data.');
    }
}
