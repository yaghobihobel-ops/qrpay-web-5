<?php

namespace App\Contracts\Providers;

/**
 * Country-specific KYC/AML providers must implement this contract to support
 * onboarding flows and continuous monitoring.
 */
interface KYCProviderInterface
{
    /**
     * Initiate a KYC session for a user.
     */
    public function start(array $payload): array;

    /**
     * Submit KYC documents or additional data points.
     */
    public function submitDocs(array $payload): array;

    /**
     * Fetch the status of a KYC verification request.
     */
    public function status(array $payload): array;

    /**
     * Return a risk score or assessment for a verified profile.
     */
    public function riskScore(array $payload): array;
}
