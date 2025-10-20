<?php

namespace App\Traits\PaymentGateway;
use Exception;
use Illuminate\Support\Str;
use App\Models\TemporaryData;
use Illuminate\Support\Carbon;
use App\Models\UserNotification;
use Illuminate\Support\Facades\DB;
use App\Constants\NotificationConst;
use Illuminate\Support\Facades\Auth;
use App\Constants\PaymentGatewayConst;
use App\Http\Helpers\PushNotificationHelper;
use App\Models\Admin\BasicSettings;
use App\Notifications\User\AddMoney\ApprovedMail;
use Illuminate\Support\Facades\Config;
use Jenssegers\Agent\Agent;
use KingFlamez\Rave\Facades\Rave as Flutterwave;
use App\Traits\TransactionAgent;
use App\Traits\PayLink\TransactionTrait;

trait FlutterwaveTrait
{
    use TransactionAgent,TransactionTrait;
    public function flutterwaveInit($output = null) {
        if(!$output) $output = $this->output;
        $credentials = $this->getFlutterCredentials($output);
        $this->flutterwaveSetSecreteKey($credentials);
        if($output['type'] === PaymentGatewayConst::TYPEADDMONEY){
            return  $this->setupFlutterwaveInitAddMoney($output);
         }else{
             return  $this->setupFlutterwaveInitPayLink($output);
         }
    }
    public function setupFlutterwaveInitAddMoney($output){
           //This generates a payment reference
        $reference = Flutterwave::generateReference();
        $amount = $output['amount']->total_amount ? number_format($output['amount']->total_amount,2,'.','') : 0;

        if(auth()->guard(get_auth_guard())->check()){
            $user = auth()->guard(get_auth_guard())->user();
            $user_email = $user->email;
            $user_phone = $user->full_mobile ?? '';
            $user_name = $user->firstname.' '.$user->lastname ?? '';
        }
        if(userGuard()['guard'] == 'web'){
           $return_url = route('user.add.money.flutterwave.callback');
        }elseif(userGuard()['guard'] == 'agent'){
            $return_url = route('agent.add.money.flutterwave.callback');
        }

        // Enter the details of the payment
        $data = [
            'payment_options' => 'card,banktransfer',
            'amount'          => $amount,
            'email'           => $user_email,
            'tx_ref'          => $reference,
            'currency'        => $output['currency']['currency_code']??"NGN",
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

        $payment = Flutterwave::initializePayment($data);
        if( $payment['status'] == "error"){
            throw new Exception($payment['message']);
        };

        $this->flutterWaveJunkInsert($data);

        if ($payment['status'] !== 'success') {
            return;
        }

        return redirect($payment['data']['link']);

    }
    public function setupFlutterwaveInitPayLink($output){
           //This generates a payment reference
        $reference = Flutterwave::generateReference();
        $amount = $output['charge_calculation']['requested_amount'] ? number_format($output['charge_calculation']['requested_amount'],2,'.','') : 0;
        $return_url = route('payment-link.gateway.payment.flutterwave.callback');

        // Enter the details of the payment
        $data = [
            'payment_options' => 'card,banktransfer',
            'amount'          => $amount,
            'email'           => $output['validated']['email'],
            'tx_ref'          => $reference,
            'currency'        => $output['charge_calculation']['sender_cur_code']??"USD",
            'redirect_url'    => $return_url,
            'customer'        => [
                'email' => $output['validated']['email'] ?? '',
                'name'  => $output['validated']['full_name'] ?? '',
            ],
            "customizations" => [
                "title"       => __("Pay Link"),
                "description" => dateFormat('d M Y', Carbon::now()),
            ]
        ];

        $payment = Flutterwave::initializePayment($data);
        if( $payment['status'] == "error"){
            throw new Exception($payment['message']);
        };

        $this->flutterWaveJunkInsertPayLink($data);

        if ($payment['status'] !== 'success') {
            return;
        }

        return redirect($payment['data']['link']);

    }
    public function flutterWaveJunkInsert($response) {
        $output = $this->output;
        $user = auth()->guard(get_auth_guard())->user();
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
            'type'          => PaymentGatewayConst::FLUTTER_WAVE,
            'identifier'    => $response['tx_ref'],
            'data'          => $data,
        ]);
    }
    public function flutterWaveJunkInsertPayLink($response){
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
            'type'          => PaymentGatewayConst::FLUTTER_WAVE,
            'identifier'    => $response['tx_ref'],
            'data'          => $data,
        ]);
    }
    // Get Flutter wave credentials
    public function getFlutterCredentials($output) {
        $gateway = $output['gateway'] ?? null;
        if(!$gateway) throw new Exception(__("Payment gateway not available"));

        $public_key_sample = ['api key','api_key','client id','primary key', 'public key'];
        $secret_key_sample = ['client_secret','client secret','secret','secret key','secret id'];
        $encryption_key_sample = ['encryption_key','encryption secret','secret hash', 'encryption id'];

        $public_key = '';
        $outer_break = false;

        foreach($public_key_sample as $item) {
            if($outer_break == true) {
                break;
            }
            $modify_item = $this->flutterwavePlainText($item);
            foreach($gateway->credentials ?? [] as $gatewayInput) {
                $label = $gatewayInput->label ?? "";
                $label = $this->flutterwavePlainText($label);
                if($label == $modify_item) {
                    $public_key = $gatewayInput->value ?? "";
                    $outer_break = true;
                    break;
                }
            }
        }

        $secret_key = '';
        $outer_break = false;
        foreach($secret_key_sample as $item) {
            if($outer_break == true) {
                break;
            }
            $modify_item = $this->flutterwavePlainText($item);
            foreach($gateway->credentials ?? [] as $gatewayInput) {
                $label = $gatewayInput->label ?? "";
                $label = $this->flutterwavePlainText($label);

                if($label == $modify_item) {
                    $secret_key = $gatewayInput->value ?? "";
                    $outer_break = true;
                    break;
                }
            }
        }

        $encryption_key = '';
        $outer_break = false;
        foreach($encryption_key_sample as $item) {
            if($outer_break == true) {
                break;
            }
            $modify_item = $this->flutterwavePlainText($item);
            foreach($gateway->credentials ?? [] as $gatewayInput) {
                $label = $gatewayInput->label ?? "";
                $label = $this->flutterwavePlainText($label);

                if($label == $modify_item) {
                    $encryption_key = $gatewayInput->value ?? "";
                    $outer_break = true;
                    break;
                }
            }
        }

        return (object) [
            'public_key'     => $public_key,
            'secret_key'     => $secret_key,
            'encryption_key' => $encryption_key,
        ];

    }
    public function flutterwavePlainText($string) {
        $string = Str::lower($string);
        return preg_replace("/[^A-Za-z0-9]/","",$string);
    }
    public function flutterwaveSetSecreteKey($credentials){
        Config::set('flutterwave.secretKey',$credentials->secret_key);
        Config::set('flutterwave.publicKey',$credentials->public_key);
        Config::set('flutterwave.secretHash',$credentials->encryption_key);
    }
    public function flutterwaveSuccess($output = null) {
        if(!$output) $output = $this->output;
        $token = $this->output['tempData']['identifier'] ?? "";
        if(empty($token)) throw new Exception(__("Transaction Failed. The record didn't save properly. Please try again"));
        if($output['type'] === PaymentGatewayConst::TYPEADDMONEY){
            if(userGuard()['type'] == "USER"){
                return $this->createTransactionFlutterwave($output);
            }else{
                return $this->createTransactionChildRecords($output,PaymentGatewayConst::STATUSSUCCESS);
            }
        }else{
            return $this->createTransactionPayLink($output,PaymentGatewayConst::STATUSSUCCESS);
        }
    }
    public function createTransactionFlutterwave($output) {
        $basic_setting = BasicSettings::first();
        $user = auth()->user();
        $trx_id = 'AM'.getTrxNum();
        $inserted_id = $this->insertRecordFlutterwave($output,$trx_id);
        $this->insertChargesFlutterwace($output,$inserted_id);
        $this->adminNotification($trx_id,$output,PaymentGatewayConst::STATUSSUCCESS);
        $this->insertDeviceFlutterWave($output,$inserted_id);
        $this->removeTempDataFlutterWave($output);

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
        }catch(Exception $e){

        }

    }
    public function updateWalletBalanceFlutterWave($output) {
        $update_amount = $output['wallet']->balance + $output['amount']->requested_amount;

        $output['wallet']->update([
            'balance'   => $update_amount,
        ]);
    }
    public function insertRecordFlutterwave($output,$trx_id) {
        $token = $this->output['tempData']['identifier'] ?? "";
        DB::beginTransaction();
        try{
            if(Auth::guard(get_auth_guard())->check()){
                $user_id = auth()->guard(get_auth_guard())->user()->id;
            }

                // Add money
                $id = DB::table("transactions")->insertGetId([
                    'user_id'                       => $user_id,
                    'user_wallet_id'                => $output['wallet']->id,
                    'payment_gateway_currency_id'   => $output['currency']->id,
                    'type'                          => $output['type'],
                    'trx_id'                        => $trx_id,
                    'request_amount'                => $output['amount']->requested_amount,
                    'payable'                       => $output['amount']->total_amount,
                    'available_balance'             => $output['wallet']->balance + $output['amount']->requested_amount,
                    'remark'                        => ucwords(remove_speacial_char($output['type']," ")) . " With " . $output['gateway']->name,
                    'details'                       => 'Flutter Wave Payment Successfull',
                    'status'                        => true,
                    'created_at'                    => now(),
                ]);
                $this->updateWalletBalanceFlutterWave($output);

            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception(__("Something went wrong! Please try again."));
        }
        return $id;
    }
    public function insertChargesFlutterwace($output,$id) {
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

        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception(__("Something went wrong! Please try again."));
        }
    }
    public function insertDeviceFlutterWave($output,$id) {
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
    public function removeTempDataFlutterWave($output) {
        TemporaryData::where("identifier",$output['tempData']['identifier'])->delete();
    }
    // ********* For API **********
    public function flutterwaveInitApi($output = null) {
        if(!$output) $output = $this->output;
        $credentials = $this->getFlutterCredentials($output);
        $this->flutterwaveSetSecreteKey($credentials);

        //This generates a payment reference
        $reference = Flutterwave::generateReference();

        $amount = $output['amount']->total_amount ? number_format($output['amount']->total_amount,2,'.','') : 0;

        if(authGuardApi()['guard'] == 'agent_api'){
            $user = authGuardApi()['user'];
            $user_email = $user->email;
            $user_phone = $user->full_mobile ?? '';
            $user_name = $user->firstname.' '.$user->lastname ?? '';
            $return_url = route('agent.api.flutterwave.callback', "r-source=".PaymentGatewayConst::APP);
        }elseif(auth()->guard(get_auth_guard())->check()){
            $user = auth()->guard(get_auth_guard())->user();
            $user_email = $user->email;
            $user_phone = $user->full_mobile ?? '';
            $user_name = $user->firstname.' '.$user->lastname ?? '';
            $return_url = route('api.flutterwave.callback', "r-source=".PaymentGatewayConst::APP);
        }


        // Enter the details of the payment
        $data = [
            'payment_options' => 'card,banktransfer',
            'amount'          => $amount,
            'email'           => $user_email,
            'tx_ref'          => $reference,
            'currency'        => $output['currency']['currency_code']??"NGN",
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

        $payment = Flutterwave::initializePayment($data);
        $data['link'] = $payment['data']['link'];
        $data['trx'] = $data['tx_ref'];

        $this->flutterWaveJunkInsert($data);

        if ($payment['status'] !== 'success') {
            // notify something went wrong
            throw new Exception($payment['message']);
        }

        return $data;

    }

}
