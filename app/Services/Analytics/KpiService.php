<?php

namespace App\Services\Analytics;

use Carbon\CarbonInterval;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class KpiService
{
    /**
     * @return array<string, mixed>
     */
    public function getRealtimeSnapshot(): array
    {
        return Cache::remember('analytics.kpis.realtime', CarbonInterval::minutes(1), function () {
            return [
                'conversion_rate' => $this->conversionRate(),
                'provider_latency_ms' => $this->providerLatency(),
                'user_error_rate' => $this->userErrorRate(),
            ];
        });
    }

    private function conversionRate(): float
    {
        $completed = (float) DB::table('transactions')->where('status', 'completed')->count();
        $initiated = (float) DB::table('transactions')->count();

        if ($initiated === 0.0) {
            return 0.0;
        }

        return round(($completed / $initiated) * 100, 2);
    }

    private function providerLatency(): float
    {
        $latency = DB::table('provider_latency_metrics')
            ->latest('captured_at')
            ->value('p95_latency_ms');

        return (float) ($latency ?? 0.0);
    }

    private function userErrorRate(): float
    {
        $errors = (float) DB::table('transaction_errors')->where('is_user_error', true)->count();
        $total = (float) DB::table('transaction_errors')->count();

        if ($total === 0.0) {
            return 0.0;
        }

        return round(($errors / $total) * 100, 2);
    }
}
