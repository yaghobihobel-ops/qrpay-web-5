<?php

namespace App\Jobs;

use App\Services\Analytics\EventPipeline;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAnalyticsEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(private array $payload)
    {
    }

    public function handle(EventPipeline $pipeline): void
    {
        $pipeline->ingest($this->payload);
    }
}
