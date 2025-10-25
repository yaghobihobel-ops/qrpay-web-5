<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Concerns\HandlesPayoutWithdrawals;
use App\Http\Controllers\Controller;
use App\Services\Compliance\ComplianceManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WithdrawalController extends Controller
{
    use HandlesPayoutWithdrawals;

    public function store(Request $request, ComplianceManager $compliance): JsonResponse
    {
        [$success, $message, $data] = $this->processWithdrawal($request, $compliance);
        $status = $success ? 200 : 422;

        if ($success) {
            $message = trans('payout.payout_success_message', ['reference' => $data['reference'] ?? '']);
        }

        return response()->json([
            'success' => $success,
            'message' => $message,
            'data' => $data,
        ], $status);
    }
}
