<?php

namespace App\Support\Cards\Exceptions;

use RuntimeException;

class CardIssuerNotConfiguredException extends RuntimeException
{
    public function __construct(string $countryCode)
    {
        parent::__construct("Card issuing is not configured for country [{$countryCode}].");
    }
}
