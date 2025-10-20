<?php

namespace App\Http\Controllers\Api\User;

use App\Constants\NotificationConst;
use App\Constants\PaymentGatewayConst;
use App\Http\Controllers\Controller;
use App\Http\Helpers\Api\Helpers;
use App\Http\Helpers\NotificationHelper;
use App\Models\Admin\BasicSettings;
use App\Models\Admin\Currency;
use App\Models\Admin\PaymentGateway;
use App\Models\Admin\PaymentGatewayCurrency;
use App\Models\TemporaryData;
use App\Models\Transaction;
use App\Models\UserNotification;
use App\Models\UserWallet;
use App\Notifications\Admin\ActivityNotification;
use App\Notifications\User\Withdraw\WithdrawMail;
use Exception;
use Jenssegers\Agent\Agent;
use Illuminate\Http\Request;
use App\Traits\ControlDynamicInputFields;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Helpers\PushNotificationHelper;
use App\Providers\Admin\BasicSettingsProvider;

class MoneyOutController extends Controller
{
    use ControlDynamicInputFields;

    protected $basic_settings;
    public function __construct()
    {
        $this->basic_settings = BasicSettingsProvider::get();
    }

    public function moneyOutInfo(){
        $user = auth()->user();
        $userWallet = UserWallet::where('user_id',$user->id)->get()->map(function($data){
                return[
                    'balance' => getAmount($data->balance,2),
                    'currency' => get_default_currency_code(),
                ];
            })->first();

            $transactions = Transaction::agentAuth()->moneyOut()->latest()->take(5)->get()->map(function($item){
                    $statusInfo = [
                        "success" =>      1,
                        "pending" =>      2,
                        "rejected" =>     3,
                        ];
                    return[
                        'id' => $item->id,
                        'trx' => $item->trx_id,
                        'gateway_name' => $item->currency->gateway->name,
                        'gateway_currency_name' => $item->currency->name,
                        'transaction_type' => $item->type,
                        'request_amount' => isCrypto($item->request_amount,get_default_currency_code(),$item->currency->gateway->crypto) ,
                        'payable' => isCrypto($item->payable,$item->creator_wallet->currency->code,$item->currency->gateway->crypto),
                        'exchange_rate' => '1 ' .get_default_currency_code().' = '.isCrypto($item->currency->rate,$item->currency->currency_code,$item->currency->gateway->crypto),
                        'total_charge' => isCrypto($item->charge->total_charge,$item->currency->currency_code,$item->currency->gateway->crypto),
                        'current_balance' => isCrypto($item->available_balance,get_default_currency_code(),$item->currency->gateway->crypto),
                        'status' => $item->stringStatus->value,
                        'date_time' => $item->created_at,
                        'status_info' =>(object)$statusInfo,
                        'rejection_reason' =>$item->reject_reason??"",

                    ];
            });
            $gateways = PaymentGateway::where('status', 1)->where('slug', PaymentGatewayConst::money_out_slug())->get()->map(function($gateway){
                    $currencies = PaymentGatewayCurrency::where('payment_gateway_id',$gateway->id)->get()->map(function($data){
                    $precision = get_precision($data->gateway);
                    return[
                        'id'                    => $data->id,
                        'payment_gateway_id'    => $data->payment_gateway_id,
                        'crypto'                => $data->gateway->crypto,
                        'type'                  => $data->gateway->type,
                        'name'                  => $data->name,
                        'alias'                 => $data->alias,
                        'currency_code'         => $data->currency_code,
                        'currency_symbol'       => $data->currency_symbol,
                        'image'                 => $data->image,
                        'min_limit'             => get_amount($data->min_limit,null,$precision),
                        'max_limit'             => get_amount($data->max_limit,null,$precision),
                        'percent_charge'        => get_amount($data->percent_charge,null,$precision),
                        'fixed_charge'          => get_amount($data->fixed_charge,null,$precision),
                        'rate'                  => get_amount($data->rate,null,$precision),
                        'created_at'            => $data->created_at,
                        'updated_at'            => $data->updated_at,
                    ];

                    });
                    return[
                        'id' => $gateway->id,
                        'name' => $gateway->name,
                        'image' => $gateway->image,
                        'slug' => $gateway->slug,
                        'code' => $gateway->code,
                        'type' => $gateway->type,
                        'alias' => $gateway->alias,
                        'crypto' => $gateway->crypto,
                        'supported_currencies' => $gateway->supported_currencies,
                        'input_fields' => $gateway->input_fields??null,
                        'status' => $gateway->status,
                        'currencies' => $currencies

                    ];
            });
            // $flutterwave_supported_bank = getFlutterwaveBanks();
            $data =[
                'base_curr'    => get_default_currency_code(),
                'base_curr_rate'    => getAmount(1,2),
                'default_image'    => "public/backend/images/default/default.webp",
                "image_path"  =>  "public/backend/images/payment-gateways",
                'userWallet'   =>   (object)$userWallet,
                'gateways'   => $gateways,
                // 'flutterwave_supported_bank'   => $flutterwave_supported_bank,
                'transactionss'   =>   $transactions,
            ];
            $message =  ['success'=>[__('Withdraw Information!')]];
            return Helpers::success($data,$message);

    }

    public function moneyOutInsert(Request $request){
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|gt:0',
            'gateway' => 'required'
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }
        $user = auth()->user();

        $userWallet = UserWallet::where('user_id',$user->id)->where('status',1)->first();
        $gate = PaymentGatewayCurrency::whereHas('gateway', function ($gateway) {
            $gateway->where('slug', PaymentGatewayConst::money_out_slug());
            $gateway->where('status', 1);
        })->where('alias',$request->gateway)->first();
        if (!$gate) {
            $error = ['error'=>[__("Gateway is not available right now! Please contact with system administration")]];
            return Helpers::error($error);
        }
        $precision = get_precision($gate->gateway);
        $baseCurrency = Currency::default();
        if (!$baseCurrency) {
            $error = ['error'=>[__('Default currency not found')]];
            return Helpers::error($error);
        }
        $amount = $request->amount;

        $min_limit =  get_amount($gate->min_limit / $gate->rate,null,$precision);
        $max_limit =  get_amount($gate->max_limit / $gate->rate,null,$precision);
        if($amount < $min_limit || $amount > $max_limit) {
            $error = ['error'=>[__("Please follow the transaction limit")]];
            return Helpers::error($error);
        }
        //gateway charge
        $fixedCharge = get_amount($gate->fixed_charge,null,$precision);
        $percent_charge =  get_amount((((($request->amount * $gate->rate)/ 100) * $gate->percent_charge)),null,$precision);
        $charge = get_amount($fixedCharge + $percent_charge,null,$precision);
        $conversion_amount = get_amount($request->amount * $gate->rate,null,$precision);
        $will_get = get_amount($conversion_amount -  $charge,null,$precision);
        //base_cur_charge
        $baseFixedCharge = get_amount($gate->fixed_charge *  $baseCurrency->rate,null,$precision);
        $basePercent_charge = get_amount(($request->amount / 100) * $gate->percent_charge,null,$precision);
        $base_total_charge = get_amount($baseFixedCharge + $basePercent_charge,null,$precision);
        $reduceAbleTotal = get_amount($amount,null,$precision);
        if( $reduceAbleTotal > $userWallet->balance){
            $error = ['error'=>[__('Sorry, insufficient balance')]];
            return Helpers::error($error);
        }

        $insertData = [
            'user_id'=> $user->id,
            'gateway_name'=> strtolower($gate->gateway->name),
            'gateway_type'=> $gate->gateway->type,
            'wallet_id'=> $userWallet->id,
            'trx_id'=> 'MO'.getTrxNum(),
            'amount' =>  get_amount($amount,null,$precision),
            'base_cur_charge' => get_amount($base_total_charge,null,$precision),
            'base_cur_rate' => get_amount($baseCurrency->rate,null,$precision),
            'gateway_id' => $gate->gateway->id,
            'gateway_currency_id' => $gate->id,
            'gateway_currency' => strtoupper($gate->currency_code),
            'gateway_percent_charge' => get_amount($percent_charge,null,$precision),
            'gateway_fixed_charge' => get_amount($fixedCharge,null,$precision),
            'gateway_charge' => get_amount($charge,null,$precision),
            'gateway_rate' => get_amount($gate->rate,null,$precision),
            'conversion_amount' => get_amount($conversion_amount,null,$precision),
            'will_get' => get_amount($will_get,null,$precision),
            'payable' => get_amount($reduceAbleTotal,null,$precision),
        ];

        $identifier = generate_unique_string("transactions","trx_id",16);
        $inserted = TemporaryData::create([
            'type'          => PaymentGatewayConst::TYPEMONEYOUT,
            'identifier'    => $identifier,
            'data'          => $insertData,
        ]);
        if( $inserted){
            $payment_gateway = PaymentGateway::where('id',$gate->payment_gateway_id)->first();
            $payment_informations =[
                'trx'                   => $identifier,
                'gateway_currency_name' => $gate->name,
                'request_amount'        => get_amount($request->amount,get_default_currency_code(),$precision),
                'exchange_rate'         => "1".' '.get_default_currency_code().' = '.get_amount($gate->rate,$gate->currency_code,$precision),
                'conversion_amount'     => get_amount($conversion_amount,$gate->currency_code,$precision),
                'total_charge'          => get_amount($charge,$gate->currency_code,$precision),
                'will_get'              => get_amount($will_get,$gate->currency_code,$precision),
                'payable'               => get_amount($reduceAbleTotal,get_default_currency_code(),$precision),

            ];
            if($gate->gateway->type == "AUTOMATIC"){
                $url = route('api.withdraw.automatic.confirmed');
                $data =[
                    'payment_informations' => $payment_informations,
                    'gateway_type' => $payment_gateway->type,
                    'gateway_currency_name' => $gate->name,
                    'branch_available' => branch_required_permission(getewayIso2($insertData['gateway_currency'])),
                    'alias' => $gate->alias,
                    'url' => $url??'',
                    'method' => "post",
                    ];
                    $message =  ['success'=>[__("Withdraw Money Inserted Successfully")]];
                    return Helpers::success($data, $message);
            }else{
                $url = route('api.withdraw.manual.confirmed');
                $data =[
                    'payment_informations' => $payment_informations,
                    'gateway_type' => $payment_gateway->type,
                    'gateway_currency_name' => $gate->name,
                    'alias' => $gate->alias,
                    'details' => $payment_gateway->desc??null,
                    'input_fields' => $payment_gateway->input_fields??null,
                    'url' => $url??'',
                    'method' => "post",
                    ];
                    $message =  ['success'=>[__("Withdraw Money Inserted Successfully")]];
                    return Helpers::success($data, $message);
            }
        }else{
            $error = ['error'=>[__("Something went wrong! Please try again.")]];
            return Helpers::error($error);
        }
    }
    //manual confirmed
    public function moneyOutConfirmed(Request $request){
        $validator = Validator::make($request->all(), [
            'trx'  => "required",
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }
        $basic_setting = BasicSettings::first();
        $track = TemporaryData::where('identifier',$request->trx)->where('type',PaymentGatewayConst::TYPEMONEYOUT)->first();
        if(!$track){
            $error = ['error'=>[__("Sorry, your payment information is invalid")]];
            return Helpers::error($error);

        }
        $moneyOutData =  $track->data;
        $gateway = PaymentGateway::where('id', $moneyOutData->gateway_id)->first();
        if($gateway->type != "MANUAL"){
            $error = ['error'=>[__("Invalid request, it is not manual gateway request")]];
            return Helpers::error($error);
        }
        $payment_fields = $gateway->input_fields ?? [];
        $validation_rules = $this->generateValidationRules($payment_fields);
        $validator2 = Validator::make($request->all(), $validation_rules);
        if ($validator2->fails()) {
            $message =  ['error' => $validator2->errors()->all()];
            return Helpers::error($message);
        }
        $validated = $validator2->validate();
        $get_values = $this->placeValueWithFields($payment_fields, $validated);
            try{
                //send notifications
                $user = auth()->user();
                $inserted_id = $this->insertRecordManual($moneyOutData,$gateway,$get_values,$reference= null,PaymentGatewayConst::STATUSPENDING);
                $this->insertChargesManual($moneyOutData,$inserted_id);
                $this->adminNotification($moneyOutData,PaymentGatewayConst::STATUSPENDING);
                $this->insertDeviceManual($moneyOutData,$inserted_id);
                $track->delete();
                try{
                    if( $basic_setting->email_notification == true){
                        $user->notify(new WithdrawMail($user,$moneyOutData));
                    }
                }catch(Exception $e){}
                $message =  ['success'=>[__('Withdraw money request send to admin successful')]];
                return Helpers::onlysuccess($message);
            }catch(Exception $e) {
                $error = ['error'=>[__("Something went wrong! Please try again.")]];
                return Helpers::error($error);
            }

    }
    //automatic confirmed
    public function confirmMoneyOutAutomatic(Request $request){
        $validator = Validator::make($request->all(), [
            'trx'  => "required",
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }
        $track = TemporaryData::where('identifier',$request->trx)->where('type',PaymentGatewayConst::TYPEMONEYOUT)->first();
        if(!$track){
            $error = ['error'=>[__("Sorry, your payment information is invalid")]];
            return Helpers::error($error);
        }
        $gateway = PaymentGateway::where('id', $track->data->gateway_id)->first();
        if($gateway->type != "AUTOMATIC"){
            $error = ['error'=>[__("Invalid request, it is not automatic gateway request")]];
            return Helpers::error($error);
        }

        $gateway_iso2 = getewayIso2($track->data->gateway_currency??get_default_currency_code());

        //flutterwave automatic
         if($track->data->gateway_name == "flutterwave"){
            $branch_status = branch_required_permission($gateway_iso2);
            $validator = Validator::make($request->all(), [
                'bank_name' => 'required',
                'account_number' => 'required',
                'beneficiary_name'  => 'required|string',
                'branch_code'       => $branch_status == true ? 'required':'nullable',
            ]);
            if($validator->fails()){
                $error =  ['error'=>$validator->errors()->all()];
                return Helpers::validation($error);
            }

            return $this->flutterwavePay($gateway,$request,$track,$branch_status);


         }else{
            $error = ['error'=>[__("Something went wrong! Please try again.")]];
            return Helpers::error($error);
         }

    }

    public function insertRecordManual($moneyOutData,$gateway,$get_values,$reference,$status) {
        $trx_id = $moneyOutData->trx_id ??'MO'.getTrxNum();
        $authWallet = UserWallet::where('id',$moneyOutData->wallet_id)->where('user_id',$moneyOutData->user_id)->first();
        if($moneyOutData->gateway_type != "AUTOMATIC"){
            $afterCharge = ($authWallet->balance - ($moneyOutData->amount));
        }else{
            $afterCharge = $authWallet->balance;
        }
        DB::beginTransaction();
        try{
            $id = DB::table("transactions")->insertGetId([
                'user_id'                       => auth()->user()->id,
                'user_wallet_id'                => $moneyOutData->wallet_id,
                'payment_gateway_currency_id'   => $moneyOutData->gateway_currency_id,
                'type'                          => PaymentGatewayConst::TYPEMONEYOUT,
                'trx_id'                        => $trx_id,
                'request_amount'                => $moneyOutData->amount,
                'payable'                       => $moneyOutData->will_get,
                'available_balance'             => $afterCharge,
                'remark'                        => ucwords(remove_speacial_char(PaymentGatewayConst::TYPEMONEYOUT," ")) . " by " .$gateway->name,
                'details'                       => json_encode($get_values),
                'status'                        => $status,
                'callback_ref'                  => $reference??null,
                'created_at'                    => now(),
            ]);
            if($moneyOutData->gateway_type != "AUTOMATIC"){
                $this->updateWalletBalanceManual($authWallet,$afterCharge);
            }

            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            $error = ['error'=>[__("Something went wrong! Please try again.")]];
            return Helpers::error($error);
        }
        return $id;
    }

    public function updateWalletBalanceManual($authWalle,$afterCharge) {
        $authWalle->update([
            'balance'   => $afterCharge,
        ]);
    }
    public function insertChargesManual($moneyOutData,$id) {
        if(Auth::guard(get_auth_guard())->check()){
            $user = auth()->guard(get_auth_guard())->user();
        }
        DB::beginTransaction();
        try{
            DB::table('transaction_charges')->insert([
                'transaction_id'    => $id,
                'percent_charge'    => $moneyOutData->gateway_percent_charge,
                'fixed_charge'      => $moneyOutData->gateway_fixed_charge,
                'total_charge'      => $moneyOutData->gateway_charge,
                'created_at'        => now(),
            ]);
            DB::commit();

            //notification
            $notification_content = [
                'title'         => "Withdraw",
                'message'       => "Your Withdraw Request Send To Admin " .$moneyOutData->amount.' '.get_default_currency_code()." Successful",
                'image'         =>  get_image($user->image,'user-profile'),
            ];

            UserNotification::create([
                'type'      => NotificationConst::MONEY_OUT,
                'user_id'  =>  auth()->user()->id,
                'message'   => $notification_content,
            ]);
            //Push Notifications
            if( $this->basic_settings->push_notification == true){
                try{
                    (new PushNotificationHelper())->prepareApi([$user->id],[
                        'title' => $notification_content['title'],
                        'desc'  => $notification_content['message'],
                        'user_type' => 'user',
                    ])->send();
                }catch(Exception $e) {}
            }
        }catch(Exception $e) {
            DB::rollBack();
            $error = ['error'=>[__("Something went wrong! Please try again.")]];
            return Helpers::error($error);
        }
    }
    public function insertChargesAutomatic($moneyOutData,$id) {
        if(Auth::guard(get_auth_guard())->check()){
            $user = auth()->guard(get_auth_guard())->user();
        }
        DB::beginTransaction();
        try{
            DB::table('transaction_charges')->insert([
                'transaction_id'    => $id,
                'percent_charge'    => $moneyOutData->gateway_percent_charge,
                'fixed_charge'      => $moneyOutData->gateway_fixed_charge,
                'total_charge'      => $moneyOutData->gateway_charge,
                'created_at'        => now(),
            ]);
            DB::commit();

            //notification
            $notification_content = [
                'title'         => __("Withdraw Money"),
                'message'       => __("Your Withdraw Request")." " .$moneyOutData->amount.' '.get_default_currency_code()." ".__("Successful"),
                'image'         =>  get_image($user->image,'user-profile'),
            ];

            UserNotification::create([
                'type'      => NotificationConst::MONEY_OUT,
                'user_id'  =>  auth()->user()->id,
                'message'   => $notification_content,
            ]);
            //Push Notifications
            if( $this->basic_settings->push_notification == true){
                try{
                    (new PushNotificationHelper())->prepareApi([$user->id],[
                        'title' => $notification_content['title'],
                        'desc'  => $notification_content['message'],
                        'user_type' => 'user',
                    ])->send();
                }catch(Exception $e) {}
            }

        }catch(Exception $e) {
            DB::rollBack();
            $error = ['error'=>[__("Something went wrong! Please try again.")]];
            return Helpers::error($error);
        }
    }

    public function insertDeviceManual($output,$id) {
        $client_ip = request()->ip() ?? false;
        $location = geoip()->getLocation($client_ip);
        $agent = new Agent();

        // $mac = exec('getmac');
        // $mac = explode(" ",$mac);
        // $mac = array_shift($mac);
        $mac = "";

        DB::beginTransaction();
        try{
            DB::table("transaction_devices")->insert([
                'transaction_id'=> $id,
                'ip'            => $client_ip,
                'mac'           => $mac,
                'city'          => $location['city'] ?? "",
                'country'       => $location['country'] ?? "",
                'longitude'     => $location['lon'] ?? "",
                'latitude'      => $location['lat'] ?? "",
                'timezone'      => $location['timezone'] ?? "",
                'browser'       => $agent->browser() ?? "",
                'os'            => $agent->platform() ?? "",
            ]);
            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            $error = ['error'=>[__("Something went wrong! Please try again.")]];
            return Helpers::error($error);
        }
    }

    //fluttrwave
    public function flutterwavePay($gateway,$request, $track, $branch_status){
        $moneyOutData =  $track->data;
        $basic_setting = BasicSettings::first();
        $credentials = $gateway->credentials;
        $data = null;
        $secret_key = getPaymentCredentials($credentials,'Secret key');
        $base_url = getPaymentCredentials($credentials,'Base Url');
        $callback_url = url('/').'/flutterwave/withdraw_webhooks';

        $ch = curl_init();
        $url =  $base_url.'/transfers';
        $reference =  generateTransactionReference();
        $data = [
            "account_bank" => $request->bank_name,
            "account_number" => $request->account_number,
            "amount" => $moneyOutData->will_get,
            "narration" => "Withdraw from wallet",
            "currency" => $moneyOutData->gateway_currency,
            "reference" => $reference,
            "callback_url" => $callback_url,
            "debit_currency" => $moneyOutData->gateway_currency,
            "beneficiary_name"  => $request->beneficiary_name??""
        ];
        if ($branch_status === true) {
            $data['destination_branch_code'] = $request->branch_code;
        }

        $headers = [
            'Authorization: Bearer '.$secret_key,
            'Content-Type: application/json'
        ];
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $result = json_decode($response,true);

            if($result['status'] && $result['status'] == 'success'){
                $get_values =[
                    'user_data' => $result['data'],
                    'charges' => [],
                ];
                try{
                    $user = auth()->user();
                    $inserted_id = $this->insertRecordManual($moneyOutData,$gateway,$get_values,$reference,PaymentGatewayConst::STATUSWAITING);
                    $this->insertChargesAutomatic($moneyOutData,$inserted_id);
                    $this->adminNotification($moneyOutData,PaymentGatewayConst::STATUSSUCCESS);
                    $this->insertDeviceManual($moneyOutData,$inserted_id);
                    $track->delete();
                    //send notifications
                    try{
                        if( $basic_setting->email_notification == true){
                            $user->notify(new WithdrawMail($user,$moneyOutData));
                        }
                    }catch(Exception $e){

                    }
                    $message =  ['success'=>[__('Withdraw money request send successful')]];
                    return Helpers::onlysuccess($message);
                }catch(Exception $e) {
                    $error = ['error'=>[__("Something went wrong! Please try again.")]];
                    return Helpers::error($error);
                }

            }else if($result['status'] && $result['status'] == 'error'){
                if(isset($result['data'])){
                    $errors = $result['message'].",".$result['data']['complete_message']??"";
                }else{
                    $errors = $result['message'];
                }
                $error = ['error'=>[$errors]];
                return Helpers::error($error);
            }else{
                $error = ['error'=>[$result['message']??""]];
                return Helpers::error($error);
            }


        curl_close($ch);

    }
    //get flutterwave banks
    public function getBanks(){
        $validator = Validator::make(request()->all(), [
            'trx'  => "required",
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }
        $track = TemporaryData::where('identifier',request()->trx)->where('type',PaymentGatewayConst::TYPEMONEYOUT)->first();
        if(!$track){
            $error = ['error'=>[__("Sorry, your payment information is invalid")]];
            return Helpers::error($error);
        }
        if($track['data']->gateway_name != "flutterwave"){
            $error = ['error'=>[__("Sorry, This Payment Request Is Not For FlutterWave")]];
            return Helpers::error($error);
        }
        $countries = get_all_countries();
        $currency = $track['data']->gateway_currency;
        $country = Collection::make($countries)->first(function ($item) use ($currency) {
            return $item->currency_code === $currency;
        });

        $allBanks = getFlutterwaveBanks($country->iso2);
        $data =[
            'bank_info' => array_values($allBanks)??[]
        ];
        $message =  ['success'=>[__("All Bank Fetch Successfully")]];
        return Helpers::success($data, $message);

    }
    public function checkBankAccount(){
        $validator = Validator::make(request()->all(), [
            'trx'  => "required",
            'bank_code'  => "required",
            'account_number'  => "required",
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }
        $bank_account   = request()->account_number;
        $bank_code      = request()->bank_code;
        $track = TemporaryData::where('identifier',request()->trx)->where('type',PaymentGatewayConst::TYPEMONEYOUT)->first();
        if(!$track){
            $error = ['error'=>[__("Sorry, your payment information is invalid")]];
            return Helpers::error($error);
        }
        if($track['data']->gateway_name != "flutterwave"){
            $error = ['error'=>[__("Sorry, This Payment Request Is Not For FlutterWave")]];
            return Helpers::error($error);
        }
        $account = checkBankAccount($bank_account,$bank_code);
        if(isset($account) && $account['status'] == 'success'){
            $info = [
                'status'        => true,
                'message'       => $account['message']??"",
                'account_info'  => $account['data']??[]
            ];
        }else{
            $info = [
                'status'        => false,
                'message'       => $account['message']??"",
                'account_info'  => $account['data']??[]
            ];
        }

        $data =[
            'account' => $info??[]
        ];
        $message =  ['success'=>[__("Account details fetched successfully")]];
        return Helpers::success($data, $message);
    }
    //get flutterwave bank branches
    public function getFlutterWaveBankBranches(){
        $validator = Validator::make(request()->all(), [
            'trx'       => "required",
            'bank_id'   => "required",
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }
        $track = TemporaryData::where('identifier',request()->trx)->where('type',PaymentGatewayConst::TYPEMONEYOUT)->first();
        if(!$track){
            $error = ['error'=>[__("Sorry, your payment information is invalid")]];
            return Helpers::error($error);
        }
        if($track['data']->gateway_name != "flutterwave"){
            $error = ['error'=>[__("Sorry, This Payment Request Is Not For FlutterWave")]];
            return Helpers::error($error);
        }
        $countries = get_all_countries();
        $currency = $track['data']->gateway_currency;
        $country = Collection::make($countries)->first(function ($item) use ($currency) {
            return $item->currency_code === $currency;
        });

        $bank_branches = branch_required_countries($country->iso2,request()->bank_id);

        $data =[
            'bank_branches' =>$bank_branches['branches']??[]
        ];
        $message =  ['success'=>[__("Bank branches fetched successfully")]];
        return Helpers::success($data, $message);

    }
    //admin notification global(Agent & User)
    public function adminNotification($data,$status){
        $user = auth()->guard(userGuard()['guard'])->user();
        $exchange_rate = " 1 ". get_default_currency_code().' = '. get_amount($data->gateway_rate,$data->gateway_currency);
        if($status == PaymentGatewayConst::STATUSSUCCESS){
            $status ="success";
        }elseif($status == PaymentGatewayConst::STATUSPENDING){
            $status ="Pending";
        }elseif($status == PaymentGatewayConst::STATUSHOLD){
            $status ="Hold";
        }elseif($status == PaymentGatewayConst::STATUSWAITING){
            $status ="Waiting";
        }elseif($status == PaymentGatewayConst::STATUSPROCESSING){
            $status ="Processing";
        }elseif($status == PaymentGatewayConst::STATUSFAILD){
            $status ="Failed";
        }

        $notification_content = [
            //email notification
            'subject' =>__("Withdraw Money")." (".userGuard()['type'].")",
            'greeting' =>__("Withdraw Money Via")." ".$data->gateway_name.' ('.$data->gateway_type.' )',
            'email_content' =>__("web_trx_id")." : ".$data->trx_id."<br>".__("request Amount")." : ".get_amount($data->amount,get_default_currency_code())."<br>".__("Exchange Rate")." : ". $exchange_rate."<br>".__("Fees & Charges")." : ". get_amount($data->gateway_charge,$data->gateway_currency)."<br>".__("Total Payable Amount")." : ".get_amount($data->payable,get_default_currency_code())."<br>".__("Will Get")." : ".get_amount($data->will_get,$data->gateway_currency,2)."<br>".__("Status")." : ".__($status),
            //push notification
            'push_title' =>  __("Withdraw Money")." (".userGuard()['type'].")",
            'push_content' => __('web_trx_id')." ".$data->trx_id." ". __("Withdraw Money").' '.get_amount($data->amount,get_default_currency_code()).' '.__('By').' '.$data->gateway_name.' ('.$user->username.')',

            //admin db notification
            'notification_type' =>  NotificationConst::MONEY_OUT,
            'trx_id' => $data->trx_id,
            'admin_db_title' =>  "Withdraw Money"." (".userGuard()['type'].")",
            'admin_db_message' =>  "Withdraw Money".' '.get_amount($data->amount,get_default_currency_code()).' '.'By'.' '.$data->gateway_name.' ('.$user->username.')'
        ];

        try{
            //notification
            (new NotificationHelper())->admin(['admin.money.out.index','admin.money.out.pending','admin.money.out.complete','admin.money.out.canceled','admin.money.out.details','admin.money.out.approved','admin.money.out.rejected','admin.money.out.export.data'])
                                    ->mail(ActivityNotification::class, [
                                        'subject'   => $notification_content['subject'],
                                        'greeting'  => $notification_content['greeting'],
                                        'content'   => $notification_content['email_content'],
                                    ])
                                    ->push([
                                        'user_type' => "admin",
                                        'title' => $notification_content['push_title'],
                                        'desc'  => $notification_content['push_content'],
                                    ])
                                    ->adminDbContent([
                                        'type' => $notification_content['notification_type'],
                                        'title' => $notification_content['admin_db_title'],
                                        'message'  => $notification_content['admin_db_message'],
                                    ])
                                    ->send();


        }catch(Exception $e) {}

    }
}
