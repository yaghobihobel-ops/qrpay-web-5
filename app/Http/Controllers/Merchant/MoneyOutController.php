<?php

namespace App\Http\Controllers\Merchant;

use App\Constants\NotificationConst;
use App\Constants\PaymentGatewayConst;
use App\Http\Controllers\Controller;
use App\Http\Helpers\NotificationHelper;
use App\Http\Helpers\PushNotificationHelper;
use Illuminate\Http\Request;
use App\Models\Admin\Currency;
use App\Models\Admin\PaymentGateway;
use App\Models\Admin\PaymentGatewayCurrency;
use App\Models\Transaction;
use Illuminate\Support\Facades\Validator;
use App\Traits\ControlDynamicInputFields;
use Exception;
use Illuminate\Support\Facades\DB;
use Jenssegers\Agent\Agent;
use App\Models\Admin\BasicSettings;
use App\Models\Merchants\MerchantNotification;
use App\Models\Merchants\MerchantWallet;
use App\Notifications\Admin\ActivityNotification;
use App\Notifications\User\Withdraw\WithdrawMail;
use App\Providers\Admin\BasicSettingsProvider;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class MoneyOutController extends Controller
{
    use ControlDynamicInputFields;

    protected $basic_settings;
    public function __construct()
    {
        $this->basic_settings = BasicSettingsProvider::get();
    }

    public function index()
    {
        $page_title = __("Withdraw Money");
        $user_wallets = MerchantWallet::auth()->get();
        $user_currencies = Currency::whereIn('id',$user_wallets->pluck('id')->toArray())->get();

        $payment_gateways = PaymentGatewayCurrency::whereHas('gateway', function ($gateway) {
            $gateway->where('slug', PaymentGatewayConst::money_out_slug());
            $gateway->where('status', 1);
        })->get();
        $transactions = Transaction::merchantAuth()->moneyOut()->orderByDesc("id")->latest()->take(10)->get();
        return view('merchant.sections.withdraw.index',compact('page_title','payment_gateways','transactions','user_wallets'));
    }

   public function paymentInsert(Request $request){
        $request->validate([
            'amount' => 'required|numeric|gt:0',
            'gateway' => 'required'
        ]);
        $user = userGuard()['user'];

        $userWallet = MerchantWallet::where('merchant_id',$user->id)->where('status',1)->first();
        $gate =PaymentGatewayCurrency::whereHas('gateway', function ($gateway) {
            $gateway->where('slug', PaymentGatewayConst::money_out_slug());
            $gateway->where('status', 1);
        })->where('alias',$request->gateway)->first();
        $precision = get_precision($gate->gateway);

        $baseCurrency = Currency::default();
        if (!$gate) {
            return back()->with(['error' => [__('Gateway not available')]]);
        }
        $amount = $request->amount;

        $min_limit =  get_amount($gate->min_limit / $gate->rate,null,$precision);
        $max_limit =  get_amount($gate->max_limit / $gate->rate,null,$precision);
        if($amount < $min_limit || $amount > $max_limit) {
            return back()->with(['error' => [__("Please follow the transaction limit")]]);
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
            return back()->with(['error' => [__('Sorry, insufficient balance')]]);
        }
        $data['merchant_id']= $user->id;
        $data['gateway_name']= $gate->gateway->name;
        $data['gateway_type']= $gate->gateway->type;
        $data['wallet_id']= $userWallet->id;
        $data['trx_id']= 'MO'.getTrxNum();
        $data['amount'] =  get_amount($amount,null,$precision);
        $data['base_cur_charge'] = get_amount($base_total_charge,null,$precision);
        $data['base_cur_rate'] = get_amount($baseCurrency->rate,null,$precision);
        $data['gateway_id'] = $gate->gateway->id;
        $data['gateway_currency_id'] = $gate->id;
        $data['gateway_currency'] = strtoupper($gate->currency_code);
        $data['gateway_percent_charge'] = get_amount($percent_charge,null,$precision);
        $data['gateway_fixed_charge'] = get_amount($fixedCharge,null,$precision);
        $data['gateway_charge'] = get_amount($charge,null,$precision);
        $data['gateway_rate'] = get_amount($gate->rate,null,$precision);
        $data['conversion_amount'] = get_amount($conversion_amount,null,$precision);
        $data['will_get'] = get_amount($will_get,null,$precision);
        $data['payable'] = get_amount($reduceAbleTotal,null,$precision);
   
        session()->put('moneyoutData', $data);
        return redirect()->route('merchant.withdraw.preview');
   }
   public function preview(){
    $moneyOutData = (object)session()->get('moneyoutData');
    $moneyOutDataExist = session()->get('moneyoutData');
    if($moneyOutDataExist  == null){
        return redirect()->route('merchant.withdraw.index');
    }
    $gateway = PaymentGateway::where('id', $moneyOutData->gateway_id)->first();
    if($gateway->type == "AUTOMATIC"){
        $page_title = __("Withdraw Via")." ".$gateway->name;
        if(strtolower($gateway->name) == "flutterwave"){
            $credentials = $gateway->credentials;
            $data = null;
            foreach ($credentials as $object) {
                $object = (object)$object;
                if ($object->label === "Secret key") {
                    $data = $object;
                    break;
                }
            }
            $countries = get_all_countries();
            $currency =  $moneyOutData->gateway_currency;
            $country = Collection::make($countries)->first(function ($item) use ($currency) {
                return $item->currency_code === $currency;
            });

            $allBanks = getFlutterwaveBanks($country->iso2);
            return view('merchant.sections.withdraw.automatic.'.strtolower($gateway->name),compact('page_title','gateway','moneyOutData','allBanks','country'));
        }else{
            return back()->with(['error' => [__("Something went wrong! Please try again.")]]);
        }
    }else{
        $page_title = __("Withdraw Via")." ".$gateway->name;
        return view('merchant.sections.withdraw.preview',compact('page_title','gateway','moneyOutData'));
    }

   }
   public function confirmMoneyOut(Request $request){
      $basic_setting = BasicSettings::first();
    $moneyOutData = (object)session()->get('moneyoutData');
    $gateway = PaymentGateway::where('id', $moneyOutData->gateway_id)->first();
    $payment_fields = $gateway->input_fields ?? [];

    $validation_rules = $this->generateValidationRules($payment_fields);
    $payment_field_validate = Validator::make($request->all(),$validation_rules)->validate();
    $get_values = $this->placeValueWithFields($payment_fields,$payment_field_validate);
        try{
            //send notifications
            $user = auth()->user();
            $inserted_id = $this->insertRecordManual($moneyOutData,$gateway,$get_values,$reference= null,PaymentGatewayConst::STATUSPENDING);
            $this->insertChargesManual($moneyOutData,$inserted_id);
            $this->adminNotification($moneyOutData,PaymentGatewayConst::STATUSPENDING);
            $this->insertDeviceManual($moneyOutData,$inserted_id);
            session()->forget('moneyoutData');
            try{
                if( $basic_setting->merchant_email_notification == true){
                   $user->notify(new WithdrawMail($user,$moneyOutData));
               }
            }catch(Exception $e){

            }
            return redirect()->route("merchant.withdraw.index")->with(['success' => [__('Withdraw money request send to admin successful')]]);
        }catch(Exception $e) {
            return redirect()->route("merchant.withdraw.index")->with(['error' => [__("Something went wrong! Please try again.")]]);
        }

   }
   public function confirmMoneyOutAutomatic(Request $request){
    $basic_setting = BasicSettings::first();
    $moneyOutData = (object)session()->get('moneyoutData');
    $gateway = PaymentGateway::where('id', $moneyOutData->gateway_id)->first();
    $gateway_iso2 = getewayIso2($moneyOutData->gateway_currency??get_default_currency_code());

    if($request->gateway_name == 'flutterwave'){
        $branch_status = branch_required_permission($gateway_iso2);

        $request->validate([
            'bank_name'         => 'required',
            'account_number'    => 'required',
            'beneficiary_name'  => 'required|string',
            'branch_code'       => $branch_status == true ? 'required':'nullable',
        ]);

        $credentials = $gateway->credentials;
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
            "currency" =>$moneyOutData->gateway_currency,
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
                try{
                    $get_values =[
                        'user_data' => $result['data'],
                        'charges' => [],
                    ];
                    //send notifications
                    $user = auth()->user();
                    $inserted_id = $this->insertRecordManual($moneyOutData,$gateway,$get_values,$reference,PaymentGatewayConst::STATUSWAITING);
                    $this->insertChargesAutomatic($moneyOutData,$inserted_id);
                    $this->adminNotification($moneyOutData,PaymentGatewayConst::STATUSSUCCESS);
                    $this->insertDeviceManual($moneyOutData,$inserted_id);
                    session()->forget('moneyoutData');
                    try{
                        if( $basic_setting->merchant_email_notification == true){
                            $user->notify(new WithdrawMail($user,$moneyOutData));
                        }
                    }catch(Exception $e){}
                    return redirect()->route("merchant.withdraw.index")->with(['success' => [__('Withdraw money request send successful')]]);
                }catch(Exception $e) {
                    return back()->with(['error' => [__("Something went wrong! Please try again.")]]);
                }

            }else if($result['status'] && $result['status'] == 'error'){
                if(isset($result['data'])){
                    $errors = $result['message'].",".$result['data']['complete_message']??"";
                }else{
                    $errors = $result['message'];
                }
                return back()->with(['error' => [ $errors]]);
            }else{
                return back()->with(['error' => [$result['message']]]);;
            }

        curl_close($ch);

    }else{
        return back()->with(['error' => [__("Invalid request,please try again later")]]);
    }
   }

   //check flutterwave banks
   public function checkBanks(Request $request){
        $bank_account = $request->account_number;
        $bank_code = $request->bank_code;
        $exist['data'] = checkBankAccount($bank_account,$bank_code);
        return response( $exist);
   }
   //Get flutterwave banks branches
   public function getFlutterWaveBankBranches(Request $request){
        $iso2 = $request->iso2;
        $bank_id = $request->bank_id;
        $data = branch_required_countries($iso2,$bank_id);
        return response($data);
    }

    public function insertRecordManual($moneyOutData,$gateway,$get_values,$reference,$status) {
        $trx_id = $moneyOutData->trx_id ??'MO'.getTrxNum();
        $authWallet = MerchantWallet::where('id',$moneyOutData->wallet_id)->where('merchant_id',$moneyOutData->merchant_id)->first();
        if($moneyOutData->gateway_type != "AUTOMATIC"){
            $afterCharge = ($authWallet->balance - ($moneyOutData->amount));
        }else{
            $afterCharge = $authWallet->balance;
        }

        DB::beginTransaction();
        try{
            $id = DB::table("transactions")->insertGetId([
                'merchant_id'                   => $moneyOutData->merchant_id,
                'merchant_wallet_id'            => $moneyOutData->wallet_id,
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
            throw new Exception(__("Something went wrong! Please try again."));
        }
        return $id;
    }


    public function updateWalletBalanceManual($authWalle,$afterCharge) {
        $authWalle->update([
            'balance'   => $afterCharge,
        ]);
    }
    public function insertChargesAutomatic($moneyOutData,$id) {
        if(Auth::guard(get_auth_guard())->check()){
            $merchant = auth()->guard(get_auth_guard())->user();
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
                'image'         => get_image($merchant->image,'merchant-profile'),
            ];

            MerchantNotification::create([
                'type'      => NotificationConst::MONEY_OUT,
                'merchant_id'  =>  $moneyOutData->merchant_id,
                'message'   => $notification_content,
            ]);

            //Push Notifications
            if( $this->basic_settings->merchant_push_notification == true){
                try{
                    (new PushNotificationHelper())->prepare([$merchant->id],[
                        'title' => $notification_content['title'],
                        'desc'  => $notification_content['message'],
                        'user_type' => 'merchant',
                    ])->send();
                }catch(Exception $e) {}
            }
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception(__("Something went wrong! Please try again."));
        }
    }
    public function insertChargesManual($moneyOutData,$id) {
        if(Auth::guard(get_auth_guard())->check()){
            $merchant = auth()->guard(get_auth_guard())->user();
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
                'message'       => __("Your Withdraw Request Send To Admin")." " .$moneyOutData->amount.' '.get_default_currency_code()." ".__("Successful"),
                'image'         => get_image($merchant->image,'merchant-profile'),
            ];

            MerchantNotification::create([
                'type'      => NotificationConst::MONEY_OUT,
                'merchant_id'  =>  $moneyOutData->merchant_id,
                'message'   => $notification_content,
            ]);

            //Push Notifications
            if( $this->basic_settings->merchant_push_notification == true){
                try{
                    (new PushNotificationHelper())->prepare([$merchant->id],[
                        'title' => $notification_content['title'],
                        'desc'  => $notification_content['message'],
                        'user_type' => 'merchant',
                    ])->send();
                }catch(Exception $e) {}
            }
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception(__("Something went wrong! Please try again."));
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
            throw new Exception(__("Something went wrong! Please try again."));
        }
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
