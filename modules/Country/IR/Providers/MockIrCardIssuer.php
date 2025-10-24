<?php

namespace Modules\Country\IR\Providers;

use Modules\Country\Shared\Providers\AbstractMockCardIssuer;

class MockIrCardIssuer extends AbstractMockCardIssuer
{
    protected function countryCode(): string
    {
        return 'IR';
    }

    protected function currency(): string
    {
        return 'IRR';
    }
}
