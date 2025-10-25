<?php

namespace App\Jobs\Risk;

use App\Events\Risk\RiskDecisionMade;
use App\Models\Transaction;
use App\Services\Risk\RiskDecisionEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessRiskDecision implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(private readonly int $transactionId)
    {
        $this->onQueue('risk');
    }

    public function handle(RiskDecisionEngine $engine): void
    {
        $transaction = Transaction::query()
            ->with(['user', 'merchant', 'agent', 'currency'])
            ->find($this->transactionId);

        if (! $transaction) {
            return;
        }

        $result = $engine->decide($transaction);

        $transaction->forceFill([
            'risk_decision' => $result['decision'],
            'risk_score' => $result['score'],
            'risk_metadata' => $result,
        ])->save();

        RiskDecisionMade::dispatch($transaction, $result);
    }
}
