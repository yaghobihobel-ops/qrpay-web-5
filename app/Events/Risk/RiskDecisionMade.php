<?php

namespace App\Events\Risk;

use App\Models\Transaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RiskDecisionMade
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param array<string, mixed> $result
     */
    public function __construct(public Transaction $transaction, public array $result)
    {
    }
}
