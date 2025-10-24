<?php

namespace App\Support\Reconciliation;

use App\Models\ReconciliationEvent;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ReconciliationRecorder
{
    public function record(array $attributes): ReconciliationEvent
    {
        $idempotencyKey = $attributes['idempotency_key'] ?? Str::uuid()->toString();
        $normalized = array_merge(
            [
                'status' => 'RECEIVED',
                'signature_valid' => false,
            ],
            $attributes,
            ['idempotency_key' => $idempotencyKey]
        );

        $normalized['payload'] = $this->normalizeArray($normalized['payload'] ?? []);
        $normalized['headers'] = $this->normalizeArray($normalized['headers'] ?? []);
        $normalized['validation_details'] = $this->normalizeArray($normalized['validation_details'] ?? []);

        return tap(
            ReconciliationEvent::updateOrCreate(
                ['idempotency_key' => $idempotencyKey],
                Arr::except($normalized, ['idempotency_key'])
            )
        )->refresh();
    }

    /**
     * @param mixed $value
     * @return array<mixed>
     */
    protected function normalizeArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : ['raw' => $value];
        }

        return [];
    }
}
