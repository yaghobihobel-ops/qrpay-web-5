<?php

namespace App\Traits\PaymentGateway;

use App\Constants\NotificationConst;
use App\Constants\PaymentGatewayConst;
use App\Http\Helpers\Api\Helpers;
use App\Http\Helpers\PushNotificationHelper;
use App\Models\Admin\BasicSettings;
use App\Models\TemporaryData;
use App\Models\UserNotification;
use App\Notifications\User\AddMoney\ApprovedMail;
use App\Providers\Admin\BasicSettingsProvider;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;
use App\Traits\TransactionAgent;
use App\Traits\PayLink\TransactionTrait;


trait Stripe
{
    use TransactionAgent,TransactionTrait;
    public function stripeInit($output = null) {
        $basic_settings = BasicSettingsProvider::get();
        if(!$output) $output = $this->output;
        $credentials = $this->getStripeCredentials($output);

        if($output['type'] === PaymentGatewayConst::TYPEADDMONEY){
           return  $this->setupInitDataAddMoney($output,$credentials,$basic_settings);
        }else{
            return  $this->setupInitDataPayLink($output,$credentials,$basic_settings);
        }

    }
    public function setupInitDataAddMoney($output,$credentials,$basic_settings){
        $reference = generateTransactionReference();
        $amount = $output['amount']->total_amount ? number_format($output['amount']->total_amount,2,'.','') : 0;
        $currency = $output['currency']['currency_code']??"USD";
        if(auth()->guard(get_auth_guard())->check()){
            $user = auth()->guard(get_auth_guard())->user();
            $user_email = $user->email;
            $user_phone = $user->full_mobile ?? '';
            $user_name = $user->firstname.' '.$user->lastname ?? '';
        }
        if(userGuard()['guard']  === 'web'){
            $return_url = route('user.add.money.stripe.payment.success', $reference);
        }elseif(userGuard()['guard']  === 'agent'){
            $return_url = route('agent.add.money.stripe.payment.success', $reference);
        }


         // Enter the details of the payment
         $data = [
            'payment_options' => 'card',
            'amount'          => $amount,
            'email'           => $user_email,
            'tx_ref'          => $reference,
            'currency'        =>  $currency,
            'redirect_url'    => $return_url,
            'customer'        => [
                'email'        => $user_email,
                "phone_number" => $user_phone,
                "name"         => $user_name
            ],
            "customizations" => [
                "title"       => "Add Money",
                "description" => dateFormat('d M Y', Carbon::now()),
            ]
        ];

       //start stripe pay link
       $stripe = new \Stripe\StripeClient($credentials->secret_key);

       //create product for Product Id
       try{
            $product_id = $stripe->products->create([
                'name' => 'Add Money( '.$basic_settings->site_name.' )',
            ]);
       }catch(Exception $e){
            throw new Exception(__("Something went wrong! Please try again."));
       }
       //create price for Price Id
       try{
            $price_id =$stripe->prices->create([
                'currency' =>  $currency,
                'unit_amount' => $amount*100,
                'product' => $product_id->id??""
              ]);
       }catch(Exception $e){
            throw new Exception("Something Is Wrong, Please Contact With Owner");
       }

    //    create payment live links
       try{
            $payment_link = $stripe->paymentLinks->create([
                'line_items' => [
                [
                    'price' => $price_id->id,
                    'quantity' => 1,
                ],
                ],
                'after_completion' => [
                'type' => 'redirect',
                'redirect' => ['url' => $return_url],
                ],


            ]);
        }catch(Exception $e){
            throw new Exception("Something Is Wrong, Please Contact With Owner");
        }
        $this->stripeJunkInsert($data);

        return redirect($payment_link->url."?prefilled_email=".@$user->email);

    }
    public function setupInitDataPayLink($output,$credentials,$basic_settings){
        $reference = generateTransactionReference();
        $amount = $output['charge_calculation']['requested_amount'] ? number_format($output['charge_calculation']['requested_amount'],2,'.','') : 0;
        $currency = $output['charge_calculation']['sender_cur_code']??"USD";
        $return_url = route('payment-link.gateway.payment.stripe.success', $reference);

         // Enter the details of the payment
         $data = [
            'payment_options' => 'card',
            'amount'          => $amount,
            'email'           => $output['validated']['email'] ?? '',
            'tx_ref'          => $reference,
            'currency'        =>  $currency,
            'redirect_url'    => $return_url,
            'customer'        => [
                'email'        => $output['validated']['email'] ?? '',
                "phone_number" => '',
                "name"         => $output['validated']['full_name'] ?? '',
            ],
            "customizations" => [
                "title"       => __("Payment Link"),
                "description" => dateFormat('d M Y', Carbon::now()),
            ]
        ];

       //start stripe pay link
       $stripe = new \Stripe\StripeClient($credentials->secret_key);

       //create product for Product Id
       try{
            $product_id = $stripe->products->create([
                'name' => __("Payment Link").' ( '.$basic_settings->site_name.' )',
            ]);
       }catch(Exception $e){
            throw new Exception(__("Something went wrong! Please try again."));
       }
       //create price for Price Id
       try{
            $price_id =$stripe->prices->create([
                'currency' =>  $currency,
                'unit_amount' => $amount * 100,
                'product' => $product_id->id??""
              ]);
       }catch(Exception $e){
            throw new Exception("Something Is Wrong, Please Contact With Owner");
       }
       //create payment live links
       try{
            $payment_link = $stripe->paymentLinks->create([
                'line_items' => [
                [
                    'price' => $price_id->id,
                    'quantity' => 1,
                ],
                ],
                'after_completion' => [
                'type' => 'redirect',
                'redirect' => ['url' => $return_url],
                ],


            ]);
        }catch(Exception $e){
            throw new Exception("Something Is Wrong, Please Contact With Owner");
        }
        $this->stripeJunkInsertPayLink($data);

        return redirect($payment_link->url."?prefilled_email=".@$output['validated']['email']);

    }
    public function getStripeCredentials($output) {
        $gateway = $output['gateway'] ?? null;
        if(!$gateway) throw new Exception(__("Payment gateway not available"));
        $client_id_sample = ['publishable_key','publishable key','publishable-key'];
        $client_secret_sample = ['secret id','secret-id','secret_id'];

        $client_id = '';
        $outer_break = false;
        foreach($client_id_sample as $item) {
            if($outer_break == true) {
                break;
            }
            $modify_item = $this->stripePlainText($item);
            foreach($gateway->credentials ?? [] as $gatewayInput) {
                $label = $gatewayInput->label ?? "";
                $label = $this->stripePlainText($label);

                if($label == $modify_item) {
                    $client_id = $gatewayInput->value ?? "";
                    $outer_break = true;
                    break;
                }
            }
        }


        $secret_id = '';
        $outer_break = false;
        foreach($client_secret_sample as $item) {
            if($outer_break == true) {
                break;
            }
            $modify_item = $this->stripePlainText($item);
            foreach($gateway->credentials ?? [] as $gatewayInput) {
                $label = $gatewayInput->label ?? "";
                $label = $this->stripePlainText($label);

                if($label == $modify_item) {
                    $secret_id = $gatewayInput->value ?? "";
                    $outer_break = true;
                    break;
                }
            }
        }

        return (object) [
            'publish_key'     => $client_id,
            'secret_key' => $secret_id,

        ];

    }
    public function stripePlainText($string) {
        $string = Str::lower($string);
        return preg_replace("/[^A-Za-z0-9]/","",$string);
    }
    public function stripeJunkInsert($response) {
        $output = $this->output;
        $creator_table = $creator_id = $wallet_table = $wallet_id = null;
        if(authGuardApi()['type']  == "AGENT"){
            $creator_table = authGuardApi()['user']->getTable();
            $creator_id = authGuardApi()['user']->id;
            $creator_guard = authGuardApi()['guard'];
            $wallet_table = $output['wallet']->getTable();
            $wallet_id = $output['wallet']->id;
        }else{
            $creator_table = auth()->guard(get_auth_guard())->user()->getTable();
            $creator_id = auth()->guard(get_auth_guard())->user()->id;
            $creator_guard = get_auth_guard();
            $wallet_table = $output['wallet']->getTable();
            $wallet_id = $output['wallet']->id;
        }

            $data = [
                'gateway'      => $output['gateway']->id,
                'currency'     => $output['currency']->id,
                'amount'       => json_decode(json_encode($output['amount']),true),
                'response'     => $response,
                'wallet_table'  => $wallet_table,
                'wallet_id'     => $wallet_id,
                'creator_table' => $creator_table,
                'creator_id'    => $creator_id,
                'creator_guard' => $creator_guard,
            ];

        return TemporaryData::create([
            'type'          => PaymentGatewayConst::STRIPE,
            'identifier'    => $response['tx_ref'],
            'data'          => $data,
        ]);
    }
    public function stripeJunkInsertPayLink($response) {
        $output = $this->output;
        $wallet_table = $output['wallet']->getTable();
        $wallet_id = $output['wallet']->id;
        $user_relation_name = strtolower($output['user_type'])??'user';

        $data = [
            'type'                  => $output['type'],
            'gateway'               => $output['gateway']->id,
            'currency'              => $output['currency']->id,
            'validated'             => $output['validated'],
            'charge_calculation'    => json_decode(json_encode($output['charge_calculation']),true),
            'response'              => $response,
            'wallet_table'          => $wallet_table,
            'wallet_id'             => $wallet_id,
            'creator_guard'         => $output['user_guard']??'',
            'user_type'             => $output['user_type']??'',
            'user_id'               => $output['wallet']->$user_relation_name->id??'',
        ];
        return TemporaryData::create([
            'type'       => PaymentGatewayConst::STRIPE,
            'identifier' => $response['tx_ref'],
            'data'       => $data,
        ]);
    }
    public function stripeSuccess($output = null) {
        if(!$output) $output = $this->output;
        $token = $this->output['tempData']['identifier'] ?? "";
        if(empty($token)) throw new Exception(__("Transaction Failed. The record didn't save properly. Please try again"));
        if($output['type'] === PaymentGatewayConst::TYPEADDMONEY){
            if(userGuard()['type'] == "USER"){
                return $this->createTransactionStripe($output);
            }else{
                return $this->createTransactionChildRecords($output,PaymentGatewayConst::STATUSSUCCESS);
            }
        }else{
           return $this->createTransactionPayLink($output,PaymentGatewayConst::STATUSSUCCESS);
        }


    }
    public function createTransactionStripe($output) {
        $basic_setting = BasicSettings::first();
        $user = auth()->user();
        $trx_id = 'AM'.getTrxNum();
        $inserted_id = $this->insertRecordStripe($output,$trx_id);
        $this->insertChargesStripe($output,$inserted_id);
        $this->adminNotification($trx_id,$output,PaymentGatewayConst::STATUSSUCCESS);
        $this->insertDeviceStripe($output,$inserted_id);
        $this->removeTempDataStripe($output);

        if($this->requestIsApiUser()) {
            // logout user
            $api_user_login_guard = $this->output['api_login_guard'] ?? null;
            if($api_user_login_guard != null) {
                auth()->guard($api_user_login_guard)->logout();
            }
        }
        try{
            if( $basic_setting->email_notification == true){
                try{
                    $user->notify(new ApprovedMail($user,$output,$trx_id));
                }catch(Exception $e){}
            }
        }catch(Exception $e){}
    }
    public function insertRecordStripe($output,$trx_id) {

        $trx_id = $trx_id;
        $token = $this->output['tempData']['identifier'] ?? "";
        DB::beginTransaction();
        try{

            if(Auth::guard(get_auth_guard())->check()){
                $user_id = auth()->guard(get_auth_guard())->user()->id;
            }
            $id = DB::table("transactions")->insertGetId([
                'user_id'                       =>  $user_id,
                'user_wallet_id'                => $output['wallet']->id,
                'payment_gateway_currency_id'   => $output['currency']->id,
                'type'                          =>  "ADD-MONEY",
                'trx_id'                        => $trx_id,
                'request_amount'                => $output['amount']->requested_amount,
                'payable'                       => $output['amount']->total_amount,
                'available_balance'             => $output['wallet']->balance + $output['amount']->requested_amount,
                'remark'                        => ucwords(remove_speacial_char(PaymentGatewayConst::TYPEADDMONEY," ")) . " With " . $output['gateway']->name,
                'details'                       => "Stripe payment successful",
                'status'                        => true,
                'attribute'                      =>PaymentGatewayConst::SEND,
                'created_at'                    => now(),
            ]);

            $this->updateWalletBalanceStripe($output);
            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception(__("Something went wrong! Please try again."));
        }
        return $id;
    }
    public function updateWalletBalanceStripe($output) {
        $update_amount = $output['wallet']->balance + $output['amount']->requested_amount;
        $output['wallet']->update([
            'balance'   => $update_amount,
        ]);
    }
    public function insertChargesStripe($output,$id) {

        if(Auth::guard(get_auth_guard())->check()){
            $user = auth()->guard(get_auth_guard())->user();
        }
        DB::beginTransaction();
        try{
            DB::table('transaction_charges')->insert([
                'transaction_id'    => $id,
                'percent_charge'    => $output['amount']->percent_charge,
                'fixed_charge'      => $output['amount']->fixed_charge,
                'total_charge'      => $output['amount']->total_charge,
                'created_at'        => now(),
            ]);
            DB::commit();

            //notification
            $notification_content = [
                'title'         => __("Add Money"),
                'message'       => __("Your Wallet")." (".$output['wallet']->currency->code.")  ".__("balance  has been added")." ".$output['amount']->requested_amount.' '. $output['wallet']->currency->code,
                'time'          => Carbon::now()->diffForHumans(),
                'image'         => get_image($user->image,'user-profile'),
            ];

            UserNotification::create([
                'type'      => NotificationConst::BALANCE_ADDED,
                'user_id'  =>  auth()->user()->id,
                'message'   => $notification_content,
            ]);

             //Push Notifications
            try{
                (new PushNotificationHelper())->prepare([$user->id],[
                    'title' => $notification_content['title'],
                    'desc'  => $notification_content['message'],
                    'user_type' => 'user',
                ])->send();
             }catch(Exception $e) {}

            //admin notification

            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception(__("Something went wrong! Please try again."));
        }
    }
    public function insertDeviceStripe($output,$id) {
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
    public function removeTempDataStripe($output) {
        TemporaryData::where("identifier",$output['tempData']['identifier'])->delete();
    }
    //for api
    public function stripeInitApi($output = null) {
        $basic_settings = BasicSettingsProvider::get();
        if(!$output) $output = $this->output;
        $credentials = $this->getStripeCredentials($output);
        $reference = generateTransactionReference();
        $amount = $output['amount']->total_amount ? number_format($output['amount']->total_amount,2,'.','') : 0;
        $currency = $output['currency']['currency_code']??"USD";

        if(authGuardApi()['guard'] == 'agent_api'){
            $user = authGuardApi()['user'];
            $user_email = $user->email;
            $user_phone = $user->full_mobile ?? '';
            $user_name = $user->firstname.' '.$user->lastname ?? '';
            $return_url = route('agent.api.stripe.payment.success', $reference."?r-source=".PaymentGatewayConst::APP);
        }elseif(auth()->guard(get_auth_guard())->check()){
            $user = auth()->guard(get_auth_guard())->user();
            $user_email = $user->email;
            $user_phone = $user->full_mobile ?? '';
            $user_name = $user->firstname.' '.$user->lastname ?? '';
            $return_url = route('api.stripe.payment.success', $reference."?r-source=".PaymentGatewayConst::APP);
        }
         // Enter the details of the payment
         $data = [
            'payment_options' => 'card',
            'amount'          => $amount,
            'email'           => $user_email,
            'tx_ref'          => $reference,
            'currency'        =>  $currency,
            'redirect_url'    => $return_url,
            'customer'        => [
                'email'        => $user_email,
                "phone_number" => $user_phone,
                "name"         => $user_name
            ],
            "customizations" => [
                "title"       => "Add Money",
                "description" => dateFormat('d M Y', Carbon::now()),
            ]
        ];

       //start stripe pay link
       $stripe = new \Stripe\StripeClient($credentials->secret_key);

       //create product for Product Id
       try{
            $product_id = $stripe->products->create([
                'name' => 'Add Money( '.$basic_settings->site_name.' )',
            ]);
       }catch(Exception $e){
            $error = ['error'=>[__("Something went wrong! Please try again.")]];
            return Helpers::error($error);
       }
       //create price for Price Id
       try{
            $price_id =$stripe->prices->create([
                'currency' =>  $currency,
                'unit_amount' => $amount*100,
                'product' => $product_id->id??""
              ]);
       }catch(Exception $e){
            $error = ['error'=>[__("Something went wrong! Please try again.")]];
            return Helpers::error($error);
       }
       //create payment live links
       try{
            $payment_link = $stripe->paymentLinks->create([
                'line_items' => [
                [
                    'price' => $price_id->id,
                    'quantity' => 1,
                ],
                ],
                'after_completion' => [
                'type' => 'redirect',
                'redirect' => ['url' => $return_url],
                ],
            ]);
        }catch(Exception $e){

            $error = ['error'=>[__("Something went wrong! Please try again.")]];
            return Helpers::error($error);
        }
        $data['link'] =  $payment_link->url;
        $data['trx'] =  $reference;

        $this->stripeJunkInsert($data);
        return $data;

    }

}
