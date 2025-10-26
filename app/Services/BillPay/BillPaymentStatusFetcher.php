<?php

namespace App\Services\BillPay;

use App\Http\Helpers\UtilityHelper;
use App\Models\Transaction;

class BillPaymentStatusFetcher
{
    /**
     * Retrieve the latest transaction data from the payment provider.
     */
    public function fetch(Transaction $transaction): ?array
    {
        $details = $transaction->details;
        $apiResponse = $details?->api_response ?? null;
        $remoteId = is_array($apiResponse)
            ? ($apiResponse['id'] ?? null)
            : ($apiResponse->id ?? null);

        if (!$remoteId) {
            return null;
        }

        return (new UtilityHelper())->getTransaction($remoteId);
    }
}
