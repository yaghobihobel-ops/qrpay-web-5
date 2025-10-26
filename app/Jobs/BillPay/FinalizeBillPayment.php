<?php

namespace App\Jobs\BillPay;

use App\Constants\PaymentGatewayConst;
use App\Jobs\BillPay\SendBillPaymentNotification;
use App\Models\AgentProfit;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class FinalizeBillPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The queue that should process the job.
     */
    public string $queue = 'bill-payments';

    /**
     * Ensure dispatching waits until database transactions are committed.
     */
    public bool $afterCommit = true;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly int $transactionId,
        public readonly string $remoteStatus,
        public readonly array $apiPayload
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $transaction = Transaction::query()
            ->with(['creator_wallet', 'creator'])
            ->whereKey($this->transactionId)
            ->where('type', PaymentGatewayConst::BILLPAY)
            ->first();

        if (!$transaction || $transaction->status !== PaymentGatewayConst::STATUSPROCESSING) {
            return;
        }

        $normalizedStatus = strtoupper($this->remoteStatus);

        DB::transaction(function () use ($transaction, $normalizedStatus) {
            if ($normalizedStatus === 'SUCCESSFUL') {
                $this->markAsSuccessful($transaction);
            } else {
                $this->markAsFailed($transaction, $normalizedStatus);
            }
        }, 3);
    }

    /**
     * Apply success state updates.
     */
    protected function markAsSuccessful(Transaction $transaction): void
    {
        $details = $this->mergePayloadIntoDetails($transaction);

        $transaction->forceFill([
            'status' => PaymentGatewayConst::STATUSSUCCESS,
            'details' => $details,
        ])->save();

        $this->recordAgentProfit($transaction, $details);

        SendBillPaymentNotification::dispatch($transaction->id, true)
            ->onQueue('notifications');
    }

    /**
     * Apply failure state updates.
     */
    protected function markAsFailed(Transaction $transaction, string $normalizedStatus): void
    {
        $details = $this->mergePayloadIntoDetails($transaction);
        $wallet = $transaction->creator_wallet;
        $charges = Arr::get($details, 'charges', []);

        $transaction->forceFill([
            'status' => PaymentGatewayConst::STATUSREJECTED,
            'details' => $details,
        ]);

        if ($wallet) {
            $payable = (float) Arr::get($charges, 'payable', 0);
            $agentCommission = (float) Arr::get($charges, 'agent_total_commission', 0);
            $afterCharge = ($wallet->balance + $payable) - $agentCommission;

            $wallet->forceFill(['balance' => $afterCharge])->save();
            $transaction->available_balance = $afterCharge;
        }

        $transaction->save();

        SendBillPaymentNotification::dispatch($transaction->id, false, $normalizedStatus)
            ->onQueue('notifications');
    }

    /**
     * Merge the provider payload into the transaction details array.
     */
    protected function mergePayloadIntoDetails(Transaction $transaction): array
    {
        $details = json_decode(json_encode($transaction->details), true) ?: [];
        $apiResponse = $this->apiPayload;
        $transactionPayload = $apiResponse['transaction'] ?? [];

        data_set($details, 'api_response.status', $transactionPayload['status'] ?? null);
        data_set($details, 'api_response.code', $apiResponse['code'] ?? null);
        data_set($details, 'api_response.message', $apiResponse['message'] ?? null);
        data_set($details, 'api_transaction', $transactionPayload);

        return $details;
    }

    /**
     * Persist agent commission details when available.
     */
    protected function recordAgentProfit(Transaction $transaction, array $details): void
    {
        if (!$transaction->agent_id) {
            return;
        }

        $wallet = $transaction->creator_wallet;
        $charges = Arr::get($details, 'charges', []);
        $agentCommission = (float) Arr::get($charges, 'agent_total_commission', 0);

        if ($wallet) {
            $wallet->forceFill([
                'balance' => $wallet->balance + $agentCommission,
            ])->save();

            $transaction->forceFill([
                'available_balance' => $wallet->balance,
            ])->save();
        }

        AgentProfit::query()->firstOrCreate(
            [
                'transaction_id' => $transaction->id,
                'agent_id' => $transaction->agent_id,
            ],
            [
                'percent_charge' => (float) Arr::get($charges, 'agent_percent_commission', 0),
                'fixed_charge' => (float) Arr::get($charges, 'agent_fixed_commission', 0),
                'total_charge' => $agentCommission,
            ]
        );
    }
}
