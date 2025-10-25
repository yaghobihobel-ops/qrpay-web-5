<?php

namespace App\Services\Compliance;

class ComplianceResult
{
    public function __construct(
        public readonly bool $cleared,
        public readonly string $message = '',
        public readonly array $checks = []
    ) {
    }

    public static function passed(array $checks = []): self
    {
        return new self(true, '', $checks);
    }

    public static function failed(string $message, array $checks = []): self
    {
        return new self(false, $message, $checks);
    }

    public function isCleared(): bool
    {
        return $this->cleared;
    }
}
