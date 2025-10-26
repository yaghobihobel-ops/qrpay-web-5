<?php

namespace App\Services\Analytics;

use App\Jobs\ProcessAnalyticsEvent;

class EventDispatcher
{
    /**
     * @param array<string, mixed> $payload
     */
    public function dispatch(array $payload): void
    {
        ProcessAnalyticsEvent::dispatch($payload);
    }
}
