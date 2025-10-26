<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Constants\ExtensionConst;
use App\Constants\NotificationConst;
use App\Http\Controllers\Controller;
use App\Http\Helpers\PushNotificationHelper;
use App\Models\Admin\AdminLoginLogs;
use App\Models\Admin\AdminNotification;
use App\Providers\Admin\ExtensionProvider;
use App\Services\Security\DeviceFingerprintService;
use App\Services\Security\SessionBindingService;
use App\Models\DeviceFingerprint;
use App\Traits\Security\LogsSecurityEvents;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Jenssegers\Agent\Agent;

class LoginController extends Controller
{
    use AuthenticatesUsers;
    use LogsSecurityEvents;

    /**
     * Display The Amdin Login From Page
     *
     * @return view
     */
    public function showLoginForm() {
        return view('admin.auth.login');
    }



    /**
     * Validate the user login request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateLogin(Request $request)
    {
        $extension = ExtensionProvider::get()->where('slug', ExtensionConst::GOOGLE_RECAPTCHA_SLUG)->first();
        $captcha_rules = "nullable";
        if($extension && $extension->status == true) {
            $captcha_rules = 'required|string|g_recaptcha_verify';
        }
        $request->validate([
            'email'                => 'required|string',
            'password'             => 'required|string',
            'g-recaptcha-response'  => $captcha_rules
        ]);
    }

    /**
     * Get The Authenticated User Guard
     * @return instance
     */
    protected function guard()
    {
        return Auth::guard('admin');
    }


    /**
     * The user has been authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
    {
        $fingerprint = app(DeviceFingerprintService::class)->register($request, $user);
        app(SessionBindingService::class)->bind($request, $user, $fingerprint);
        $user->update([
            'two_factor_verified'   => false,
        ]);
        $this->createLoginLog($user, $fingerprint);
        $this->updateInfo($user);
        return redirect()->intended(route('admin.dashboard'));
    }


    protected function createLoginLog($admin, ?DeviceFingerprint $fingerprint = null) {

        $client_ip = request()->ip() ?? false;
        $location = geoip()->getLocation($client_ip);

        $agent = new Agent();

        // $mac = exec('getmac');
        // $mac = explode(" ",$mac);
        // $mac = array_shift($mac);
        $mac = "";

        $data = [
            'admin_id'      => $admin->id,
            'ip'            => $client_ip,
            'mac'           => $mac,
            'city'          => $location['city'] ?? "",
            'country'       => $location['country'] ?? "",
            'longitude'     => $location['lon'] ?? "",
            'latitude'      => $location['lat'] ?? "",
            'timezone'      => $location['timezone'] ?? "",
            'browser'       => $agent->browser() ?? "",
            'os'            => $agent->platform() ?? "",
            'device_fingerprint_id' => $fingerprint?->id,
        ];

        try{
            AdminLoginLogs::create($data);
            $this->logSecurityInfo('admin_login_success', [
                'admin_id' => $admin->id,
                'fingerprint_id' => $fingerprint?->id,
                'ip' => $client_ip,
                'city' => $data['city'],
                'country' => $data['country'],
                'browser' => $data['browser'],
                'os' => $data['os'],
                'context' => 'admin_web',
            ]);
            $notification_message = [
                'title'   => $admin->fullname . "(" . $admin->username . ")" . " logged in.",
                'time'      => Carbon::now()->diffForHumans(),
                'image'     => get_image($admin->image,'admin-profile'),
            ];
            AdminNotification::create([
                'type'      => NotificationConst::SIDE_NAV,
                'admin_id'  => $admin->id,
                'message'   => $notification_message,
            ]);
            // event(new NotificationEvent($notification_message));
            try{
                (new PushNotificationHelper())->prepare([$admin->id],[
                    'title' => $admin->fullname . "(" . $admin->username . ")" . " logged in.",
                    'desc'  => "",
                    'user_type' => 'admin',
                ])->send();
            }catch(Exception $e) {}
        }catch(Exception $e) {
            $this->logSecurityError('admin_login_log_failed', [
                'admin_id' => $admin->id,
                'ip' => $client_ip,
                'message' => $e->getMessage(),
            ]);
        }
    }


    /**
     * Get the failed login response instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        $identifier = (string) $request->input('email');
        $attempts = method_exists($this, 'limiter') ? $this->limiter()->attempts($this->throttleKey($request)) : 0;

        $this->logSecurityWarning('admin_login_failed', [
            'identifier' => $identifier,
            'attempts' => $attempts,
            'ip' => $request->ip(),
            'context' => 'admin_web',
        ]);

        $this->notifyLoginThresholdExceeded($request, $identifier, $attempts, [
            'context' => 'admin_web',
        ]);

        throw ValidationException::withMessages([
            'credential' => [trans('auth.failed')],
        ]);
    }


    protected function updateInfo($admin) {
        try{
            $admin->update([
                'last_logged_in'    => now(),
                'login_status'      => true,
            ]);
        }catch(Exception $e) {
            // handle error
        }
    }


    /**
     * Get the needed authorization credentials from the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function credentials(Request $request)
    {
        $request->merge(['status' => true]);
        return $request->only($this->username(), 'password','status');
    }
}
