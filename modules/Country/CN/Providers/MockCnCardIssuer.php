<?php

namespace Modules\Country\CN\Providers;

use Modules\Country\Shared\Providers\AbstractMockCardIssuer;

class MockCnCardIssuer extends AbstractMockCardIssuer
{
    protected function countryCode(): string
    {
        return 'CN';
    }

    protected function currency(): string
    {
        return 'CNY';
    }
}
