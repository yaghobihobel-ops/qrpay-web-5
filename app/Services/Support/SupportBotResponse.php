<?php

namespace App\Services\Support;

class SupportBotResponse
{
    public function __construct(
        public string $reply,
        public ?string $intent = null,
        public ?float $confidence = null,
        public bool $handoff = false,
        public array $metadata = []
    ) {
    }
}
