<?php

namespace App\Http\Controllers\Agent;

use App\Constants\GlobalConst;
use App\Constants\NotificationConst;
use App\Constants\PaymentGatewayConst;
use App\Http\Controllers\Controller;
use App\Http\Helpers\NotificationHelper;
use App\Http\Helpers\PushNotificationHelper;
use App\Models\Admin\BasicSettings;
use App\Models\Admin\TransactionSetting;
use App\Models\Transaction;
use App\Notifications\User\SendMoney\ReceiverMail;
use App\Notifications\User\SendMoney\SenderMail;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Agent;
use App\Models\AgentNotification;
use App\Models\AgentWallet;
use App\Notifications\Admin\ActivityNotification;
use App\Providers\Admin\BasicSettingsProvider;
use Illuminate\Support\Facades\Validator;

class SendMoneyController extends Controller
{
    protected  $trx_id;
    protected $basic_settings;

    public function __construct()
    {
        $this->trx_id = 'SM'.getTrxNum();
        $this->basic_settings = BasicSettingsProvider::get();
    }
    public function index() {
        $page_title = __("Send Money");
        $sendMoneyCharge = TransactionSetting::where('slug','transfer')->where('status',1)->first();
        $transactions = Transaction::agentAuth()->senMoney()->latest()->take(10)->get();
        return view('agent.sections.send-money.index',compact("page_title",'sendMoneyCharge','transactions'));
    }
    public function checkUser(Request $request){
        $email = $request->email;
        $exist['data'] = Agent::where('email',$email)->active()->first();

        $user = auth()->user();
        if(@$exist['data'] && $user->email == @$exist['data']->email){
            return response()->json(['own'=>__("Can't send money to your own")]);
        }
        return response($exist);
    }
    public function confirmedSendMoney(Request $request){
        $validated = Validator::make($request->all(),[
            'amount' => 'required|numeric|gt:0',
            'email' => 'required|email'
        ])->validate();

        $basic_setting = BasicSettings::first();

        $sender_wallet = AgentWallet::auth()->active()->first();
        if(!$sender_wallet){
            return back()->with(['error' => [__('Agent wallet not found')]]);
        }
        if( $sender_wallet->agent->email == $validated['email']){
            return back()->with(['error' => [__("Can't transfer money to your own")]]);
        }
        $field_name = "username";
        if(check_email($validated['email'])) {
            $field_name = "email";
        }
        $receiver = Agent::where($field_name,$validated['email'])->active()->first();
        if(!$receiver){
            return back()->with(['error' => [__("Receiver doesn't exists or Receiver is temporary banned")]]);
        }
        $receiver_wallet = AgentWallet::where("agent_id",$receiver->id)->first();

        if(!$receiver_wallet){
            return back()->with(['error' => [__("Receiver wallet not found")]]);
        }

        $trx_charges =  TransactionSetting::where('slug','transfer')->where('status',1)->first();
        $charges = $this->transferCharges($validated['amount'],$trx_charges,$sender_wallet,$receiver->wallet->currency);

        $sender_currency_rate = $sender_wallet->currency->rate;
        $min_amount = $trx_charges->min_limit * $sender_currency_rate;
        $max_amount = $trx_charges->max_limit * $sender_currency_rate;

        if($charges['sender_amount'] < $min_amount || $charges['sender_amount'] > $max_amount) {
            return back()->with(['error' => [__("Please follow the transaction limit")]]);
        }
        if($charges['payable'] > $sender_wallet->balance) {
            return back()->with(['error' => [__("Sorry, insufficient balance")]]);
         }
        try{
            $trx_id = $this->trx_id;
            $sender = $this->insertSender($trx_id, $sender_wallet,$charges,$receiver_wallet);
            if($sender){
                 $this->insertSenderCharges($sender,$charges,$sender_wallet,$receiver_wallet);
                 try{
                    if( $basic_setting->agent_email_notification == true){
                        $notifyDataSender = [
                            'trx_id'  => $trx_id,
                            'title'  => __("Send Money to")." @" . @$receiver_wallet->agent->username." (".@$receiver_wallet->agent->email.")",
                            'request_amount'  => getAmount($charges['sender_amount'],4).' '.$charges['sender_currency'],
                            'payable'   =>  getAmount($charges['payable'],4).' ' .$charges['sender_currency'],
                            'charges'   => getAmount( $charges['total_charge'], 2).' ' .$charges['sender_currency'],
                            'received_amount'  => getAmount($charges['receiver_amount'], 2).' ' .$charges['receiver_currency'],
                            'status'  => __("success"),
                        ];
                        //sender notifications
                        $sender_wallet->agent->notify(new SenderMail($sender_wallet->agent,(object)$notifyDataSender));
                    }
                 }catch(Exception $e){
                    //Error Handler
                 }
            }

            $receiverTrans = $this->insertReceiver($trx_id, $sender_wallet,$charges,$receiver_wallet);
            if($receiverTrans){
                 $this->insertReceiverCharges($receiverTrans,$charges,$sender_wallet,$receiver_wallet);
                 try{
                     //Receiver notifications
                    if( $basic_setting->agent_email_notification == true){
                        $notifyDataReceiver = [
                            'trx_id'  => $trx_id,
                            'title'  => __("Received Money from")." @" .@$sender_wallet->agent->username." (".@$sender_wallet->agent->email.")",
                            'received_amount'  => getAmount($charges['receiver_amount'], 2).' ' .$charges['receiver_currency'],
                            'status'  => __("success"),
                        ];
                        //send notifications
                        $receiver->notify(new ReceiverMail($receiver,(object)$notifyDataReceiver));
                    }
                 }catch(Exception $e){
                    //Error Handler
                 }

            }
             //admin notification
             $this->adminNotification($trx_id,$charges,$sender_wallet->agent,$receiver);
            return back()->with(['success' => [__("Send Money successful")]]);
        }catch(Exception $e) {
          return back()->with(['error' => [__("Something went wrong! Please try again.")]]);
        }
    }
    //admin notification
    public function adminNotification($trx_id,$charges,$sender,$receiver){
        $notification_content = [
            //email notification
            'subject' =>__("Send Money")." (".authGuardApi()['type'].")",
            'greeting' =>__("Send Money Information"),
            'email_content' =>__("web_trx_id")." : ".$trx_id."<br>".__("sender").": @".$sender->email."<br>".__("Receiver").": @".$receiver->email."<br>".__("request Amount")." : ".get_amount($charges['sender_amount'],get_default_currency_code())."<br>".__("Fees & Charges")." : ".get_amount($charges['total_charge'],get_default_currency_code())."<br>".__("Total Payable Amount")." : ".get_amount($charges['payable'],get_default_currency_code())."<br>".__("Recipient Received")." : ".get_amount($charges['sender_amount'],get_default_currency_code())."<br>".__("Status")." : ".__("success"),

            //push notification
            'push_title' => __("Send Money")." ".__('Successful')." (".authGuardApi()['type'].")",
            'push_content' => __('web_trx_id')." ".$trx_id." ".__("sender").": @".$sender->email." ".__("Receiver").": @".$receiver->email." ".__("Sender Amount")." : ".get_amount($charges['sender_amount'],get_default_currency_code())." ".__("Receiver Amount")." : ".get_amount($charges['sender_amount'],get_default_currency_code()),

            //admin db notification
            'notification_type' =>  NotificationConst::TRANSFER_MONEY,
            'trx_id' =>  $trx_id,
            'admin_db_title' => "Send Money"." (".$trx_id.")"." (".authGuardApi()['type'].")",
            'admin_db_message' =>"Sender".": @".$sender->email.","."Receiver".": @".$receiver->email.","."Sender Amount"." : ".get_amount($charges['sender_amount'],get_default_currency_code()).","."Receiver Amount"." : ".get_amount($charges['sender_amount'],get_default_currency_code())
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
    public function insertSender($trx_id,$sender_wallet,$charges,$receiver_wallet) {
        $trx_id = $trx_id;
        $authWallet = $sender_wallet;
        $afterCharge = ($authWallet->balance - $charges['payable']) + $charges['agent_total_commission'];

        DB::beginTransaction();
        try{
            $id = DB::table("transactions")->insertGetId([
                'agent_id'                      => $sender_wallet->agent->id,
                'agent_wallet_id'               => $sender_wallet->id,
                'payment_gateway_currency_id'   => null,
                'type'                          => PaymentGatewayConst::TYPETRANSFERMONEY,
                'trx_id'                        => $trx_id,
                'request_amount'                => $charges['sender_amount'],
                'payable'                       => $charges['payable'],
                'available_balance'             => $afterCharge,
                'remark'                        => ucwords(remove_speacial_char(PaymentGatewayConst::TYPETRANSFERMONEY," ")) . " To " .$receiver_wallet->agent->fullname,
                'details'                       => json_encode([
                                                        'receiver_username'=> $receiver_wallet->agent->username,
                                                        'receiver_email'=> $receiver_wallet->agent->email,
                                                        'sender_username'=> $sender_wallet->agent->username,
                                                        'sender_email'=> $sender_wallet->agent->email,
                                                        'charges' => $charges
                                                    ]),
                'attribute'                      =>PaymentGatewayConst::SEND,
                'status'                        => GlobalConst::SUCCESS,
                'created_at'                    => now(),
            ]);
            $this->updateSenderWalletBalance($authWallet,$afterCharge);
            $this->agentProfitInsert($id,$authWallet,$charges);

            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
          return back()->with(['error' => [__("Something went wrong! Please try again.")]]);
        }
        return $id;
    }
    public function agentProfitInsert($id,$authWallet,$charges) {
        DB::beginTransaction();
        try{
            DB::table('agent_profits')->insert([
                'agent_id'          => $authWallet->agent->id,
                'transaction_id'    => $id,
                'percent_charge'    => $charges['agent_percent_commission'],
                'fixed_charge'      => $charges['agent_fixed_commission'],
                'total_charge'      => $charges['agent_total_commission'],
                'created_at'        => now(),
            ]);
            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
          return back()->with(['error' => [__("Something went wrong! Please try again.")]]);
        }
    }
    public function updateSenderWalletBalance($authWallet,$afterCharge) {
        $authWallet->update([
            'balance'   => $afterCharge,
        ]);
    }
    public function insertSenderCharges($id,$charges,$sender_wallet,$receiver_wallet) {
        DB::beginTransaction();
        try{
            DB::table('transaction_charges')->insert([
                'transaction_id'    => $id,
                'percent_charge'    => $charges['percent_charge'],
                'fixed_charge'      => $charges['fixed_charge'],
                'total_charge'      => $charges['total_charge'],
                'created_at'        => now(),
            ]);
            DB::commit();

            //store notification
            $notification_content = [
                'title'         =>__("Transfer Money"),
                'message'       => "Transfer Money to  ".$receiver_wallet->agent->fullname.' ' .$charges['sender_amount'].' '.$charges['sender_currency']." Successful",
                'image'         =>  get_image($sender_wallet->agent->image,'agent-profile'),
            ];
            AgentNotification::create([
                'type'      => NotificationConst::TRANSFER_MONEY,
                'agent_id'  => $sender_wallet->agent->id,
                'message'   => $notification_content,
            ]);
            //Push Notifications
            if( $this->basic_settings->agent_push_notification == true){
                try{
                    (new PushNotificationHelper())->prepare([$sender_wallet->agent->id],[
                        'title' => $notification_content['title'],
                        'desc'  => $notification_content['message'],
                        'user_type' => 'agent',
                    ])->send();
                }catch(Exception $e) {}
            }

        }catch(Exception $e) {
            DB::rollBack();
          return back()->with(['error' => [__("Something went wrong! Please try again.")]]);
        }
    }
    //Receiver Transaction
    public function insertReceiver($trx_id,$sender_wallet,$charges,$receiver_wallet) {
        $trx_id = $trx_id;
        $receiverWallet = $receiver_wallet;
        $recipient_amount = ($receiverWallet->balance + $charges['receiver_amount']);

        DB::beginTransaction();
        try{
            $id = DB::table("transactions")->insertGetId([
                'agent_id'                       => $receiver_wallet->agent->id,
                'agent_wallet_id'                => $receiver_wallet->id,
                'payment_gateway_currency_id'   => null,
                'type'                          => PaymentGatewayConst::TYPETRANSFERMONEY,
                'trx_id'                        => $trx_id,
                'request_amount'                => $charges['receiver_amount'],
                'payable'                       => $charges['receiver_amount'],
                'available_balance'             => $receiver_wallet->balance + $charges['receiver_amount'],
                'remark'                        => ucwords(remove_speacial_char(PaymentGatewayConst::TYPETRANSFERMONEY," ")) . " From " .$sender_wallet->agent->fullname,
                'details'                       => json_encode([
                                                            'receiver_username'=> $receiver_wallet->agent->username,
                                                            'receiver_email'=> $receiver_wallet->agent->email,
                                                            'sender_username'=> $sender_wallet->agent->username,
                                                            'sender_email'=> $sender_wallet->agent->email,
                                                            'charges' => $charges
                                                        ]),
                'attribute'                     =>PaymentGatewayConst::RECEIVED,
                'status'                        => GlobalConst::SUCCESS,
                'created_at'                    => now(),
            ]);
            $this->updateReceiverWalletBalance($receiverWallet,$recipient_amount);

            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
          return back()->with(['error' => [__("Something went wrong! Please try again.")]]);
        }
        return $id;
    }
    public function updateReceiverWalletBalance($receiverWallet,$recipient_amount) {
        $receiverWallet->update([
            'balance'   => $recipient_amount,
        ]);
    }
    public function insertReceiverCharges($id,$charges,$sender_wallet,$receiver_wallet) {
        DB::beginTransaction();
        try{
            DB::table('transaction_charges')->insert([
                'transaction_id'    => $id,
                'percent_charge'    => 0,
                'fixed_charge'      => 0,
                'total_charge'      => 0,
                'created_at'        => now(),
            ]);
            DB::commit();

            //store notification
            $notification_content = [
                'title'         =>__("Transfer Money"),
                'message'       => "Transfer Money from  ".$sender_wallet->agent->fullname.' ' .$charges['receiver_amount'].' '.$charges['receiver_currency']." Successful",
                'image'         => get_image($receiver_wallet->agent->image,'agent-profile'),
            ];
            AgentNotification::create([
                'type'      => NotificationConst::TRANSFER_MONEY,
                'agent_id'  => $receiver_wallet->agent->id,
                'message'   => $notification_content,
            ]);
            DB::commit();
            //Push Notifications
            if( $this->basic_settings->agent_push_notification == true){
                try{
                    (new PushNotificationHelper())->prepare([$receiver_wallet->agent->id],[
                        'title' => $notification_content['title'],
                        'desc'  => $notification_content['message'],
                        'user_type' => 'agent',
                    ])->send();
                }catch(Exception $e) {}
            }

        }catch(Exception $e) {
            DB::rollBack();
          return back()->with(['error' => [__("Something went wrong! Please try again.")]]);
        }
    }
    public function transferCharges($sender_amount,$charges,$sender_wallet,$receiver_currency) {
        $exchange_rate = $receiver_currency->rate / $sender_wallet->currency->rate;

        $data['exchange_rate']                      = $exchange_rate;
        $data['sender_amount']                      = $sender_amount;
        $data['sender_currency']                    = $sender_wallet->currency->code;
        $data['receiver_amount']                    = $sender_amount * $exchange_rate;
        $data['receiver_currency']                  = $receiver_currency->code;
        $data['percent_charge']                     = ($sender_amount / 100) * $charges->percent_charge ?? 0;
        $data['fixed_charge']                       = $sender_wallet->currency->rate * $charges->fixed_charge ?? 0;
        $data['total_charge']                       = $data['percent_charge'] + $data['fixed_charge'];
        $data['sender_wallet_balance']              = $sender_wallet->balance;
        $data['payable']                            = $sender_amount + $data['total_charge'];
        $data['agent_percent_commission']           = ($sender_amount / 100) * $charges->agent_percent_commissions ?? 0;
        $data['agent_fixed_commission']             = $sender_wallet->currency->rate * $charges->agent_fixed_commissions ?? 0;
        $data['agent_total_commission']             = $data['agent_percent_commission'] + $data['agent_fixed_commission'];
        return $data;
    }
}
