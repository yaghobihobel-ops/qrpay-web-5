<?php

namespace App\Services\Workflow\Exceptions;

use RuntimeException;

class InvalidStageTransitionException extends RuntimeException
{
    public static function make(string $from, string $to): self
    {
        return new self("Cannot transition transaction workflow from [{$from}] to [{$to}].");
    }
}
