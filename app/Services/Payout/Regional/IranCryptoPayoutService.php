<?php

namespace App\Services\Payout\Regional;

use App\Services\Payout\PayoutResponse;

class IranCryptoPayoutService extends AbstractRegionalPayoutService
{
    public function lookupBank(array $payload): PayoutResponse
    {
        $token = strtoupper($payload['token'] ?? 'USDT');
        $network = strtoupper($payload['network'] ?? '');

        $networks = $this->networks();

        if (!isset($networks[$token])) {
            return PayoutResponse::failure(trans('payout.token_not_supported', ['token' => $token]));
        }

        if ($network && !in_array($network, array_map('strtoupper', $networks[$token]['networks'] ?? []), true)) {
            return PayoutResponse::failure(trans('payout.network_not_supported', ['network' => $network]));
        }

        return PayoutResponse::success(trans('payout.network_found'), [
            'token' => $token,
            'networks' => $networks[$token]['networks'] ?? [],
            'fee' => $networks[$token]['fee'] ?? $this->config['fee'] ?? 0,
        ]);
    }

    public function createPayout(array $payload): PayoutResponse
    {
        $amount = (float) ($payload['amount'] ?? 0);
        $wallet = $payload['wallet_address'] ?? null;
        $token = strtoupper($payload['token'] ?? 'USDT');
        $network = strtoupper($payload['network'] ?? '');

        if (!$wallet) {
            return PayoutResponse::failure(trans('payout.missing_wallet_address'));
        }

        $lookup = $this->lookupBank(['token' => $token, 'network' => $network]);

        if (!$lookup->success) {
            return $lookup;
        }

        if ($message = $this->validateAmount($amount)) {
            return PayoutResponse::failure($message);
        }

        $settlementAmount = $this->applyFee($amount);
        $reference = $this->generateReference('IRCR');

        return PayoutResponse::success(trans('payout.transfer_created'), [
            'reference' => $reference,
            'wallet_address' => $wallet,
            'token' => $token,
            'network' => $network ?: ($lookup->data['networks'][0] ?? ''),
            'original_amount' => $amount,
            'settlement_amount' => $settlementAmount,
        ]);
    }

    public function checkStatus(string $reference, array $context = []): PayoutResponse
    {
        return PayoutResponse::success(trans('payout.status_onchain_confirmation'), [
            'reference' => $reference,
            'status' => 'awaiting_confirmations',
        ]);
    }
}
