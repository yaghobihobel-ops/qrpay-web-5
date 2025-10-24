<?php

namespace Modules\Country\TR\Providers;

use Modules\Country\Shared\Providers\AbstractMockCardIssuer;

class MockTrCardIssuer extends AbstractMockCardIssuer
{
    protected function countryCode(): string
    {
        return 'TR';
    }

    protected function currency(): string
    {
        return 'TRY';
    }
}
