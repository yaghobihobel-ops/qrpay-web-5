<?php

namespace App\Services\Payout\Regional;

use App\Services\Payout\PayoutResponse;

class TurkeyBankTransferService extends AbstractRegionalPayoutService
{
    public function lookupBank(array $payload): PayoutResponse
    {
        $iban = $payload['iban'] ?? null;

        if (!$iban) {
            return PayoutResponse::failure(trans('payout.missing_iban'));
        }

        foreach ($this->banks() as $bank) {
            if (isset($bank['iban_prefix']) && str_starts_with(strtoupper($iban), strtoupper($bank['iban_prefix']))) {
                return PayoutResponse::success(trans('payout.bank_found'), [
                    'bank' => $bank,
                    'iban' => $iban,
                ]);
            }
        }

        return PayoutResponse::failure(trans('payout.bank_not_supported', ['bank' => $iban]));
    }

    public function createPayout(array $payload): PayoutResponse
    {
        $amount = (float) ($payload['amount'] ?? 0);
        $iban = $payload['iban'] ?? null;

        if (!$iban) {
            return PayoutResponse::failure(trans('payout.missing_iban'));
        }

        $lookup = $this->lookupBank(['iban' => $iban]);

        if (!$lookup->success) {
            return $lookup;
        }

        if ($message = $this->validateAmount($amount)) {
            return PayoutResponse::failure($message);
        }

        $settlementAmount = $this->applyFee($amount);
        $reference = $this->generateReference('TRBT');

        return PayoutResponse::success(trans('payout.transfer_created'), [
            'reference' => $reference,
            'bank' => $lookup->data['bank'] ?? [],
            'original_amount' => $amount,
            'settlement_amount' => $settlementAmount,
            'iban' => $iban,
        ]);
    }

    public function checkStatus(string $reference, array $context = []): PayoutResponse
    {
        return PayoutResponse::success(trans('payout.status_in_review'), [
            'reference' => $reference,
            'status' => 'in_review',
        ]);
    }
}
