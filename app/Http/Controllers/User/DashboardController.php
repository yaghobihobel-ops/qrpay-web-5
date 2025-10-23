<?php
namespace App\Http\Controllers\User;

use App\Constants\PaymentGatewayConst;
use App\Http\Controllers\Controller;
use App\Http\Helpers\PushNotificationHelper;
use App\Models\Admin\Currency;
use App\Models\Agent;
use App\Models\AgentQrCode;
use App\Models\GiftCard;
use App\Models\Merchants\Merchant;
use App\Models\Merchants\MerchantQrCode;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Cache\GlobalCacheService;
use Exception;
use App\Models\UserQrCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Traits\AdminNotifications\AuthNotifications;

class DashboardController extends Controller
{
    use AuthNotifications;
    public function index()
    {
        $page_title =__( "Dashboard");
        $baseCurrency = Currency::default();
        $reportingConnection = config('performance.database.reporting_connection', config('database.default'));
        $transactionQuery = function () use ($reportingConnection) {
            return Transaction::on($reportingConnection)->auth();
        };
        $transactions = $transactionQuery()->latest()->take(5)->get();
        $data['totalReceiveRemittance'] = $transactionQuery()->remitance()->where('attribute',"RECEIVED")->where('status',1)->sum('request_amount');
        $data['totalSendRemittance'] = $transactionQuery()->remitance()->where('attribute',"SEND")->where('status',1)->sum('request_amount');
        $data['cardAmount'] = userActiveCardData()['total_balance'];
        $data['billPay'] = amountOnBaseCurrency($transactionQuery()->billPay()->where('status',1)->get());
        $data['topUps'] = amountOnBaseCurrency($transactionQuery()->mobileTopup()->where('status',1)->get());
        $data['withdraw'] = $transactionQuery()->moneyOut()->where('status',1)->sum('request_amount');
        $data['total_transaction'] = $transactionQuery()->where('status', 1)->count();
        $data['total_gift_cards'] = GiftCard::auth()->count();
        $accountSummary = null;
        $userId = Auth::id();
        if ($userId) {
            $accountSummary = GlobalCacheService::rememberUserAccount($userId, function () use ($userId) {
                return User::with(['wallets.currency'])->find($userId);
            });
        }
        $data['cached_wallet_balance'] = 0;
        if ($accountSummary instanceof User) {
            $data['cached_wallet_balance'] = $accountSummary->wallets->sum('balance');
        }

        $start = strtotime(date('Y-m-01'));
        $end = strtotime(date('Y-m-31'));
        // Add Money
        $pending_data  = [];
        $success_data  = [];
        $canceled_data = [];
        $hold_data     = [];
        $month_day  = [];
        // Money Out
        $Money_out_pending_data  = [];
        $Money_out_success_data  = [];
        $Money_out_canceled_data = [];
        $Money_out_hold_data     = [];
        while ($start <= $end) {
            $start_date = date('Y-m-d', $start);


            // Monthly add money
            $pending = Transaction::auth()->where('type', PaymentGatewayConst::TYPEADDMONEY)
                                        ->whereDate('created_at',$start_date)
                                        ->where('status', 2)
                                        ->count();
            $success = Transaction::auth()->where('type', PaymentGatewayConst::TYPEADDMONEY)
                                        ->whereDate('created_at',$start_date)
                                        ->where('status', 1)
                                        ->count();
            $canceled = Transaction::auth()->where('type', PaymentGatewayConst::TYPEADDMONEY)
                                        ->whereDate('created_at',$start_date)
                                        ->where('status', 4)
                                        ->count();
            $hold = Transaction::auth()->where('type', PaymentGatewayConst::TYPEADDMONEY)
                                        ->whereDate('created_at',$start_date)
                                        ->where('status', 3)
                                        ->count();

            $pending_data[]  = $pending;
            $success_data[]  = $success;
            $canceled_data[] = $canceled;
            $hold_data[]     = $hold;



              // Monthley money Out
              $money_pending = Transaction::auth()->where('type', PaymentGatewayConst::TYPEMONEYOUT)
                                        ->whereDate('created_at',$start_date)
                                        ->where('status', 2)
                                        ->count();
            $money_success = Transaction::auth()->where('type', PaymentGatewayConst::TYPEMONEYOUT)
                                ->whereDate('created_at',$start_date)
                                ->where('status', 1)
                                ->count();
            $money_canceled = Transaction::auth()->where('type', PaymentGatewayConst::TYPEMONEYOUT)
                                ->whereDate('created_at',$start_date)
                                ->where('status', 4)
                                ->count();
            $money_hold = Transaction::auth()->where('type', PaymentGatewayConst::TYPEMONEYOUT)
                            ->whereDate('created_at',$start_date)
                            ->where('status', 3)
                            ->count();
            $Money_out_pending_data[]  = $money_pending;
            $Money_out_success_data[]  = $money_success;
            $Money_out_canceled_data[] = $money_canceled;
            $Money_out_hold_data[]     = $money_hold;

            $month_day[] = date('Y-m-d', $start);
            $start = strtotime('+1 day',$start);
        }
         // Chart one
         $chart_one_data = [
            'pending_data'  => $pending_data,
            'success_data'  => $success_data,
            'canceled_data' => $canceled_data,
            'hold_data'     => $hold_data,
        ];
         // Chart two
         $chart_two_data = [
            'pending_data'  => $Money_out_pending_data,
            'success_data'  => $Money_out_success_data,
            'canceled_data' => $Money_out_canceled_data,
            'hold_data'     => $Money_out_hold_data,
        ];
        $chartData =[
            'chart_one_data'   => $chart_one_data,
            'chart_two_data'   => $chart_two_data,
            'month_day'        => $month_day,
        ];

         //
        return view('user.dashboard',compact("page_title","baseCurrency",'transactions','data','chartData','accountSummary'));
    }

    public function logout(Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('user.login')->with(['success' => [__('Logout Successfully!')]]);
    }
    public function qrScan($qr_code)
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: PUT, GET, POST");
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
        $qrCode = UserQrCode::where('qr_code',$qr_code)->first();
        if(!$qrCode){
            return response()->json(['error'=>__("Invalid request")]);
        }
        $user = User::where('id',$qrCode->user_id)->active()->first();
        if(!$user){
            return response()->json(['error'=>__('Not found')]);
        }
        return $user->email;
    }
    public function agentQrScan($qr_code)
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: PUT, GET, POST");
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
        $qrCode = AgentQrCode::where('qr_code',$qr_code)->first();
        if(!$qrCode){
            return response()->json(['error'=>__("Invalid request")]);
        }
        $user = Agent::where('id',$qrCode->agent_id)->active()->first();
        if(!$user){
            return response()->json(['error'=>__('Invalid Agent')]);
        }
        return $user->email;
    }
    public function merchantQrScan($qr_code)
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: PUT, GET, POST");
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
        $qrCode = MerchantQrCode::where('qr_code',$qr_code)->first();
        if(!$qrCode){
            return response()->json(['error'=>__("Invalid request")]);
        }
        $user = Merchant::where('id',$qrCode->merchant_id)->active()->first();
        if(!$user){
            return response()->json(['error'=>__('Invalid merchant')]);
        }
        return $user->email;
    }
    public function deleteAccount(Request $request) {
        $validator = Validator::make($request->all(),[
            'target'        => 'required',
        ]);
        $user = auth()->user();
        //make unsubscribe
         try{
            (new PushNotificationHelper(['users' => [$user->id],'user_type' => 'user']))->unsubscribe();
        }catch(Exception $e) {}
        //admin notification
        $this->deleteUserNotificationToAdmin($user,"USER",'web');
        $user->status = false;
        $user->email_verified = false;
        $user->kyc_verified = false;
        $user->deleted_at = now();
        $user->save();
        try{
            Auth::logout();
            return redirect()->route('index')->with(['success' => [__('Your profile deleted successfully!')]]);
        }catch(Exception $e) {
            return back()->with(['error' => [__("Something went wrong! Please try again.")]]);
        }
    }
}
