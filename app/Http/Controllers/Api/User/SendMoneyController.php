<?php

namespace App\Http\Controllers\Api\User;

use App\Constants\NotificationConst;
use App\Constants\PaymentGatewayConst;
use App\Http\Controllers\Controller;
use App\Http\Helpers\Api\Helpers;
use App\Http\Helpers\NotificationHelper;
use App\Models\Admin\BasicSettings;
use App\Models\Admin\Currency;
use App\Models\Admin\TransactionSetting;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\UserQrCode;
use App\Models\UserWallet;
use App\Notifications\Admin\ActivityNotification;
use App\Notifications\User\SendMoney\ReceiverMail;
use App\Notifications\User\SendMoney\SenderMail;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Helpers\PushNotificationHelper;
use App\Providers\Admin\BasicSettingsProvider;

class SendMoneyController extends Controller
{
    protected  $trx_id;
    protected $basic_settings;

    public function __construct()
    {
        $this->trx_id = 'SM'.getTrxNum();
        $this->basic_settings = BasicSettingsProvider::get();
    }
    public function sendMoneyInfo(){
        $user = auth()->user();
        $sendMoneyCharge = TransactionSetting::where('slug','transfer')->where('status',1)->get()->map(function($data){
            return[
                'id' => $data->id,
                'slug' => $data->slug,
                'title' => $data->title,
                'fixed_charge' => getAmount($data->fixed_charge,2),
                'percent_charge' => getAmount($data->percent_charge,2),
                'min_limit' => getAmount($data->min_limit,2),
                'max_limit' => getAmount($data->max_limit,2),
                'monthly_limit' => getAmount($data->monthly_limit,2),
                'daily_limit' => getAmount($data->daily_limit,2),
            ];
        })->first();
        $transactions = Transaction::auth()->senMoney()->latest()->take(10)->get()->map(function($item){
            $statusInfo = [
                "success" =>      1,
                "pending" =>      2,
                "rejected" =>     3,
                ];
                if($item->attribute == payment_gateway_const()::SEND){
                    return[
                        'id' => @$item->id,
                        'type' =>$item->attribute,
                        'trx' => @$item->trx_id,
                        'transaction_type' => $item->type,
                        'transaction_heading' => "Send Money to @" . @$item->details->receiver->username." (".@$item->details->receiver->email.")",
                        'request_amount' => getAmount(@$item->request_amount,2).' '.get_default_currency_code() ,
                        'total_charge' => getAmount(@$item->charge->total_charge,2).' '.get_default_currency_code(),
                        'payable' => getAmount(@$item->payable,2).' '.get_default_currency_code(),
                        'recipient_received' => getAmount(@$item->details->recipient_amount,2).' '.get_default_currency_code(),
                        'current_balance' => getAmount(@$item->available_balance,2).' '.get_default_currency_code(),
                        'status' => @$item->stringStatus->value ,
                        'date_time' => @$item->created_at ,
                        'status_info' =>(object)@$statusInfo ,
                    ];
                }elseif($item->attribute == payment_gateway_const()::RECEIVED){
                    return[
                        'id' => @$item->id,
                        'type' =>$item->attribute,
                        'trx' => @$item->trx_id,
                        'transaction_type' => $item->type,
                        'transaction_heading' => "Received Money from @" .@$item->details->sender->username." (".@$item->details->sender->email.")",
                        'recipient_received' => getAmount(@$item->request_amount,2).' '.get_default_currency_code(),
                        'current_balance' => getAmount(@$item->available_balance,2).' '.get_default_currency_code(),
                        'status' => @$item->stringStatus->value ,
                        'date_time' => @$item->created_at ,
                        'status_info' =>(object)@$statusInfo ,
                    ];

                }

        });
        $userWallet = UserWallet::where('user_id',$user->id)->get()->map(function($data){
            return[
                'balance' => getAmount($data->balance,2),
                'currency' => get_default_currency_code(),
            ];
        })->first();
        $data =[
            'base_curr' => get_default_currency_code(),
            'base_curr_rate' => get_default_currency_rate(),
            'sendMoneyCharge'=> (object)$sendMoneyCharge,
            'userWallet'=>  (object)$userWallet,
            'transactions'   => $transactions,
        ];
        $message =  ['success'=>[__('Send Money Information')]];
        return Helpers::success($data,$message);
    }
    public function checkUser(Request $request){
        $validator = Validator::make(request()->all(), [
            'email'     => "required|email",
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }
        $column = "email";
        if(check_email($request->email)) $column = "email";
        $exist = User::where($column,$request->email)->active()->first();
        if( !$exist){
            $error = ['error'=>[__('User not found')]];
            return Helpers::error($error);
        }
        $user = auth()->user();
        if(@$exist && $user->email == @$exist->email){
             $error = ['error'=>[__("Can't send money to your own")]];
            return Helpers::error($error);
        }
        $data =[
            'exist_user'   => $exist,
        ];
        $message =  ['success'=>[__('Valid user for transaction.')]];
        return Helpers::success($data,$message);
    }
    public function qrScan(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            'qr_code'     => "required|string",
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }
        $qr_code = $request->qr_code;
        $qrCode = UserQrCode::where('qr_code',$qr_code)->first();
        if(!$qrCode){
            $error = ['error'=>[__('Not found')]];
            return Helpers::error($error);
        }
        $user = User::where('id',$qrCode->user_id)->active()->first();
        if(!$user){
            $error = ['error'=>[__('User not found')]];
            return Helpers::error($error);
        }
        if( $user->email == auth()->user()->email){
            $error = ['error'=>[__("Can't send money to your own")]];
            return Helpers::error($error);
        }
        $data =[
            'user_email'   => $user->email,
            ];
        $message =  ['success'=>[__('QR Scan Result.')]];
        return Helpers::success($data,$message);
    }

    public function confirmedSendMoney(Request $request){
        $validator = Validator::make(request()->all(), [
            'amount' => 'required|numeric|gt:0',
            'email' => 'required|email'
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }
        $basic_setting = BasicSettings::first();
        $user = auth()->user();
        if($basic_setting->kyc_verification){
            if( $user->kyc_verified == 0){
                $error = ['error'=>[__('Please submit kyc information!')]];
                return Helpers::error($error);
            }elseif($user->kyc_verified == 2){
                $error = ['error'=>[__('Please wait before admin approves your kyc information')]];
                return Helpers::error($error);
            }elseif($user->kyc_verified == 3){
                $error = ['error'=>[__('Admin rejected your kyc information, Please re-submit again')]];
                return Helpers::error($error);
            }
        }
        $amount = $request->amount;
        $user = auth()->user();
        $sendMoneyCharge = TransactionSetting::where('slug','transfer')->where('status',1)->first();
        $userWallet = UserWallet::where('user_id',$user->id)->first();
        if(!$userWallet){
            $error = ['error'=>[__('User wallet not found')]];
            return Helpers::error($error);
        }
        $baseCurrency = Currency::default();
        if(!$baseCurrency){
            $error = ['error'=>[__('Default currency not found')]];
            return Helpers::error($error);
        }
        $rate = $baseCurrency->rate;
        $email = $request->email;
        $receiver = User::where('email',$email)->active()->first();
        if(!$receiver){
            $error = ['error'=>[__('Receiver not exist')]];
            return Helpers::error($error);
        }
        if($receiver->email ==  $user->email){
            $error = ['error'=>[__("Can't send money to your own")]];
            return Helpers::error($error);
        }
        $receiverWallet = UserWallet::where('user_id',$receiver->id)->first();
        if(!$receiverWallet){
            $error = ['error'=>[__('Receiver wallet not found')]];
            return Helpers::error($error);
        }

        $minLimit =  $sendMoneyCharge->min_limit *  $rate;
        $maxLimit =  $sendMoneyCharge->max_limit *  $rate;
        if($amount < $minLimit || $amount > $maxLimit) {
            $error = ['error'=>[__("Please follow the transaction limit")]];
            return Helpers::error($error);
        }
        //charge calculations
        $fixedCharge = $sendMoneyCharge->fixed_charge *  $rate;
        $percent_charge = ($request->amount / 100) * $sendMoneyCharge->percent_charge;
        $total_charge = $fixedCharge + $percent_charge;
        $payable = $total_charge + $amount;
        $recipient = $amount;
        if($payable > $userWallet->balance ){
            $error = ['error'=>[__('Sorry, insufficient balance')]];
            return Helpers::error($error);
        }

        try{
            $trx_id = $this->trx_id;
            $notifyDataSender = [
                'trx_id'  => $trx_id,
                'title'  => __("Send Money to")." @" . @$receiver->username." (".@$receiver->email.")",
                'request_amount'  => getAmount($amount,4).' '.get_default_currency_code(),
                'payable'   =>  getAmount($payable,4).' ' .get_default_currency_code(),
                'charges'   => getAmount( $total_charge, 2).' ' .get_default_currency_code(),
                'received_amount'  => getAmount( $recipient, 2).' ' .get_default_currency_code(),
                'status'  => __("success"),
              ];

            $sender = $this->insertSender( $trx_id,$user,$userWallet,$amount,$recipient,$payable,$receiver);
            if($sender){
                 $this->insertSenderCharges( $fixedCharge,$percent_charge, $total_charge, $amount,$user,$sender,$receiver);
            }
            //sender notifications
            try{
                if( $basic_setting->email_notification == true){
                    $user->notify(new SenderMail($user,(object)$notifyDataSender));
                }

            }catch(Exception $e){}

             //Receiver notifications
             $notifyDataReceiver = [
                'trx_id'  => $trx_id,
                 'title'  => __("Received Money from")." @" .@$user->username." (".@$user->email.")",
                'received_amount'  => getAmount( $recipient, 2).' ' .get_default_currency_code(),
                'status'  => __("success"),
              ];

            $receiverTrans = $this->insertReceiver( $trx_id,$user,$userWallet,$amount,$recipient,$payable,$receiver,$receiverWallet);
            if($receiverTrans){
                 $this->insertReceiverCharges( $fixedCharge,$percent_charge, $total_charge, $amount,$user,$receiverTrans,$receiver);
            }
            //send notifications
            try{
                if( $basic_setting->email_notification == true){
                    $receiver->notify(new ReceiverMail($receiver,(object)$notifyDataReceiver));
                }
            }catch(Exception $e){}
             //admin notification
             $this->adminNotification($trx_id,$total_charge,$amount,$payable,$user,$receiver);
            $message =  ['success'=>[__('Send Money successful to').' '.$receiver->fullname]];
            return Helpers::onlysuccess($message);
        }catch(Exception $e) {
            $error = ['error'=>[__("Something went wrong! Please try again.")]];
            return Helpers::error($error);

        }
    }
    //admin notification
    public function adminNotification($trx_id,$total_charge,$amount,$payable,$user,$receiver){
        $notification_content = [
            //email notification
            'subject' =>__("Send Money"),
            'greeting' =>__("Send Money Information"),
            'email_content' =>__("web_trx_id")." : ".$trx_id."<br>".__("sender").": @".$user->email."<br>".__("Receiver").": @".$receiver->email."<br>".__("request Amount")." : ".get_amount($amount,get_default_currency_code())."<br>".__("Fees & Charges")." : ".get_amount($total_charge,get_default_currency_code())."<br>".__("Total Payable Amount")." : ".get_amount($payable,get_default_currency_code())."<br>".__("Recipient Received")." : ".get_amount($amount,get_default_currency_code())."<br>".__("Status")." : ".__("success"),

            //push notification
            'push_title' => __("Send Money")." ".__('Successful'),
            'push_content' => __('web_trx_id')." ".$trx_id." ".__("sender").": @".$user->email." ".__("Receiver").": @".$receiver->email." ".__("Sender Amount")." : ".get_amount($amount,get_default_currency_code())." ".__("Receiver Amount")." : ".get_amount($amount,get_default_currency_code()),

            //admin db notification
            'notification_type' =>  NotificationConst::TRANSFER_MONEY,
            'trx_id' =>  $trx_id,
            'admin_db_title' => "Send Money"." ".'Successful'." ".get_amount($amount,get_default_currency_code())." (".$trx_id.")",
            'admin_db_message' =>"Sender".": @".$user->email.","."Receiver".": @".$receiver->email.","."Sender Amount"." : ".get_amount($amount,get_default_currency_code()).","."Receiver Amount"." : ".get_amount($amount,get_default_currency_code())
        ];

        try{
            //notification
            (new NotificationHelper())->admin(['admin.send.money.index','admin.send.money.export.data'])
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

     //sender transaction
     public function insertSender($trx_id,$user,$userWallet,$amount,$recipient,$payable,$receiver) {
        $trx_id = $trx_id;
        $authWallet = $userWallet;
        $afterCharge = ($authWallet->balance - $payable);
        $details =[
            'recipient_amount' => $recipient,
            'receiver' => $receiver,
        ];
        DB::beginTransaction();
        try{
            $id = DB::table("transactions")->insertGetId([
                'user_id'                       => $user->id,
                'user_wallet_id'                => $authWallet->id,
                'payment_gateway_currency_id'   => null,
                'type'                          => PaymentGatewayConst::TYPETRANSFERMONEY,
                'trx_id'                        => $trx_id,
                'request_amount'                => $amount,
                'payable'                       => $payable,
                'available_balance'             => $afterCharge,
                'remark'                        => ucwords(remove_speacial_char(PaymentGatewayConst::TYPETRANSFERMONEY," ")) . " To " .$receiver->fullname,
                'details'                       => json_encode($details),
                'attribute'                      =>PaymentGatewayConst::SEND,
                'status'                        => true,
                'created_at'                    => now(),
            ]);
            $this->updateSenderWalletBalance($authWallet,$afterCharge);

            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            $error = ['error'=>[__("Something went wrong! Please try again.")]];
            return Helpers::error($error);
        }
        return $id;
    }
    public function updateSenderWalletBalance($authWallet,$afterCharge) {
        $authWallet->update([
            'balance'   => $afterCharge,
        ]);
    }
    public function insertSenderCharges($fixedCharge,$percent_charge, $total_charge, $amount,$user,$id,$receiver) {
        DB::beginTransaction();
        try{
            DB::table('transaction_charges')->insert([
                'transaction_id'    => $id,
                'percent_charge'    => $percent_charge,
                'fixed_charge'      =>$fixedCharge,
                'total_charge'      =>$total_charge,
                'created_at'        => now(),
            ]);
            DB::commit();

            //notification
            $notification_content = [
                'title'         => __("Send Money"),
                'message'       => __('Transfer Money to')." ".$receiver->fullname.' ' .$amount.' '.get_default_currency_code()." ".__('Successful'),
                'image'         => files_asset_path('profile-default'),
            ];

            UserNotification::create([
                'type'      => NotificationConst::TRANSFER_MONEY,
                'user_id'  => $user->id,
                'message'   => $notification_content,
            ]);
            //push notification
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
    //Receiver Transaction
    public function insertReceiver($trx_id,$user,$userWallet,$amount,$recipient,$payable,$receiver,$receiverWallet) {
        $trx_id = $trx_id;
        $receiverWallet = $receiverWallet;
        $recipient_amount = ($receiverWallet->balance + $recipient);
        $details =[
            'sender_amount' => $amount,
            'sender' => $user,
        ];
        DB::beginTransaction();
        try{
            $id = DB::table("transactions")->insertGetId([
                'user_id'                       => $receiver->id,
                'user_wallet_id'                => $receiverWallet->id,
                'payment_gateway_currency_id'   => null,
                'type'                          => PaymentGatewayConst::TYPETRANSFERMONEY,
                'trx_id'                        => $trx_id,
                'request_amount'                => $amount,
                'payable'                       => $payable,
                'available_balance'             => $recipient_amount,
                'remark'                        => ucwords(remove_speacial_char(PaymentGatewayConst::TYPETRANSFERMONEY," ")) . " From " .$user->fullname,
                'details'                       => json_encode($details),
                'attribute'                      =>PaymentGatewayConst::RECEIVED,
                'status'                        => true,
                'created_at'                    => now(),
            ]);
            $this->updateReceiverWalletBalance($receiverWallet,$recipient_amount);

            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            $error = ['error'=>[__("Something went wrong! Please try again.")]];
            return Helpers::error($error);
        }
        return $id;
    }
    public function updateReceiverWalletBalance($receiverWallet,$recipient_amount) {
        $receiverWallet->update([
            'balance'   => $recipient_amount,
        ]);
    }
    public function insertReceiverCharges($fixedCharge,$percent_charge, $total_charge, $amount,$user,$id,$receiver) {
        DB::beginTransaction();
        try{
            DB::table('transaction_charges')->insert([
                'transaction_id'    => $id,
                'percent_charge'    => $percent_charge,
                'fixed_charge'      =>$fixedCharge,
                'total_charge'      =>$total_charge,
                'created_at'        => now(),
            ]);
            DB::commit();

            //notification
            $notification_content = [
                'title'         => __("Send Money"),
                'message'       => __('Transfer Money from')." ".$user->fullname.' ' .$amount.' '.get_default_currency_code()." ".__('Successful'),
                'image'         => files_asset_path('profile-default'),
            ];

            UserNotification::create([
                'type'      => NotificationConst::TRANSFER_MONEY,
                'user_id'  => $receiver->id,
                'message'   => $notification_content,
            ]);
            //push notification
            if( $this->basic_settings->push_notification == true){
                try{
                    (new PushNotificationHelper())->prepareApi([$receiver->id],[
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

}
