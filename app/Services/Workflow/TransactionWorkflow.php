<?php

namespace App\Services\Workflow;

use App\Constants\PaymentGatewayConst;
use App\Events\Transaction\TransactionStageChanged;
use App\Events\Transaction\TransactionStageFailed;
use App\Jobs\Transactions\SendTransactionCallback;
use App\Models\Transaction;
use App\Services\Workflow\Compensations\MarkTransactionFailed;
use App\Services\Workflow\Exceptions\InvalidStageTransitionException;
use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Throwable;

class TransactionWorkflow
{
    public const STAGE_NONE = 'none';
    public const STAGE_INITIATE = 'initiate';
    public const STAGE_VERIFY = 'verify';
    public const STAGE_SETTLE = 'settle';

    protected Transaction $transaction;

    protected array $transitions = [
        self::STAGE_NONE => [self::STAGE_INITIATE],
        self::STAGE_INITIATE => [self::STAGE_VERIFY],
        self::STAGE_VERIFY => [self::STAGE_SETTLE],
    ];

    protected array $stageConfiguration = [
        self::STAGE_INITIATE => [
            'callback_job' => SendTransactionCallback::class,
            'compensation' => MarkTransactionFailed::class,
            'context' => [
                'status' => PaymentGatewayConst::STATUSPENDING,
            ],
        ],
        self::STAGE_VERIFY => [
            'callback_job' => SendTransactionCallback::class,
            'compensation' => MarkTransactionFailed::class,
            'context' => [
                'status' => PaymentGatewayConst::STATUSPROCESSING,
            ],
        ],
        self::STAGE_SETTLE => [
            'callback_job' => SendTransactionCallback::class,
            'compensation' => MarkTransactionFailed::class,
            'context' => [
                'status' => PaymentGatewayConst::STATUSSUCCESS,
            ],
        ],
    ];

    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }

    public static function for(Transaction $transaction): self
    {
        return new self($transaction);
    }

    public function initiate(array $context = [], ?Closure $operation = null): self
    {
        return $this->moveTo(self::STAGE_INITIATE, $context, $operation);
    }

    public function verify(array $context = [], ?Closure $operation = null): self
    {
        return $this->moveTo(self::STAGE_VERIFY, $context, $operation);
    }

    public function settle(array $context = [], ?Closure $operation = null): self
    {
        return $this->moveTo(self::STAGE_SETTLE, $context, $operation);
    }

    public function currentStage(): string
    {
        $details = $this->detailsAsArray();
        return Arr::get($details, 'workflow.stage', self::STAGE_NONE);
    }

    protected function moveTo(string $stage, array $context = [], ?Closure $operation = null): self
    {
        $from = $this->currentStage();

        if ($from === $stage) {
            return $this;
        }

        if (! in_array($stage, $this->transitions[$from] ?? [], true)) {
            throw InvalidStageTransitionException::make($from, $stage);
        }

        $configuration = $this->stageConfiguration[$stage] ?? [];
        $context = array_merge($configuration['context'] ?? [], $context);

        try {
            DB::transaction(function () use ($stage, $context, $operation): void {
                if ($operation instanceof Closure) {
                    $operation($this->transaction, $context);
                }

                $this->persistStage($stage);
            });
        } catch (Throwable $throwable) {
            event(new TransactionStageFailed(
                $this->transaction,
                $from,
                $stage,
                $context,
                $configuration['compensation'] ?? MarkTransactionFailed::class,
                $throwable
            ));

            throw $throwable;
        }

        event(new TransactionStageChanged(
            $this->transaction,
            $from,
            $stage,
            $context,
            $configuration['callback_job'] ?? SendTransactionCallback::class
        ));

        return $this;
    }

    protected function persistStage(string $stage): void
    {
        $details = $this->detailsAsArray();
        $history = Arr::get($details, 'workflow.history', []);

        if (! in_array($stage, $history, true)) {
            $history[] = $stage;
        }

        Arr::set($details, 'workflow.stage', $stage);
        Arr::set($details, 'workflow.history', $history);
        Arr::set($details, 'workflow.updated_at', now()->toIso8601String());

        $this->transaction->update([
            'details' => $details,
        ]);
    }

    protected function detailsAsArray(): array
    {
        $details = $this->transaction->details;

        if (empty($details)) {
            return [];
        }

        return json_decode(json_encode($details), true);
    }
}
