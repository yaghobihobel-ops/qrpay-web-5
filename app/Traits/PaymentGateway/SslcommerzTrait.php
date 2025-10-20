<?php

namespace App\Traits\PaymentGateway;

use App\Constants\NotificationConst;
use App\Constants\PaymentGatewayConst;
use App\Http\Helpers\PushNotificationHelper;
use App\Models\Admin\BasicSettings;
use App\Models\TemporaryData;
use App\Models\UserNotification;
use App\Notifications\User\AddMoney\ApprovedMail;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Jenssegers\Agent\Agent;
use Illuminate\Support\Str;
use App\Traits\TransactionAgent;
use App\Traits\PayLink\TransactionTrait;


trait SslcommerzTrait
{
    use TransactionAgent,TransactionTrait;
    public function sslcommerzInit($output = null) {
        if(!$output) $output = $this->output;
        $credentials = $this->getSslCredentials($output);
        $reference = generateTransactionReference();
        if($output['type'] === PaymentGatewayConst::TYPEADDMONEY){
            return  $this->setupSslInitAddMoney($output,$credentials,$reference);
         }else{
             return  $this->setupSslInitPayLink($output,$credentials,$reference);
         }
    }
    public function setupSslInitAddMoney($output,$credentials,$reference){
        $amount = $output['amount']->total_amount ? number_format($output['amount']->total_amount,2,'.','') : 0;
        $currency = $output['currency']['currency_code']??"USD";

        if(auth()->guard(get_auth_guard())->check()){
            $user = auth()->guard(get_auth_guard())->user();
            $user_email = $user->email;
            $user_phone = $user->full_mobile ?? '';
            $user_name = $user->firstname.' '.$user->lastname ?? '';
        }

        $post_data = array();
        $post_data['store_id'] = $credentials->store_id??"";
        $post_data['store_passwd'] = $credentials->store_password??"";
        $post_data['total_amount'] =$amount;
        $post_data['currency'] = $currency;
        $post_data['tran_id'] =  $reference;

        if(userGuard()['guard'] == 'web'){
            $post_data['success_url'] =  route('add.money.ssl.success');
            $post_data['fail_url'] = route('add.money.ssl.fail');
            $post_data['cancel_url'] = route('add.money.ssl.cancel');

        } elseif(userGuard()['guard'] == 'agent'){
            $post_data['success_url'] =  route('agent.add.money.ssl.success');
            $post_data['fail_url'] = route('agent.add.money.ssl.fail');
            $post_data['cancel_url'] = route('agent.add.money.ssl.cancel');

        }

        # $post_data['multi_card_name'] = "mastercard,visacard,amexcard";  # DISABLE TO DISPLAY ALL AVAILABLE

        # EMI INFO
        $post_data['emi_option'] = "0";
        $post_data['emi_max_inst_option'] = "9";
        $post_data['emi_selected_inst'] = "9";

        # CUSTOMER INFORMATION
        $post_data['cus_name'] = $user->fullname??"Test Customer";
        $post_data['cus_email'] = $user->email??"test@test.com";
        $post_data['cus_add1'] = $user->address->country??"Dhaka";
        $post_data['cus_add2'] = $user->address->address??"Dhaka";
        $post_data['cus_city'] = $user->address->city??"Dhaka";
        $post_data['cus_state'] = $user->address->state??"Dhaka";
        $post_data['cus_postcode'] = $user->address->zip??"1000";
        $post_data['cus_country'] = $user->address->country??"Bangladesh";
        $post_data['cus_phone'] = $user->full_mobile??"01711111111";
        $post_data['cus_fax'] = "";



        # PRODUCT INFORMATION
        $post_data['product_name'] = "Add Money";
        $post_data['product_category'] = "Add Money";
        $post_data['product_profile'] = "Add Money";
        # SHIPMENT INFORMATION
        $post_data['shipping_method'] = "NO";

         $data = [
            'request_data'    => $post_data,
            'amount'          => $amount,
            'email'           => $user_email,
            'tx_ref'          => $reference,
            'currency'        =>  $currency,
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

        if( $credentials->mode == Str::lower(PaymentGatewayConst::ENV_SANDBOX)){
            $link_url =  $credentials->sandbox_url;
        }else{
            $link_url =  $credentials->live_url;
        }
        # REQUEST SEND TO SSLCOMMERZ
        $direct_api_url = $link_url."/gwprocess/v4/api.php";

        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $direct_api_url );
        curl_setopt($handle, CURLOPT_TIMEOUT, 30);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($handle, CURLOPT_POST, 1 );
        curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($post_data));
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, FALSE); # KEEP IT FALSE IF YOU RUN FROM LOCAL PC


        $content = curl_exec($handle );
        $result = json_decode( $content,true);
        if( $result['status']  != "SUCCESS"){
            throw new Exception($result['failedreason']);
        }
        $this->sslJunkInsert($data);
        return redirect($result['GatewayPageURL']);

    }
    public function setupSslInitPayLink($output,$credentials,$reference){
        $amount = $output['charge_calculation']['requested_amount'] ? number_format($output['charge_calculation']['requested_amount'],2,'.','') : 0;
        $currency = $output['charge_calculation']['sender_cur_code']??"USD";


        $post_data = array();
        $post_data['store_id'] = $credentials->store_id??"";
        $post_data['store_passwd'] = $credentials->store_password??"";
        $post_data['total_amount'] = $amount;
        $post_data['currency'] = $currency;
        $post_data['tran_id'] =  $reference;

        $post_data['success_url'] =  route('payment-link.gateway.payment.ssl.success');
        $post_data['fail_url'] = route('payment-link.gateway.payment.ssl.fail');
        $post_data['cancel_url'] = route('payment-link.gateway.payment.ssl.cancel');


        # $post_data['multi_card_name'] = "mastercard,visacard,amexcard";  # DISABLE TO DISPLAY ALL AVAILABLE
        # EMI INFO
        $post_data['emi_option'] = "0";
        $post_data['emi_max_inst_option'] = "9";
        $post_data['emi_selected_inst'] = "9";

        # CUSTOMER INFORMATION
        $post_data['cus_name'] =  $output['validated']['full_name'];
        $post_data['cus_email'] = $output['validated']['email'];
        $post_data['cus_add1'] = "";
        $post_data['cus_add2'] = "";
        $post_data['cus_city'] = "";
        $post_data['cus_state'] = "";
        $post_data['cus_postcode'] = "";
        $post_data['cus_country'] = "";
        $post_data['cus_phone'] = $output['validated']['phone'];
        $post_data['cus_fax'] = "";




        # PRODUCT INFORMATION
        $post_data['product_name'] =__("Pay Link");
        $post_data['product_category'] =__("Pay Link");
        $post_data['product_profile'] =__("Pay Link");
        # SHIPMENT INFORMATION
        $post_data['shipping_method'] = "NO";

         $data = [
            'request_data'    => $post_data,
            'amount'          => $amount,
            'email'           => $output['validated']['email'],
            'tx_ref'          => $reference,
            'currency'        =>  $currency,
            'customer'        => [
                'email'        => $output['validated']['email'],
                "phone_number" =>  $output['validated']['phone'],
                "name"         => $output['validated']['full_name']
            ],
            "customizations" => [
                "title"       =>__("Pay Link"),
                "description" => dateFormat('d M Y', Carbon::now()),
            ]
        ];

        if( $credentials->mode == Str::lower(PaymentGatewayConst::ENV_SANDBOX)){
            $link_url =  $credentials->sandbox_url;
        }else{
            $link_url =  $credentials->live_url;
        }
        # REQUEST SEND TO SSLCOMMERZ
        $direct_api_url = $link_url."/gwprocess/v4/api.php";

        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $direct_api_url );
        curl_setopt($handle, CURLOPT_TIMEOUT, 30);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($handle, CURLOPT_POST, 1 );
        curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($post_data));
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, FALSE); # KEEP IT FALSE IF YOU RUN FROM LOCAL PC


        $content = curl_exec($handle );
        $result = json_decode( $content,true);
        if( $result['status']  != "SUCCESS"){
            throw new Exception($result['failedreason']);
        }

        $this->sslJunkInsertPayLink($data);
        return redirect($result['GatewayPageURL']);

    }
    public function getSslCredentials($output) {
        $gateway = $output['gateway'] ?? null;
        if(!$gateway) throw new Exception(__("Payment gateway not available"));
        $store_id_sample = ['store_id','Store Id','store-id'];
        $store_password_sample = ['Store Password','store-password','store_password'];
        $sandbox_url_sample = ['Sandbox Url','sandbox-url','sandbox_url'];
        $live_url_sample = ['Live Url','live-url','live_url'];

        $store_id = '';
        $outer_break = false;
        foreach($store_id_sample as $item) {
            if($outer_break == true) {
                break;
            }
            $modify_item = $this->sllPlainText($item);
            foreach($gateway->credentials ?? [] as $gatewayInput) {
                $label = $gatewayInput->label ?? "";
                $label = $this->sllPlainText($label);

                if($label == $modify_item) {
                    $store_id = $gatewayInput->value ?? "";
                    $outer_break = true;
                    break;
                }
            }
        }


        $store_password = '';
        $outer_break = false;
        foreach($store_password_sample as $item) {
            if($outer_break == true) {
                break;
            }
            $modify_item = $this->sllPlainText($item);
            foreach($gateway->credentials ?? [] as $gatewayInput) {
                $label = $gatewayInput->label ?? "";
                $label = $this->sllPlainText($label);

                if($label == $modify_item) {
                    $store_password = $gatewayInput->value ?? "";
                    $outer_break = true;
                    break;
                }
            }
        }
        $sandbox_url = '';
        $outer_break = false;
        foreach($sandbox_url_sample as $item) {
            if($outer_break == true) {
                break;
            }
            $modify_item = $this->sllPlainText($item);
            foreach($gateway->credentials ?? [] as $gatewayInput) {
                $label = $gatewayInput->label ?? "";
                $label = $this->sllPlainText($label);

                if($label == $modify_item) {
                    $sandbox_url = $gatewayInput->value ?? "";
                    $outer_break = true;
                    break;
                }
            }
        }
        $live_url = '';
        $outer_break = false;
        foreach($live_url_sample as $item) {
            if($outer_break == true) {
                break;
            }
            $modify_item = $this->sllPlainText($item);
            foreach($gateway->credentials ?? [] as $gatewayInput) {
                $label = $gatewayInput->label ?? "";
                $label = $this->sllPlainText($label);

                if($label == $modify_item) {
                    $live_url = $gatewayInput->value ?? "";
                    $outer_break = true;
                    break;
                }
            }
        }

        $mode = $gateway->env;

        $paypal_register_mode = [
            PaymentGatewayConst::ENV_SANDBOX => "sandbox",
            PaymentGatewayConst::ENV_PRODUCTION => "live",
        ];
        if(array_key_exists($mode,$paypal_register_mode)) {
            $mode = $paypal_register_mode[$mode];
        }else {
            $mode = "sandbox";
        }

        return (object) [
            'store_id'     => $store_id,
            'store_password' => $store_password,
            'sandbox_url' => $sandbox_url,
            'live_url' => $live_url,
            'mode'          => $mode,

        ];

    }

    public function sllPlainText($string) {
        $string = Str::lower($string);
        return preg_replace("/[^A-Za-z0-9]/","",$string);
    }

    public function sslJunkInsert($response) {
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
            'type'          => PaymentGatewayConst::SSLCOMMERZ,
            'identifier'    => $response['tx_ref'],
            'data'          => $data,
        ]);
    }
    public function sslJunkInsertPayLink($response) {
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
            'type'          => PaymentGatewayConst::SSLCOMMERZ,
            'identifier'    => $response['tx_ref'],
            'data'          => $data,
        ]);
    }

    public function sslcommerzSuccess($output = null) {
        if(!$output) $output = $this->output;
        $token = $this->output['tempData']['identifier'] ?? "";
        if(empty($token)) throw new Exception(__("Transaction Failed. The record didn't save properly. Please try again"));
        if($output['type'] === PaymentGatewayConst::TYPEADDMONEY){
            if(userGuard()['type'] == "USER"){
                return $this->createTransactionSsl($output);
            }else{
                return $this->createTransactionChildRecords($output,PaymentGatewayConst::STATUSSUCCESS);
            }
        }else{
            return $this->createTransactionPayLink($output,PaymentGatewayConst::STATUSSUCCESS);
        }
    }

    public function createTransactionSsl($output) {
        $basic_setting = BasicSettings::first();
        $user = auth()->user();
        $trx_id = 'AM'.getTrxNum();
        $inserted_id = $this->insertRecordSsl($output,$trx_id);
        $this->insertChargesSsl($output,$inserted_id);
        $this->adminNotification($trx_id,$output,PaymentGatewayConst::STATUSSUCCESS);
        $this->insertDeviceSsl($output,$inserted_id);
        $this->removeTempDataSsl($output);

        if($this->requestIsApiUser()) {
            // logout user
            $api_user_login_guard = $this->output['api_login_guard'] ?? null;
            if($api_user_login_guard != null) {
                auth()->guard($api_user_login_guard)->logout();
            }
        }
        try{
            if( $basic_setting->email_notification == true){
                $user->notify(new ApprovedMail($user,$output,$trx_id));
            }
        }catch(Exception $e){}
    }

    public function insertRecordSsl($output,$trx_id) {

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
                'details'                       => PaymentGatewayConst::SSLCOMMERZ." payment successful",
                'status'                        => true,
                'attribute'                      =>PaymentGatewayConst::SEND,
                'created_at'                    => now(),
            ]);

            $this->updateWalletBalanceSsl($output);
            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception(__("Something went wrong! Please try again."));
        }
        return $id;
    }

    public function updateWalletBalanceSsl($output) {
        $update_amount = $output['wallet']->balance + $output['amount']->requested_amount;
        $output['wallet']->update([
            'balance'   => $update_amount,
        ]);
    }

    public function insertChargesSsl($output,$id) {
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
                'image'         =>  get_image($user->image,'user-profile')
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
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception(__("Something went wrong! Please try again."));
        }
    }

    public function insertDeviceSsl($output,$id) {
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

    public function removeTempDataSsl($output) {
        TemporaryData::where("identifier",$output['tempData']['identifier'])->delete();
    }
    //for api
    public function sslcommerzInitApi($output = null) {
        if(!$output) $output = $this->output;
        $credentials = $this->getSslCredentials($output);
        $reference = generateTransactionReference();
        $amount = $output['amount']->total_amount ? number_format($output['amount']->total_amount,2,'.','') : 0;
        $currency = $output['currency']['currency_code']??"USD";


        $post_data = array();

        if(authGuardApi()['guard'] == 'agent_api'){
            $user = authGuardApi()['user'];
            $user_email = $user->email;
            $user_phone = $user->full_mobile ?? '';
            $user_name = $user->firstname.' '.$user->lastname ?? '';
            $post_data['success_url'] =  route('agent.api.add.money.ssl.success',"?r-source=".PaymentGatewayConst::APP);
            $post_data['fail_url'] = route('agent.api.add.money.ssl.fail',"?r-source=".PaymentGatewayConst::APP);
            $post_data['cancel_url'] = route('agent.api.add.money.ssl.cancel',"?r-source=".PaymentGatewayConst::APP);
        }elseif(auth()->guard(get_auth_guard())->check()){
            $user = auth()->guard(get_auth_guard())->user();
            $user_email = $user->email;
            $user_phone = $user->full_mobile ?? '';
            $user_name = $user->firstname.' '.$user->lastname ?? '';
            $post_data['success_url'] =  route('api.add.money.ssl.success',"?r-source=".PaymentGatewayConst::APP);
            $post_data['fail_url'] = route('api.add.money.ssl.fail',"?r-source=".PaymentGatewayConst::APP);
            $post_data['cancel_url'] = route('api.add.money.ssl.cancel',"?r-source=".PaymentGatewayConst::APP);
        }
        $post_data['store_id'] = $credentials->store_id??"";
        $post_data['store_passwd'] = $credentials->store_password??"";
        $post_data['total_amount'] =$amount;
        $post_data['currency'] = $currency;
        $post_data['tran_id'] =  $reference;

        # $post_data['multi_card_name'] = "mastercard,visacard,amexcard";  # DISABLE TO DISPLAY ALL AVAILABLE

        # EMI INFO
        $post_data['emi_option'] = "1";
        $post_data['emi_max_inst_option'] = "9";
        $post_data['emi_selected_inst'] = "9";

        # CUSTOMER INFORMATION
        $post_data['cus_name'] = $user->fullname??"Test Customer";
        $post_data['cus_email'] = $user->email??"test@test.com";
        $post_data['cus_add1'] = $user->address->country??"Dhaka";
        $post_data['cus_add2'] = $user->address->address??"Dhaka";
        $post_data['cus_city'] = $user->address->city??"Dhaka";
        $post_data['cus_state'] = $user->address->state??"Dhaka";
        $post_data['cus_postcode'] = $user->address->zip??"1000";
        $post_data['cus_country'] = $user->address->country??"Bangladesh";
        $post_data['cus_phone'] = $user->full_mobile??"01711111111";
        $post_data['cus_fax'] = "";



        # PRODUCT INFORMATION
        $post_data['product_name'] = "Add Money";
        $post_data['product_category'] = "Add Money";
        $post_data['product_profile'] = "Add Money";
        # SHIPMENT INFORMATION
        $post_data['shipping_method'] = "NO";

         $data = [
            'request_data'    => $post_data,
            'amount'          => $amount,
            'email'           => $user_email,
            'tx_ref'          => $reference,
            'currency'        =>  $currency,
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

        if( $credentials->mode == Str::lower(PaymentGatewayConst::ENV_SANDBOX)){
            $link_url =  $credentials->sandbox_url;
        }else{
            $link_url =  $credentials->live_url;
        }
        # REQUEST SEND TO SSLCOMMERZ
        $direct_api_url = $link_url."/gwprocess/v4/api.php";

        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $direct_api_url );
        curl_setopt($handle, CURLOPT_TIMEOUT, 30);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($handle, CURLOPT_POST, 1 );
        curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($post_data));
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, FALSE); # KEEP IT FALSE IF YOU RUN FROM LOCAL PC


        $content = curl_exec($handle );
        $result = json_decode( $content,true);
        if( $result['status']  != "SUCCESS"){
            throw new Exception($result['failedreason']);
        }

        $data['link'] = $result['GatewayPageURL'];
        $data['trx'] =  $reference;

        $this->sslJunkInsert($data);
        return $data;

    }

}
