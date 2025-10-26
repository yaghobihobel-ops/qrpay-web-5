<?php

namespace App\Services\Payout\Regional;

use App\Services\Payout\PayoutResponse;

class RussiaBankTransferService extends AbstractRegionalPayoutService
{
    public function lookupBank(array $payload): PayoutResponse
    {
        $swift = $payload['swift_code'] ?? null;

        if (!$swift) {
            return PayoutResponse::failure(trans('payout.missing_swift_code'));
        }

        $bank = $this->findBankByCode($swift);

        if (!$bank) {
            return PayoutResponse::failure(trans('payout.bank_not_supported', ['bank' => $swift]));
        }

        return PayoutResponse::success(trans('payout.bank_found'), ['bank' => $bank]);
    }

    public function createPayout(array $payload): PayoutResponse
    {
        $amount = (float) ($payload['amount'] ?? 0);
        $swift = $payload['swift_code'] ?? null;

        if (!$swift) {
            return PayoutResponse::failure(trans('payout.missing_swift_code'));
        }

        $bank = $this->findBankByCode($swift);

        if (!$bank) {
            return PayoutResponse::failure(trans('payout.bank_not_supported', ['bank' => $swift]));
        }

        if ($message = $this->validateAmount($amount)) {
            return PayoutResponse::failure($message);
        }

        $settlementAmount = $this->applyFee($amount);
        $reference = $this->generateReference('RUBT');

        return PayoutResponse::success(trans('payout.transfer_created'), [
            'reference' => $reference,
            'bank' => $bank,
            'original_amount' => $amount,
            'settlement_amount' => $settlementAmount,
        ]);
    }

    public function checkStatus(string $reference, array $context = []): PayoutResponse
    {
        return PayoutResponse::success(trans('payout.status_submitted'), [
            'reference' => $reference,
            'status' => 'submitted_to_bank',
        ]);
    }
}
