<?php

namespace Tests\Feature\Jobs\BillPay;

use App\Constants\PaymentGatewayConst;
use App\Jobs\BillPay\FinalizeBillPayment;
use App\Jobs\BillPay\SendBillPaymentNotification;
use App\Jobs\BillPay\SyncBillPaymentStatus;
use App\Models\Admin\Admin;
use App\Models\Admin\Currency;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserWallet;
use App\Notifications\User\BillPay\BillPayFromReloadly;
use App\Notifications\User\BillPay\BillPayFromReloadlyRjected;
use App\Services\BillPay\BillPaymentStatusFetcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BillPaymentJobsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_job_dispatches_finalize_when_remote_success(): void
    {
        [$transaction] = $this->createTransaction();

        Queue::fake();

        $this->app->instance(BillPaymentStatusFetcher::class, new class {
            public function fetch($transaction): array
            {
                return [
                    'code' => 200,
                    'message' => 'OK',
                    'transaction' => ['status' => 'SUCCESSFUL'],
                ];
            }
        });

        $job = new SyncBillPaymentStatus($transaction->id);
        $job->handle(app(BillPaymentStatusFetcher::class));

        Queue::assertPushed(FinalizeBillPayment::class, function ($job) use ($transaction) {
            return $job->transactionId === $transaction->id
                && $job->remoteStatus === 'SUCCESSFUL'
                && $job->queue === 'bill-payments';
        });
    }

    public function test_sync_job_requeues_when_status_still_processing(): void
    {
        [$transaction] = $this->createTransaction();

        Queue::fake();

        $this->app->instance(BillPaymentStatusFetcher::class, new class {
            public function fetch($transaction): array
            {
                return [
                    'code' => 200,
                    'message' => 'OK',
                    'transaction' => ['status' => 'PROCESSING'],
                ];
            }
        });

        $job = new SyncBillPaymentStatus($transaction->id);
        $job->handle(app(BillPaymentStatusFetcher::class));

        Queue::assertNotPushed(FinalizeBillPayment::class);
        Queue::assertPushed(SyncBillPaymentStatus::class, function ($job) use ($transaction) {
            return $job->transactionId === $transaction->id
                && $job->queue === 'bill-payments'
                && $job->delay !== null;
        });
    }

    public function test_finalize_job_updates_transaction_on_success(): void
    {
        [$transaction] = $this->createTransaction();

        Queue::fake();

        $job = new FinalizeBillPayment($transaction->id, 'SUCCESSFUL', [
            'code' => 200,
            'message' => 'OK',
            'transaction' => [
                'status' => 'SUCCESSFUL',
                'reference' => 'remote-123',
            ],
        ]);

        $job->handle();

        $transaction->refresh();

        $this->assertSame(PaymentGatewayConst::STATUSSUCCESS, $transaction->status);
        $this->assertSame('SUCCESSFUL', data_get($transaction->details, 'api_response.status'));

        Queue::assertPushed(SendBillPaymentNotification::class, function ($job) use ($transaction) {
            return $job->transactionId === $transaction->id
                && $job->successful === true
                && $job->queue === 'notifications';
        });
    }

    public function test_finalize_job_marks_failure_and_updates_wallet(): void
    {
        [$transaction, $user, $wallet] = $this->createTransaction([
            'available_balance' => 80,
        ]);

        Queue::fake();

        $initialBalance = $wallet->balance;
        $payable = (float) data_get($transaction->details, 'charges.payable');

        $job = new FinalizeBillPayment($transaction->id, 'FAILED', [
            'code' => 422,
            'message' => 'Declined',
            'transaction' => [
                'status' => 'FAILED',
            ],
        ]);

        $job->handle();

        $transaction->refresh();
        $wallet->refresh();

        $this->assertSame(PaymentGatewayConst::STATUSREJECTED, $transaction->status);
        $this->assertEquals($initialBalance + $payable, $wallet->balance);
        $this->assertSame($wallet->balance, $transaction->available_balance);

        Queue::assertPushed(SendBillPaymentNotification::class, function ($job) use ($transaction) {
            return $job->transactionId === $transaction->id
                && $job->successful === false
                && $job->queue === 'notifications';
        });
    }

    public function test_notification_job_sends_success_notification(): void
    {
        [$transaction, $user] = $this->createTransaction();

        $transaction->forceFill(['status' => PaymentGatewayConst::STATUSSUCCESS])->save();

        Notification::fake();

        $job = new SendBillPaymentNotification($transaction->id, true);
        $job->handle();

        Notification::assertSentTo($user, BillPayFromReloadly::class, function ($notification) use ($transaction) {
            return $notification->data->trx_id === $transaction->trx_id;
        });
    }

    public function test_notification_job_sends_failure_notification(): void
    {
        [$transaction, $user] = $this->createTransaction();

        $transaction->forceFill(['status' => PaymentGatewayConst::STATUSREJECTED])->save();

        Notification::fake();

        $job = new SendBillPaymentNotification($transaction->id, false, 'FAILED');
        $job->handle();

        Notification::assertSentTo($user, BillPayFromReloadlyRjected::class, function ($notification) use ($transaction) {
            return $notification->data->trx_id === $transaction->trx_id;
        });
    }

    protected function createTransaction(array $overrides = []): array
    {
        $admin = Admin::factory()->create();
        $currency = Currency::factory()->create(['admin_id' => $admin->id]);
        $user = User::factory()->create();
        $walletBalance = $overrides['wallet_balance'] ?? 50;
        unset($overrides['wallet_balance']);

        $wallet = UserWallet::create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'balance' => $walletBalance,
            'status' => true,
        ]);

        $transaction = Transaction::create(array_merge([
            'user_id' => $user->id,
            'user_wallet_id' => $wallet->id,
            'type' => PaymentGatewayConst::BILLPAY,
            'trx_id' => 'BP' . str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT),
            'request_amount' => 50,
            'payable' => 50,
            'available_balance' => $wallet->balance,
            'remark' => 'Bill Pay',
            'details' => [
                'bill_type_name' => 'Utility',
                'bill_month' => '2023-01',
                'bill_number' => '12345',
                'charges' => [
                    'sender_amount' => 50,
                    'sender_currency' => 'USD',
                    'payable' => 50,
                    'agent_total_commission' => 0,
                ],
                'api_response' => [
                    'id' => 'remote-transaction-id',
                ],
            ],
            'attribute' => PaymentGatewayConst::SEND,
            'status' => PaymentGatewayConst::STATUSPROCESSING,
        ], $overrides));

        return [$transaction, $user, $wallet];
    }
}
