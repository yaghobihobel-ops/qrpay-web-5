<?php

namespace App\Support\Reconciliation;

use App\Models\ReconciliationEvent;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class SettlementReportGenerator
{
    /**
     * Generate an aggregated settlement view grouped by provider and currency.
     */
    public function generate(?CarbonImmutable $start = null, ?CarbonImmutable $end = null): Collection
    {
        $query = ReconciliationEvent::query();

        if ($start) {
            $query->where('created_at', '>=', $start);
        }

        if ($end) {
            $query->where('created_at', '<=', $end);
        }

        return $query
            ->selectRaw('country_code, channel, provider_key, status, COUNT(*) as total_events')
            ->selectRaw('SUM(CASE WHEN signature_valid = 1 THEN 1 ELSE 0 END) as valid_signatures')
            ->selectRaw('MIN(created_at) as first_seen, MAX(created_at) as last_seen')
            ->groupBy('country_code', 'channel', 'provider_key', 'status')
            ->orderBy('country_code')
            ->orderBy('channel')
            ->orderBy('provider_key')
            ->get()
            ->map(function ($row) {
                return [
                    'country_code' => $row->country_code,
                    'channel' => $row->channel,
                    'provider_key' => $row->provider_key,
                    'status' => $row->status,
                    'total_events' => (int) $row->total_events,
                    'valid_signatures' => (int) $row->valid_signatures,
                    'first_seen' => $row->first_seen,
                    'last_seen' => $row->last_seen,
                ];
            });
    }
}
