<?php

namespace App\Services\Payout;

class PayoutResponse
{
    public function __construct(
        protected bool $successful,
        protected array $data = [],
        protected ?string $message = null,
        protected int $statusCode = 0
    ) {
    }

    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return $this->data;
    }

    public function message(): ?string
    {
        return $this->message;
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }
}
