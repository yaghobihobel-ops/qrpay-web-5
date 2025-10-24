<?php

namespace App\Contracts\Providers;

/**
 * Crypto bridge providers offer on/off-ramp connectivity for fallback routes.
 */
interface CryptoBridgeInterface
{
    /**
     * Convert fiat or wallet balance into a crypto asset for transfer.
     */
    public function onRamp(array $payload): array;

    /**
     * Convert a crypto asset into fiat or wallet balance on the destination side.
     */
    public function offRamp(array $payload): array;

    /**
     * Provide cryptographic proofs or attestations for regulatory reporting.
     */
    public function proofs(array $payload): array;

    /**
     * Retrieve the status of a blockchain transaction relevant to the bridge.
     */
    public function chainTxStatus(array $payload): array;
}
