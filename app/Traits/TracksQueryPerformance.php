<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait TracksQueryPerformance
{
    protected bool $trackDashboardQueries = false;

    protected function startQueryMonitoring(): void
    {
        $this->trackDashboardQueries = (bool) config('app.debug');

        if (! $this->trackDashboardQueries) {
            return;
        }

        $connection = DB::connection();

        if (method_exists($connection, 'flushQueryLog')) {
            $connection->flushQueryLog();
        }

        $connection->enableQueryLog();
    }

    protected function stopQueryMonitoring(string $context): void
    {
        if (! $this->trackDashboardQueries) {
            return;
        }

        $connection = DB::connection();
        $queries = $connection->getQueryLog();
        $totalTime = collect($queries)->sum('time');

        Log::debug('Dashboard query performance', [
            'context' => $context,
            'query_count' => count($queries),
            'total_time_ms' => $totalTime,
            'sample_queries' => collect($queries)
                ->take(10)
                ->map(static function ($query) {
                    return [
                        'sql' => $query['query'],
                        'time_ms' => $query['time'],
                        'bindings' => $query['bindings'],
                    ];
                })
                ->all(),
        ]);

        $connection->disableQueryLog();
    }
}
