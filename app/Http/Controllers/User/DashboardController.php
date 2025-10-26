<?php
namespace App\Http\Controllers\User;

use App\Constants\PaymentGatewayConst;
use App\Http\Controllers\Controller;
use App\Http\Helpers\PushNotificationHelper;
use App\Models\Admin\Currency;
use App\Models\Admin\Language;
use App\Models\Agent;
use App\Models\AgentQrCode;
use App\Models\GiftCard;
use App\Models\Merchants\Merchant;
use App\Models\Merchants\MerchantQrCode;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserQrCode;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Traits\AdminNotifications\AuthNotifications;
use App\Services\Recommendations\RouteRecommendationEngine;

class DashboardController extends Controller
{
    use AuthNotifications, TracksQueryPerformance;
    public function index()
    {
        $page_title =__( "Dashboard");
        $user = auth()->user();
        $baseCurrency = Currency::default();
        $walletBalance = authWalletBalance();

        $successTransactionsQuery = Transaction::auth()->where('status', PaymentGatewayConst::STATUSSUCCESS);
        $data['totalReceiveRemittance'] = (clone $successTransactionsQuery)->remitance()->where('attribute', PaymentGatewayConst::RECEIVED)->sum('request_amount');
        $data['totalSendRemittance'] = (clone $successTransactionsQuery)->remitance()->where('attribute', PaymentGatewayConst::SEND)->sum('request_amount');
        $data['cardAmount'] = userActiveCardData()['total_balance'];
        $data['billPay'] = amountOnBaseCurrency(Transaction::auth()->billPay()->where('status', PaymentGatewayConst::STATUSSUCCESS)->get());
        $data['topUps'] = amountOnBaseCurrency(Transaction::auth()->mobileTopup()->where('status', PaymentGatewayConst::STATUSSUCCESS)->get());
        $data['withdraw'] = (clone $successTransactionsQuery)->moneyOut()->sum('request_amount');
        $data['total_transaction'] = $successTransactionsQuery->count();
        $data['total_gift_cards'] = GiftCard::auth()->count();

        $recommendationEngine = app(RouteRecommendationEngine::class);
        $recommendation = $recommendationEngine->recommendFor(auth()->user());

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
        $dayIndex = array_flip($days);
        $statusKeys = [
            PaymentGatewayConst::STATUSPENDING => 'pending',
            PaymentGatewayConst::STATUSSUCCESS => 'success',
            PaymentGatewayConst::STATUSREJECTED => 'canceled',
            PaymentGatewayConst::STATUSHOLD => 'hold',
        ];

        $emptySeries = function () use ($days) {
            return [
                'pending' => array_fill(0, count($days), 0),
                'success' => array_fill(0, count($days), 0),
                'canceled' => array_fill(0, count($days), 0),
                'hold' => array_fill(0, count($days), 0),
            ];
        };

        $addMoneySeries = $emptySeries();
        $withdrawSeries = $emptySeries();
        $currencySeries = [];

        $monthlyTransactions = Transaction::auth()
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->with(['user_wallet.currency'])
            ->get();

        foreach ($monthlyTransactions as $transaction) {
            $index = $dayIndex[$transaction->created_at->format('Y-m-d')] ?? null;
            if ($index === null) {
                continue;
            }
            $statusKey = $statusKeys[$transaction->status] ?? null;
            if (!$statusKey) {
                continue;
            }
            $currencyCode = optional(optional($transaction->user_wallet)->currency)->code ?? $baseCurrency->code;

            if (!isset($currencySeries[$currencyCode])) {
                $currencySeries[$currencyCode] = [
                    'addMoney' => $emptySeries(),
                    'withdraw' => $emptySeries(),
                ];
            }

            if ($transaction->type === PaymentGatewayConst::TYPEADDMONEY) {
                $addMoneySeries[$statusKey][$index]++;
                $currencySeries[$currencyCode]['addMoney'][$statusKey][$index]++;
            } elseif ($transaction->type === PaymentGatewayConst::TYPEMONEYOUT) {
                $withdrawSeries[$statusKey][$index]++;
                $currencySeries[$currencyCode]['withdraw'][$statusKey][$index]++;
            }
        }

        $seriesLabels = [
            'pending' => __('Pending'),
            'success' => __('Success'),
            'canceled' => __('Rejected'),
            'hold' => __('On hold'),
        ];

        $formatSeries = function ($series) use ($seriesLabels) {
            return collect($seriesLabels)->map(function ($label, $key) use ($series) {
                return [
                    'name' => $label,
                    'data' => $series[$key] ?? [],
                ];
            })->values()->toArray();
        };

        $chartSeriesGroups = [
            'addMoney' => [
                'title' => __('Add money'),
                'subtitle' => __('Monthly add money performance'),
                'series' => $formatSeries($addMoneySeries),
                'byCurrency' => [],
            ],
            'withdraw' => [
                'title' => __('Withdrawals'),
                'subtitle' => __('Monthly withdrawal performance'),
                'series' => $formatSeries($withdrawSeries),
                'byCurrency' => [],
            ],
        ];

        foreach ($currencySeries as $code => $types) {
            $chartSeriesGroups['addMoney']['byCurrency'][$code] = $formatSeries($types['addMoney']);
            $chartSeriesGroups['withdraw']['byCurrency'][$code] = $formatSeries($types['withdraw']);
        }

        $userWallets = $user->wallets()->with('currency')->get();
        $transactionCounts = Transaction::auth()
            ->selectRaw('user_wallet_id, COUNT(*) as total')
            ->groupBy('user_wallet_id')
            ->pluck('total', 'user_wallet_id');

        $currencyFilters = $userWallets->map(function ($wallet) use ($transactionCounts) {
            return [
                'code' => optional($wallet->currency)->code,
                'balance' => $wallet->balance,
                'transactions' => $transactionCounts[$wallet->id] ?? 0,
            ];
        })->filter(fn ($item) => $item['code'])->values();

        $totalTransactions = Transaction::auth()->count();
        $successCount = Transaction::auth()->where('status', PaymentGatewayConst::STATUSSUCCESS)->count();
        $pendingCount = Transaction::auth()->where('status', PaymentGatewayConst::STATUSPENDING)->count();
        $failedCount = Transaction::auth()->whereIn('status', [PaymentGatewayConst::STATUSREJECTED, PaymentGatewayConst::STATUSFAILD])->count();
        $averageTransaction = Transaction::auth()->where('status', PaymentGatewayConst::STATUSSUCCESS)->avg('request_amount');

        $analytics = [
            'title' => __('Realtime performance'),
            'subtitle' => __('Key health metrics refresh live for your workspace'),
            'badge' => __('Live sync'),
            'items' => [
                [
                    'key' => 'success-rate',
                    'label' => __('Success rate'),
                    'value' => $totalTransactions > 0 ? number_format(($successCount / $totalTransactions) * 100, 1) . '%' : '0%',
                    'caption' => __('Completed vs total transactions'),
                ],
                [
                    'key' => 'pending',
                    'label' => __('Pending'),
                    'value' => $pendingCount,
                    'caption' => __('Awaiting your review'),
                ],
                [
                    'key' => 'failed',
                    'label' => __('Failed or rejected'),
                    'value' => $failedCount,
                    'caption' => __('Items requiring attention'),
                ],
                [
                    'key' => 'average',
                    'label' => __('Average ticket'),
                    'value' => $averageTransaction ? number_format($averageTransaction, 2) . ' ' . $baseCurrency->code : '0 ' . $baseCurrency->code,
                    'caption' => __('Across successful transactions'),
                ],
            ],
        ];

        $transactions = Transaction::auth()->latest()->take(10)->with(['user_wallet.currency'])->get();
        $statusStyles = [
            PaymentGatewayConst::STATUSSUCCESS => ['class' => 'bg-success/10 text-success', 'icon' => 'las la-check'],
            PaymentGatewayConst::STATUSPENDING => ['class' => 'bg-warning/10 text-warning', 'icon' => 'las la-clock'],
            PaymentGatewayConst::STATUSREJECTED => ['class' => 'bg-danger/10 text-danger', 'icon' => 'las la-times-circle'],
            PaymentGatewayConst::STATUSHOLD => ['class' => 'bg-warning/10 text-warning', 'icon' => 'las la-pause-circle'],
        ];

        $transactionItems = $transactions->map(function ($transaction) use ($statusStyles, $baseCurrency) {
            $currencyCode = optional(optional($transaction->user_wallet)->currency)->code ?? $baseCurrency->code;
            $status = $statusStyles[$transaction->status] ?? ['class' => 'bg-slate-200 text-slate-600', 'icon' => 'las la-info-circle'];
            $statusLabel = __($transaction->stringStatus->value ?? 'Unknown');
            return [
                'trx' => $transaction->trx_id,
                'title' => Str::of($transaction->type)->replace('-', ' ')->headline(),
                'amount' => number_format($transaction->request_amount, 2) . ' ' . $currencyCode,
                'currency' => $currencyCode,
                'badge' => [
                    'class' => $status['class'],
                    'icon' => $status['icon'],
                    'label' => ucfirst($statusLabel),
                ],
                'date' => $transaction->created_at ? $transaction->created_at->format('M d, Y H:i') : '',
            ];
        })->toArray();

        $languageOptions = Language::where('status', true)
            ->get(['name', 'code', 'dir'])
            ->map(fn ($language) => [
                'code' => $language->code,
                'label' => $language->name,
                'dir' => $language->dir,
            ])->toArray();

        $fallbackLanguages = [
            ['code' => 'zh', 'label' => '中文 (简体)', 'dir' => 'ltr'],
            ['code' => 'ru', 'label' => 'Русский', 'dir' => 'ltr'],
        ];

        foreach ($fallbackLanguages as $fallback) {
            $exists = collect($languageOptions)->contains(fn ($language) => $language['code'] === $fallback['code']);
            if (!$exists) {
                $languageOptions[] = $fallback;
            }
        }

        $notifications = array_merge([
            'email' => true,
            'sms' => false,
            'push' => true,
        ], $user->notification_preferences ?? []);

        $dashboardPayload = [
            'summary' => $summaryCards,
            'analytics' => $analytics,
            'chart' => [
                'title' => __('Financial pulse'),
                'subtitle' => __('Status mix across add money and withdraw flows'),
                'categories' => $days,
                'seriesGroups' => $chartSeriesGroups,
                'filters' => $currencyFilters,
                'labels' => [
                    'all' => __('All currencies'),
                ],
            ],
            'transactions' => $transactionItems,
            'latestTransactions' => [
                'title' => __('Latest transactions'),
                'subtitle' => __('Most recent movements across every module'),
                'cta' => [
                    'href' => route('user.transactions.index'),
                    'label' => __('View all'),
                ],
                'headers' => [
                    'reference' => __('Reference'),
                    'type' => __('Type'),
                    'amount' => __('Amount'),
                    'status' => __('Status'),
                    'date' => __('Date'),
                ],
                'empty' => __('No transactions found'),
            ],
            'preferences' => [
                'theme' => $user->preferred_theme ?? 'light',
                'language' => $user->preferred_language ?? app()->getLocale(),
                'notifications' => $notifications,
                'csrf' => csrf_token(),
            ],
            'personalization' => [
                'title' => __('Personalize your workspace'),
                'subtitle' => __('Switch theme, language, and delivery preferences instantly.'),
                'badge' => __('User specific'),
                'labels' => [
                    'light' => __('Light'),
                    'dark' => __('Dark'),
                    'system' => __('System'),
                    'email' => __('Email alerts'),
                    'emailCaption' => __('Transactional and marketing emails.'),
                    'sms' => __('SMS alerts'),
                    'smsCaption' => __('Critical balance and security texts.'),
                    'push' => __('Push notifications'),
                    'pushCaption' => __('In-app and browser updates.'),
                    'themeLabel' => __('Theme'),
                    'themeDescription' => __('Choose the appearance that matches your environment.'),
                    'languageLabel' => __('Language'),
                    'languageDescription' => __('Set the language for menus and system copy.'),
                    'notificationLabel' => __('Notifications'),
                    'notificationDescription' => __('Decide how you want to be notified.'),
                    'save' => __('Save changes'),
                    'saving' => __('Saving'),
                    'neverSaved' => __('Changes are applied instantly after saving.'),
                    'savedAt' => __('Last synced'),
                ],
            ],
            'languages' => $languageOptions,
            'endpoints' => [
                'preferences' => route('user.preferences.update'),
            ],
            'currency' => $baseCurrency->code,
            'locale' => app()->getLocale(),
        ];

         //
        return view('user.dashboard',compact("page_title","baseCurrency",'transactions','data','chartData','recommendation'));
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
