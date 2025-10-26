<?php

namespace App\Jobs\BillPay;

use App\Constants\PaymentGatewayConst;
use App\Models\Transaction;
use App\Notifications\User\BillPay\BillPayFromReloadly;
use App\Notifications\User\BillPay\BillPayFromReloadlyRjected;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class SendBillPaymentNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The queue that should process the job.
     */
    public string $queue = 'notifications';

    /**
     * Ensure dispatching waits until database transactions are committed.
     */
    public bool $afterCommit = true;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly int $transactionId,
        public readonly bool $successful,
        public readonly ?string $remoteStatus = null
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $transaction = Transaction::query()
            ->with(['user', 'agent', 'merchant'])
            ->whereKey($this->transactionId)
            ->first();

        if (!$transaction) {
            return;
        }

        if ($this->successful && $transaction->status !== PaymentGatewayConst::STATUSSUCCESS) {
            return;
        }

        if (!$this->successful && $transaction->status !== PaymentGatewayConst::STATUSREJECTED) {
            return;
        }

        $details = json_decode(json_encode($transaction->details), true) ?: [];
        $charges = Arr::get($details, 'charges', []);

        $payload = [
            'trx_id' => $transaction->trx_id,
            'biller_name' => Arr::get($details, 'bill_type_name'),
            'bill_month' => Arr::get($details, 'bill_month'),
            'bill_number' => Arr::get($details, 'bill_number'),
            'sender_amount' => get_amount(
                (float) Arr::get($charges, 'sender_amount', 0),
                Arr::get($charges, 'sender_currency')
            ),
            'status' => $this->successful ? __('success') : __('Failed'),
        ];

        $notifiable = $transaction->user
            ?? $transaction->agent
            ?? $transaction->merchant;

        if (!$notifiable) {
            return;
        }

        try {
            if ($this->successful) {
                $notifiable->notify(new BillPayFromReloadly($notifiable, (object) $payload));
            } else {
                $notifiable->notify(new BillPayFromReloadlyRjected($notifiable, (object) $payload));
            }
        } catch (\Throwable $exception) {
            Log::warning('Unable to send bill payment notification', [
                'transaction_id' => $transaction->id,
                'successful' => $this->successful,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
