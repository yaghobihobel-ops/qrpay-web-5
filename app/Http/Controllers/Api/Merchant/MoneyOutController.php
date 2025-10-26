<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Application\Services\PayoutApplicationService;
use App\Http\Controllers\Controller;
use App\Http\Helpers\Api\Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MoneyOutController extends Controller
{
    public function __construct(private readonly PayoutApplicationService $service)
    {
    }

    public function moneyOutInfo()
    {
        return $this->service->getMoneyOutInfo(auth()->user())->toResponse();
    }

    public function moneyOutInsert(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|gt:0',
            'gateway' => 'required',
        ]);

        if ($validator->fails()) {
            return Helpers::validation(['error' => $validator->errors()->all()]);
        }

        return $this->service->initiate($request, auth()->user())->toResponse();
    }

    public function moneyOutConfirmed(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trx' => 'required',
        ]);

        if ($validator->fails()) {
            return Helpers::validation(['error' => $validator->errors()->all()]);
        }

        return $this->service->confirmManual($request)->toResponse();
    }

    public function confirmMoneyOutAutomatic(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trx' => 'required',
        ]);

        if ($validator->fails()) {
            return Helpers::validation(['error' => $validator->errors()->all()]);
        }

        return $this->service->confirmAutomatic($request)->toResponse();
    }

    public function getBanks(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trx' => 'required',
        ]);

        if ($validator->fails()) {
            return Helpers::validation(['error' => $validator->errors()->all()]);
        }

        return $this->service->getFlutterWaveBanks($request)->toResponse();
    }

    public function getFlutterWaveBankBranches(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trx' => 'required',
            'bank_id' => 'required',
        ]);

        if ($validator->fails()) {
            return Helpers::validation(['error' => $validator->errors()->all()]);
        }

        return $this->service->getFlutterWaveBankBranches($request)->toResponse();
    }

    public function checkBankAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trx' => 'required',
            'bank_code' => 'required',
            'account_number' => 'required',
        ]);

        if ($validator->fails()) {
            return Helpers::validation(['error' => $validator->errors()->all()]);
        }

        return $this->service->checkFlutterWaveBankAccount($request)->toResponse();
    }
}
