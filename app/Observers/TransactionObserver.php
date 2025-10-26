<?php

namespace App\Observers;

use App\Constants\PaymentGatewayConst;
use App\Jobs\Risk\ProcessRiskDecision;
use App\Models\Transaction;

class TransactionObserver
{
    public function created(Transaction $transaction): void
    {
        if (! $this->shouldEvaluate($transaction)) {
            return;
        }

        ProcessRiskDecision::dispatch($transaction->id);
    }

    protected function shouldEvaluate(Transaction $transaction): bool
    {
        return in_array($transaction->type, [
            PaymentGatewayConst::TYPEADDMONEY,
            PaymentGatewayConst::TYPEMONEYOUT,
            PaymentGatewayConst::TYPEWITHDRAW,
            PaymentGatewayConst::TYPEMONEYEXCHANGE,
            PaymentGatewayConst::SENDREMITTANCE,
            PaymentGatewayConst::TYPEMAKEPAYMENT,
            PaymentGatewayConst::MERCHANTPAYMENT,
        ], true);
    }
}
