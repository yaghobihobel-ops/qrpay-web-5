<?php

namespace App\Jobs;

use App\Constants\PaymentGatewayConst;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class UpdatePayoutStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param  array<string, mixed>  $payload
     */
    public function __construct(public array $payload)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $reference = Arr::get($this->payload, 'data.reference');
        if (! $reference) {
            Log::warning('Payout webhook payload missing reference.', [
                'payload' => $this->payload,
            ]);
            return;
        }

        $transaction = Transaction::with('creator_wallet')->where('callback_ref', $reference)->first();
        if (! $transaction) {
            Log::warning('Payout webhook reference not found in transactions.', [
                'reference' => $reference,
            ]);
            return;
        }

        $wallet = $transaction->creator_wallet;
        if (! $wallet) {
            Log::warning('Payout webhook missing wallet relation for transaction.', [
                'transaction_id' => $transaction->id,
            ]);
            return;
        }

        $status = Arr::get($this->payload, 'data.status');
        $completeMessage = Arr::get($this->payload, 'data.complete_message');

        $details = json_decode(json_encode($transaction->details), true) ?? [];
        $details['callback_data'] = $this->payload;

        if ($status === 'SUCCESSFUL' && $transaction->request_amount > $wallet->balance) {
            $transaction->update([
                'status' => PaymentGatewayConst::STATUSFAILD,
                'details' => $details,
                'reject_reason' => __('Insufficient Balance In Your Wallet'),
                'available_balance' => $wallet->balance,
            ]);

            Log::info('Payout webhook failed due to insufficient balance.', [
                'transaction_id' => $transaction->id,
            ]);

            SendPayoutNotificationJob::dispatch($transaction->id, PaymentGatewayConst::STATUSFAILD, __('Insufficient Balance In Your Wallet'));
            return;
        }

        if ($status === 'SUCCESSFUL') {
            $reducedBalance = $wallet->balance - $transaction->request_amount;

            $transaction->update([
                'status' => PaymentGatewayConst::STATUSSUCCESS,
                'details' => $details,
                'available_balance' => $reducedBalance,
            ]);

            $wallet->update([
                'balance' => $reducedBalance,
            ]);

            Log::info('Payout webhook marked transaction as successful.', [
                'transaction_id' => $transaction->id,
            ]);

            SendPayoutNotificationJob::dispatch($transaction->id, PaymentGatewayConst::STATUSSUCCESS, __('Your payout request has been processed successfully.'));
            return;
        }

        if ($status === 'FAILED') {
            $transaction->update([
                'status' => PaymentGatewayConst::STATUSFAILD,
                'details' => $details,
                'reject_reason' => $completeMessage,
                'available_balance' => $wallet->balance,
            ]);

            Log::info('Payout webhook marked transaction as failed.', [
                'transaction_id' => $transaction->id,
            ]);

            SendPayoutNotificationJob::dispatch($transaction->id, PaymentGatewayConst::STATUSFAILD, $completeMessage ?? __('Your payout request could not be completed.'));
        }
    }
}
