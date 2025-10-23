<?php

namespace App\Services\Compliance;

use App\Models\User;

class ComplianceManager
{
    protected array $config;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?? config('payouts.compliance', []);
    }

    public function approvePayout(?User $user, array $payload): ComplianceResult
    {
        $country = strtoupper($payload['country'] ?? '');
        $checks = [
            'country' => $country,
        ];

        $enhancedCountries = $this->config['enhanced_due_diligence_countries'] ?? [];

        if (!in_array($country, $enhancedCountries, true)) {
            return ComplianceResult::passed($checks);
        }

        if (!($payload['kyc_verified'] ?? false)) {
            return ComplianceResult::failed('KYC verification is required before processing this payout.', $checks + ['kyc_verified' => false]);
        }

        if (!($payload['sanctions_screened'] ?? false)) {
            return ComplianceResult::failed('Sanctions screening must be completed.', $checks + ['sanctions_screened' => false]);
        }

        $requiredDocs = $this->config['document_requirements'][$country] ?? [];
        $providedDocs = $payload['supporting_documents'] ?? [];

        foreach ($requiredDocs as $doc) {
            if (!in_array($doc, $providedDocs, true)) {
                return ComplianceResult::failed("Missing required document: {$doc}.", $checks + ['missing_document' => $doc]);
            }
        }

        if ($user && method_exists($user, 'isSanctioned') && $user->isSanctioned()) {
            return ComplianceResult::failed('The account is blocked due to sanctions.', $checks + ['user_sanctioned' => true]);
        }

        return ComplianceResult::passed($checks + ['enhanced_due_diligence' => true]);
    }
}
