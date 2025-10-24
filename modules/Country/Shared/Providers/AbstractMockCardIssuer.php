<?php

namespace Modules\Country\Shared\Providers;

use App\Contracts\Providers\CardIssuerInterface;
use Illuminate\Support\Str;

abstract class AbstractMockCardIssuer implements CardIssuerInterface
{
    abstract protected function countryCode(): string;

    abstract protected function currency(): string;

    protected function issuerName(): string
    {
        return sprintf('Mock %s Issuer', $this->countryCode());
    }

    public function issueVirtual(array $payload): array
    {
        $card = $this->buildBaseCard('VIRTUAL', $payload);
        $card['status'] = 'ACTIVE';
        $card['pan_masked'] = $payload['pan_masked'] ?? $this->maskPan($this->countryCode());
        $card['cardholder'] = $payload['cardholder'] ?? 'Demo User';
        $card['expiry'] = $payload['expiry'] ?? now()->addYears(3)->format('m/Y');

        return $card;
    }

    public function issuePhysical(array $payload): array
    {
        $card = $this->buildBaseCard('PHYSICAL', $payload);
        $card['status'] = 'IN_PRODUCTION';
        $card['shipping'] = [
            'status' => 'PENDING_DISPATCH',
            'estimated_dispatch' => now()->addDays(3)->toDateString(),
            'estimated_delivery' => now()->addDays(10)->toDateString(),
        ];

        return $card;
    }

    public function activate(array $payload): array
    {
        return [
            'card_id' => $payload['card_id'] ?? $this->generateCardId(),
            'status' => 'ACTIVE',
            'activated_at' => now()->toIso8601String(),
            'metadata' => $this->mergeMetadata($payload),
        ];
    }

    public function block(array $payload): array
    {
        $permanent = (bool) ($payload['permanent'] ?? false);

        return [
            'card_id' => $payload['card_id'] ?? $this->generateCardId(),
            'status' => $permanent ? 'PERMANENTLY_BLOCKED' : 'TEMPORARILY_BLOCKED',
            'reason' => $payload['reason'] ?? null,
            'blocked_at' => now()->toIso8601String(),
            'metadata' => $this->mergeMetadata($payload),
        ];
    }

    public function limits(array $payload): array
    {
        return [
            'card_id' => $payload['card_id'] ?? $this->generateCardId(),
            'currency' => $this->currency(),
            'limits' => $this->normalizeLimits($payload['limits'] ?? []),
            'updated_at' => now()->toIso8601String(),
        ];
    }

    protected function buildBaseCard(string $type, array $payload): array
    {
        return [
            'card_id' => $payload['card_id'] ?? $this->generateCardId(),
            'card_type' => $type,
            'currency' => $payload['currency'] ?? $this->currency(),
            'kyc_tier' => $payload['kyc_tier'] ?? 'standard',
            'limits' => $this->normalizeLimits($payload['limits'] ?? []),
            'metadata' => $this->mergeMetadata($payload),
            'issued_at' => now()->toIso8601String(),
        ];
    }

    protected function mergeMetadata(array $payload): array
    {
        $metadata = $payload['metadata'] ?? [];

        $metadata['country'] = $this->countryCode();
        $metadata['issuer'] = $this->issuerName();

        if (isset($payload['user_id'])) {
            $metadata['user_id'] = $payload['user_id'];
        }

        if (isset($payload['customer_reference'])) {
            $metadata['customer_reference'] = $payload['customer_reference'];
        }

        return $metadata;
    }

    protected function normalizeLimits(array $limits): array
    {
        $defaults = [
            'daily' => 1000,
            'monthly' => 10000,
            'per_transaction' => 500,
        ];

        foreach ($limits as $key => $value) {
            if ($value === null) {
                unset($limits[$key]);
            }
        }

        return array_merge($defaults, $limits);
    }

    protected function maskPan(string $countryCode): string
    {
        return match ($countryCode) {
            'IR' => '5022********1234',
            'CN' => '6214********5678',
            'TR' => '4543********9876',
            default => '4000********0000',
        };
    }

    protected function generateCardId(): string
    {
        return $this->countryCode() . '-' . strtoupper(Str::random(10));
    }
}
