<?php

namespace App\Jobs\BillPay;

use App\Constants\PaymentGatewayConst;
use App\Jobs\BillPay\FinalizeBillPayment;
use App\Models\Transaction;
use App\Services\BillPay\BillPaymentStatusFetcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncBillPaymentStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The queue that should process the job.
     */
    public string $queue = 'bill-payments';

    /**
     * Create a new job instance.
     */
    public function __construct(public readonly int $transactionId)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(BillPaymentStatusFetcher $statusFetcher): void
    {
        $transaction = Transaction::query()
            ->whereKey($this->transactionId)
            ->where('type', PaymentGatewayConst::BILLPAY)
            ->first();

        if (!$transaction || $transaction->status !== PaymentGatewayConst::STATUSPROCESSING) {
            return;
        }

        $apiResponse = $statusFetcher->fetch($transaction);

        if (!is_array($apiResponse) || !isset($apiResponse['transaction'])) {
            $this->requeue();
            return;
        }

        $remoteTransaction = $apiResponse['transaction'];
        $remoteStatus = strtoupper((string) ($remoteTransaction['status'] ?? ''));

        if ($remoteStatus === '' || $remoteStatus === 'PROCESSING') {
            $this->requeue();
            return;
        }

        if (!in_array($remoteStatus, ['SUCCESSFUL', 'FAILED', 'REFUNDED', 'PENDING'], true)) {
            Log::warning('Unexpected bill payment status received from provider', [
                'transaction_id' => $this->transactionId,
                'status' => $remoteStatus,
            ]);
            $this->requeue();
            return;
        }

        FinalizeBillPayment::dispatch(
            $this->transactionId,
            $remoteStatus,
            $apiResponse
        )->onQueue('bill-payments');
    }

    /**
     * Schedule another attempt to sync the transaction.
     */
    protected function requeue(): void
    {
        static::dispatch($this->transactionId)
            ->onQueue($this->queue)
            ->delay(now()->addMinutes(1));
    }
}
