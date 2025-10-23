<?php

namespace App\Support\Queue;

use Carbon\Carbon;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class QueueMetricsRecorder
{
    protected const TIMER_PREFIX = 'queue:metrics:runtime:';

    public static function register(): void
    {
        Queue::before(function (JobProcessing $event) {
            static::recordStart($event);
        });

        Queue::after(function (JobProcessed $event) {
            static::recordProcessed($event);
        });

        Queue::failing(function (JobFailed $event) {
            static::recordFailure($event);
        });
    }

    protected static function recordStart(JobProcessing $event): void
    {
        if ($jobId = static::jobId($event->job)) {
            Cache::put(static::TIMER_PREFIX . $jobId, microtime(true), now()->addMinutes(10));
        }

        $delayMs = null;
        if (method_exists($event->job, 'availableAt')) {
            $availableAt = $event->job->availableAt();
            if ($availableAt) {
                $delayMs = max(0, now()->diffInMilliseconds(Carbon::createFromTimestamp($availableAt)));
            }
        }

        Log::channel('queue-metrics')->info('Job processing started', [
            'job' => $event->job->resolveName(),
            'queue' => $event->job->getQueue(),
            'connection' => $event->connectionName,
            'delay_ms' => $delayMs,
        ]);
    }

    protected static function recordProcessed(JobProcessed $event): void
    {
        $runtimeMs = null;
        if ($jobId = static::jobId($event->job)) {
            $started = Cache::pull(static::TIMER_PREFIX . $jobId);
            if ($started) {
                $runtimeMs = round((microtime(true) - $started) * 1000, 2);
            }
        }

        Log::channel('queue-metrics')->info('Job processed', [
            'job' => $event->job->resolveName(),
            'queue' => $event->job->getQueue(),
            'connection' => $event->connectionName,
            'runtime_ms' => $runtimeMs,
        ]);
    }

    protected static function recordFailure(JobFailed $event): void
    {
        if ($jobId = static::jobId($event->job)) {
            Cache::forget(static::TIMER_PREFIX . $jobId);
        }

        Log::channel('queue-metrics')->warning('Job failed', [
            'job' => $event->job->resolveName(),
            'queue' => $event->job->getQueue(),
            'connection' => $event->connectionName,
            'exception' => $event->exception->getMessage(),
        ]);
    }

    protected static function jobId($job): ?string
    {
        if (method_exists($job, 'getJobId')) {
            return (string) $job->getJobId();
        }

        return null;
    }
}
