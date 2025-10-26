<?php

namespace App\Listeners\Transaction;

use App\Events\Transaction\TransactionStageFailed;
use App\Services\Workflow\Compensations\MarkTransactionFailed;

class RunTransactionCompensation
{
    public function handle(TransactionStageFailed $event): void
    {
        $compensationClass = $event->compensationClass ?: MarkTransactionFailed::class;

        if (! class_exists($compensationClass)) {
            return;
        }

        $compensation = app($compensationClass);

        if (! method_exists($compensation, 'handle')) {
            return;
        }

        $compensation->handle(
            $event->transaction,
            $event->from,
            $event->to,
            $event->context,
            $event->exception
        );
    }
}
