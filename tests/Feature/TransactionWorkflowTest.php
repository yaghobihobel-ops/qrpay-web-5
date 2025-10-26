<?php

namespace Tests\Feature;

use App\Constants\PaymentGatewayConst;
use App\Jobs\Transactions\SendTransactionCallback;
use App\Models\Transaction;
use App\Services\Workflow\TransactionWorkflow;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class TransactionWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_full_payment_workflow_dispatches_callbacks(): void
    {
        Queue::fake();

        $transaction = $this->createTransaction([
            'callbacks' => [
                TransactionWorkflow::STAGE_INITIATE => ['url' => 'https://example.test/initiate'],
                TransactionWorkflow::STAGE_VERIFY => ['url' => 'https://example.test/verify'],
                TransactionWorkflow::STAGE_SETTLE => ['url' => 'https://example.test/settle'],
            ],
        ]);

        $workflow = TransactionWorkflow::for($transaction);

        $workflow->initiate();
        $workflow->verify();
        $workflow->settle();

        $transaction->refresh();

        $this->assertSame(
            TransactionWorkflow::STAGE_SETTLE,
            data_get(json_decode(json_encode($transaction->details), true), 'workflow.stage')
        );

        Queue::assertPushed(SendTransactionCallback::class, 3);

        Queue::assertPushed(SendTransactionCallback::class, function (SendTransactionCallback $job) use ($transaction) {
            return $job->transactionId === $transaction->id
                && $job->stage === TransactionWorkflow::STAGE_SETTLE
                && data_get($job->payload, 'status') === PaymentGatewayConst::STATUSSUCCESS;
        });
    }

    public function test_failed_stage_runs_compensation_and_marks_transaction_failed(): void
    {
        Queue::fake();

        $transaction = $this->createTransaction([
            'callbacks' => [
                TransactionWorkflow::STAGE_INITIATE => ['url' => 'https://example.test/initiate'],
                TransactionWorkflow::STAGE_VERIFY => ['url' => 'https://example.test/verify'],
            ],
        ]);

        $workflow = TransactionWorkflow::for($transaction);

        $workflow->initiate();

        try {
            $workflow->verify([], function () {
                throw new RuntimeException('Gateway timeout');
            });
            $this->fail('RuntimeException was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Gateway timeout', $exception->getMessage());
        }

        $transaction->refresh();

        $this->assertSame(
            TransactionWorkflow::STAGE_INITIATE,
            data_get(json_decode(json_encode($transaction->details), true), 'workflow.stage')
        );

        $this->assertSame(PaymentGatewayConst::STATUSFAILD, $transaction->status);

        Queue::assertPushed(SendTransactionCallback::class, 1);
        Queue::assertPushed(SendTransactionCallback::class, function (SendTransactionCallback $job) {
            return $job->stage === TransactionWorkflow::STAGE_INITIATE;
        });
    }

    protected function createTransaction(array $details = []): Transaction
    {
        return Transaction::create([
            'type' => PaymentGatewayConst::TYPEADDMONEY,
            'trx_id' => Str::upper(Str::random(12)),
            'request_amount' => 0,
            'payable' => 0,
            'available_balance' => 0,
            'remark' => 'workflow-test',
            'details' => $details,
            'status' => PaymentGatewayConst::STATUSPENDING,
            'attribute' => PaymentGatewayConst::SEND,
        ]);
    }
}
