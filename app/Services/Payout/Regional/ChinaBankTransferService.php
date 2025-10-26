<?php

namespace App\Services\Payout\Regional;

use App\Services\Payout\PayoutResponse;

class ChinaBankTransferService extends AbstractRegionalPayoutService
{
    public function lookupBank(array $payload): PayoutResponse
    {
        $code = $payload['bank_code'] ?? null;

        if (!$code) {
            return PayoutResponse::failure(trans('payout.missing_bank_code'));
        }

        $bank = $this->findBankByCode($code);

        if (!$bank) {
            return PayoutResponse::failure(trans('payout.bank_not_supported', ['bank' => $code]));
        }

        return PayoutResponse::success(trans('payout.bank_found'), ['bank' => $bank]);
    }

    public function createPayout(array $payload): PayoutResponse
    {
        $amount = (float) ($payload['amount'] ?? 0);
        $bankCode = $payload['bank_code'] ?? null;

        if (!$bankCode) {
            return PayoutResponse::failure(trans('payout.missing_bank_code'));
        }

        $bank = $this->findBankByCode($bankCode);

        if (!$bank) {
            return PayoutResponse::failure(trans('payout.bank_not_supported', ['bank' => $bankCode]));
        }

        if ($message = $this->validateAmount($amount)) {
            return PayoutResponse::failure($message);
        }

        $settlementAmount = $this->applyFee($amount);
        $reference = $this->generateReference('CNBT');

        return PayoutResponse::success(trans('payout.transfer_created'), [
            'reference' => $reference,
            'bank' => $bank,
            'original_amount' => $amount,
            'settlement_amount' => $settlementAmount,
        ]);
    }

    public function checkStatus(string $reference, array $context = []): PayoutResponse
    {
        return PayoutResponse::success(trans('payout.status_pending'), [
            'reference' => $reference,
            'status' => 'pending_review',
        ]);
    }
}
