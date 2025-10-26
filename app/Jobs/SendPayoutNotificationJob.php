<?php

namespace App\Jobs;

use App\Models\Transaction;
use App\Notifications\PayoutStatusNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPayoutNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $transactionId, public int $status, public ?string $message = null)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $transaction = Transaction::with(['creator', 'creator_wallet.currency'])->find($this->transactionId);
        if (! $transaction || ! $transaction->creator) {
            Log::warning('Unable to send payout notification.', [
                'transaction_id' => $this->transactionId,
            ]);
            return;
        }

        $transaction->creator->notify(new PayoutStatusNotification($transaction, $this->status, $this->message));
    }
}
