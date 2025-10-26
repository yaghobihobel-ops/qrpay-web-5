<?php

namespace App\Listeners;

use App\Events\PayoutWebhookReceived;
use App\Jobs\UpdatePayoutStatusJob;

class QueuePayoutWebhookProcessing
{
    /**
     * Handle the event.
     */
    public function handle(PayoutWebhookReceived $event): void
    {
        UpdatePayoutStatusJob::dispatch($event->payload);
    }
}
