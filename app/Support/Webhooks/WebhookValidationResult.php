<?php

namespace App\Support\Webhooks;

class WebhookValidationResult
{
    public function __construct(
        public readonly bool $valid,
        public readonly ?string $providedSignature,
        public readonly ?string $expectedSignature,
        public readonly string $algorithm,
        public readonly ?string $reason = null,
    ) {
    }
}
