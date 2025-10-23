<?php

namespace App\Services\Payout;

class PayoutResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly array $data = []
    ) {
    }

    public static function success(string $message, array $data = []): self
    {
        return new self(true, $message, $data);
    }

    public static function failure(string $message, array $data = []): self
    {
        return new self(false, $message, $data);
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'data' => $this->data,
        ];
    }
}
