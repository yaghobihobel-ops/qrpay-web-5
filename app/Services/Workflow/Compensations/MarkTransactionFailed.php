<?php

namespace App\Services\Workflow\Compensations;

use App\Constants\PaymentGatewayConst;
use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Throwable;

class MarkTransactionFailed
{
    public function handle(Transaction $transaction, string $from, string $to, array $context, Throwable $exception): void
    {
        $transaction->forceFill([
            'status' => PaymentGatewayConst::STATUSFAILD,
        ])->save();

        $details = $this->detailsAsArray($transaction);
        $details['workflow']['failed_at'] = CarbonImmutable::now()->toIso8601String();
        $details['workflow']['failed_stage'] = $to;
        $transaction->update([
            'details' => $details,
        ]);
    }

    protected function detailsAsArray(Transaction $transaction): array
    {
        $details = $transaction->details;
        if (empty($details)) {
            return ['workflow' => []];
        }

        return json_decode(json_encode($details), true);
    }
}
