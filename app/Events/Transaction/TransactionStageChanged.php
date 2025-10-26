<?php

namespace App\Events\Transaction;

use App\Models\Transaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionStageChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Transaction $transaction,
        public string $from,
        public string $to,
        public array $context,
        public ?string $callbackJob
    ) {
    }
}
