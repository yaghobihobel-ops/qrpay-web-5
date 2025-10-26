<?php

namespace App\Http\Controllers\PaymentGateway\QrPay\v1;

use Exception;
use Illuminate\Http\Request;
use App\Constants\GlobalConst;
use App\Enums\ApiErrorCode;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Constants\PaymentGatewayConst;
use Illuminate\Support\Facades\Validator;
use App\Models\Merchants\PaymentOrderRequest;
use App\Models\Merchants\DeveloperApiCredential;

class AuthenticationController extends Controller
{

    protected $access_token_expire_time = 600; // SECOND

    public function generateToken(Request $request) {
        $validator = Validator::make($request->all(),[
            'client_id'     => "required|string|exists:developer_api_credentials",
            'secret_id'     => "required|string|exists:developer_api_credentials,client_secret",
        ],[
            'client_id'    => "Invalid client ID",
            'secret_id'    => "Invalid secret ID",
        ]);

        if($validator->fails()) {
            return response()->error(
                __('The given data was invalid.'),
                ApiErrorCode::VALIDATION_ERROR,
                ['errors' => $validator->errors()->all()],
                422
            );
        }

        $validated = $validator->validate();

        if(request()->is("*/sandbox/*")) {
            // request url comes from sandbox
            $developer_credentials = DeveloperApiCredential::where(DB::raw('BINARY `client_id`'),$validated['client_id'])
                                                        ->where(DB::raw("BINARY `client_secret`"),$validated['secret_id'])
                                                        ->where('mode',PaymentGatewayConst::ENV_SANDBOX)
                                                        ->first();
        }else {
            // request url comes from production URL
            $developer_credentials = DeveloperApiCredential::where(DB::raw('BINARY `client_id`'),$validated['client_id'])
                                                        ->where(DB::raw("BINARY `client_secret`"),$validated['secret_id'])
                                                        ->where('mode',PaymentGatewayConst::ENV_PRODUCTION)
                                                        ->first();
        }

        if(!$developer_credentials) {
            return response()->error(
                __('Requested credentials is invalid'),
                ApiErrorCode::INVALID_CREDENTIALS
            );
        }

        $access_token = generate_unique_string('payment_order_requests','access_token',350);
        $token = generate_unique_string('payment_order_requests','token',60);
        $trx_id = generate_unique_string('payment_order_requests','trx_id',16);

        $merchant = $developer_credentials->merchant;
        if(!$merchant) {
            return response()->error(
                __('Merchant doesn\'t exists or credentials is invalid'),
                ApiErrorCode::MERCHANT_NOT_FOUND,
                null,
                404
            );
        }

        // // Comment for Qrpay
        // if($merchant->type != GlobalConst::EXPRESS) {
        //     return Response::paymentApiError(['Sorry, this credentials is invalid'],[],404);
        // }

        DB::beginTransaction();
        try{
            $this->deleteOldRecords();
            DB::table('payment_order_requests')->insert([
                'access_token'      => $access_token,
                'token'             => $token,
                'trx_id'            => $trx_id,
                'merchant_id'       => $merchant->id,
                'request_user_type' => GlobalConst::USER,
                'created_at'        => now(),
            ]);
            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            return response()->error(
                __('Failed to create access token. Please try again'),
                ApiErrorCode::PAYMENT_CREATION_FAILED,
                null,
                500
            );
        }
        return response()->success(
            __('Access token generated successfully.'),
            [
                'status' => 'SUCCESS',
                'access_token' => $access_token,
                'expire_time' => $this->access_token_expire_time,
            ]
        );
    }

    public function deleteOldRecords() {
        foreach(PaymentOrderRequest::get() as $item) {
            if(Carbon::now() >= $item->created_at->addSeconds($this->access_token_expire_time)) {
                $item->delete();
            }
        }
    }
}
