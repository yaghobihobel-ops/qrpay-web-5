<?php

namespace App\Contracts\Providers;

/**
 * Card issuer integrations (virtual and physical) must implement this contract.
 */
interface CardIssuerInterface
{
    /**
     * Issue a virtual card for a wallet user.
     */
    public function issueVirtual(array $payload): array;

    /**
     * Issue a physical card for a wallet user.
     */
    public function issuePhysical(array $payload): array;

    /**
     * Activate a card once delivered to the customer.
     */
    public function activate(array $payload): array;

    /**
     * Block a card (temporary or permanent) based on risk signals.
     */
    public function block(array $payload): array;

    /**
     * Retrieve or update the spending limits for a card.
     */
    public function limits(array $payload): array;
}
