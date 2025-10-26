<?php

namespace App\Http\Controllers\Concerns;

use App\Services\Compliance\ComplianceManager;
use App\Services\Payout\PayoutResponse;
use App\Services\Payout\PayoutServiceFactory;
use Illuminate\Http\Request;
use InvalidArgumentException;

trait HandlesPayoutWithdrawals
{
    protected function processWithdrawal(Request $request, ComplianceManager $compliance): array
    {
        $data = $this->validate($request, [
            'country' => 'required|string|size:2',
            'channel' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'bank_code' => 'sometimes|string',
            'iban' => 'sometimes|string',
            'swift_code' => 'sometimes|string',
            'wallet_address' => 'sometimes|string',
            'token' => 'sometimes|string',
            'network' => 'sometimes|string',
            'kyc_verified' => 'sometimes|boolean',
            'sanctions_screened' => 'sometimes|boolean',
            'supporting_documents' => 'sometimes|array',
            'supporting_documents.*' => 'string',
        ]);

        $data['country'] = strtoupper($data['country']);
        $data['channel'] = strtolower($data['channel']);
        $data['supporting_documents'] = $data['supporting_documents'] ?? [];

        try {
            $provider = PayoutServiceFactory::make($data['country'], $data['channel']);
        } catch (InvalidArgumentException $exception) {
            return [
                false,
                $exception->getMessage(),
                [],
            ];
        }

        $complianceResult = $compliance->approvePayout($request->user(), $data);

        if (!$complianceResult->isCleared()) {
            return [
                false,
                trans('payout.compliance_blocked', ['reason' => $complianceResult->message]),
                ['checks' => $complianceResult->checks],
            ];
        }

        /** @var PayoutResponse $response */
        $response = $provider->createPayout($data);

        return [
            $response->success,
            $response->message,
            $response->data,
        ];
    }
}
