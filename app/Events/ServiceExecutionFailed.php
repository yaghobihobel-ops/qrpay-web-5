<?php

namespace App\Events;

use App\Services\Monitoring\DomainOperationContext;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ServiceExecutionFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public DomainOperationContext $context,
        public Throwable $exception,
        public int $failureCount,
        public int $threshold
    ) {
    }
}
