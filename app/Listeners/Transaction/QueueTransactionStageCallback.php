<?php

namespace App\Listeners\Transaction;

use App\Events\Transaction\TransactionStageChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class QueueTransactionStageCallback implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(TransactionStageChanged $event): void
    {
        $jobClass = $event->callbackJob;

        if (! $jobClass || ! class_exists($jobClass)) {
            return;
        }

        dispatch(new $jobClass(
            $event->transaction->id,
            $event->to,
            $event->context
        ));
    }
}
