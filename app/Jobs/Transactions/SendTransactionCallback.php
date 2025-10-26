<?php

namespace App\Jobs\Transactions;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendTransactionCallback implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $transactionId,
        public string $stage,
        public array $payload = []
    ) {
    }

    public function handle(): void
    {
        $transaction = Transaction::find($this->transactionId);

        if (! $transaction) {
            return;
        }

        $details = $this->detailsAsArray($transaction);
        $callbackUrl = Arr::get($this->payload, 'callback_url')
            ?? Arr::get($details, "callbacks.{$this->stage}.url")
            ?? Arr::get($details, 'callback_url');

        if (! $callbackUrl) {
            return;
        }

        $body = array_merge($this->payload, [
            'transaction_id' => $transaction->trx_id,
            'stage' => $this->stage,
        ]);

        try {
            Http::post($callbackUrl, $body);
        } catch (Throwable $throwable) {
            Log::warning('Failed to send transaction callback.', [
                'transaction_id' => $transaction->id,
                'stage' => $this->stage,
                'callback_url' => $callbackUrl,
                'exception' => $throwable->getMessage(),
            ]);
        }
    }

    protected function detailsAsArray(Transaction $transaction): array
    {
        $details = $transaction->details;

        if (empty($details)) {
            return [];
        }

        return json_decode(json_encode($details), true);
    }
}
