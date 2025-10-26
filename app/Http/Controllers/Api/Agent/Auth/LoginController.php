<?php

namespace App\Http\Controllers\Api\Agent\Auth;

use App\Constants\GlobalConst;
use App\Http\Controllers\Controller;
use App\Http\Helpers\Api\Helpers as ApiHelpers;
use App\Models\Admin\SetupKyc;
use App\Models\Agent;
use App\Models\AgentQrCode;
use App\Providers\Admin\BasicSettingsProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Traits\Agent\LoggedInUsers;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Traits\Agent\RegisteredUsers;
use App\Traits\ControlDynamicInputFields;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use App\Traits\AdminNotifications\AuthNotifications;
use App\Services\Security\DeviceFingerprintService;
use App\Traits\Security\LogsSecurityEvents;
use Illuminate\Support\Facades\RateLimiter;

class LoginController extends Controller
{
    use  AuthenticatesUsers, LoggedInUsers ,RegisteredUsers,ControlDynamicInputFields,AuthNotifications, LogsSecurityEvents;
    protected $basic_settings;

    public function __construct()
    {
        $this->basic_settings = BasicSettingsProvider::get();
    }
    public function login(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:50',
            'password' => 'required|min:6',
        ]);

        if($validator->fails()){
            $this->logSecurityWarning('api_agent_login_validation_failed', [
                'identifier' => (string) $request->input('email'),
                'errors' => $validator->errors()->all(),
                'context' => 'agent_api',
                'ip' => $request->ip(),
            ]);
            $error =  ['error'=>$validator->errors()->all()];
            return ApiHelpers::validation($error);
        }
        $user = Agent::where('email',$request->email)->first();
        $throttleKey = $this->loginThrottleKey($request);
        if(!$user){
            RateLimiter::hit($throttleKey);
            $attempts = RateLimiter::attempts($throttleKey);
            $this->logSecurityWarning('api_agent_login_failed', [
                'identifier' => $request->email,
                'reason' => 'agent_not_found',
                'attempts' => $attempts,
                'context' => 'agent_api',
                'ip' => $request->ip(),
            ]);
            $this->notifyLoginThresholdExceeded($request, $request->email, $attempts, [
                'context' => 'agent_api',
            ]);
            $error = ['error'=>[__("Agent doesn't exists.")]];
            return ApiHelpers::validation($error);
        }
        if (Hash::check($request->password, $user->password)) {
            if($user->status == 0){
                $this->logSecurityWarning('api_agent_login_blocked', [
                    'agent_id' => $user->id,
                    'identifier' => $request->email,
                    'reason' => 'account_suspended',
                    'context' => 'agent_api',
                    'ip' => $request->ip(),
                ]);
                $error = ['error'=>[__('Account Has been Suspended')]];
                return ApiHelpers::validation($error);
            }
            $user->two_factor_verified = false;
            $user->save();
            $this->refreshUserWallets($user);
            $fingerprint = app(DeviceFingerprintService::class)->register($request, $user);
            $this->createLoginLog($user, $fingerprint);
            $this->createQr($user);
            $token = $user->createToken('agent_token')->accessToken;
            RateLimiter::clear($throttleKey);
            $this->logSecurityInfo('api_agent_login_success', [
                'agent_id' => $user->id,
                'identifier' => $request->email,
                'token_created' => true,
                'context' => 'agent_api',
                'ip' => $request->ip(),
            ]);
            $data = ['token' => $token, 'agent' => $user, ];
            $message =  ['success'=>[__('Login Successful')]];
            return ApiHelpers::success($data,$message);

        } else {
            RateLimiter::hit($throttleKey);
            $attempts = RateLimiter::attempts($throttleKey);
            $this->logSecurityWarning('api_agent_login_failed', [
                'identifier' => $request->email,
                'reason' => 'invalid_password',
                'attempts' => $attempts,
                'context' => 'agent_api',
                'ip' => $request->ip(),
            ]);
            $this->notifyLoginThresholdExceeded($request, $request->email, $attempts, [
                'context' => 'agent_api',
            ]);
            $error = ['error'=>[__('Incorrect Password')]];
            return ApiHelpers::error($error);
        }

    }

    protected function loginThrottleKey(Request $request): string
    {
        return sprintf('api_agent_login|%s|%s', strtolower((string) $request->input('email')), $request->ip());
    }

    public function register(Request $request){
        $basic_settings = $this->basic_settings;
        $passowrd_rule = security_password_rules();
        if( $basic_settings->agent_agree_policy){
            $agree ='required';
        }else{
            $agree ='';
        }

        $validator = Validator::make($request->all(), [
            'firstname'     => 'required|string|max:60',
            'lastname'      => 'required|string|max:60',
            'store_name'    => 'required|string|max:100',
            'email'         => 'required|string|email|max:150|unique:agents,email',
            'password'      => $passowrd_rule,
            'country'       => 'required|string|max:150',
            'city'          => 'required|string|max:150',
            'phone_code'    => 'required|string|max:10',
            'phone'         => 'required|string|max:20',
            'zip_code'      => 'required|string|max:8',
            'agree'         =>  $agree,

        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return ApiHelpers::validation($error);
        }
        if($basic_settings->agent_kyc_verification == true){
            $user_kyc_fields = SetupKyc::agentKyc()->first()->fields ?? [];
            $validation_rules = $this->generateValidationRules($user_kyc_fields);
            $validated = Validator::make($request->all(), $validation_rules);

            if ($validated->fails()) {
                $message =  ['error' => $validated->errors()->all()];
                return ApiHelpers::error($message);
            }
            $validated = $validated->validate();
            $get_values = $this->registerPlaceValueWithFields($user_kyc_fields, $validated);
        }
        $data = $request->all();
        $mobile        = remove_speacial_char($data['phone']);
        $mobile_code   = remove_speacial_char($data['phone_code']);
        $complete_phone             =  $mobile_code . $mobile;

        $check_agent = Agent::where('mobile',$mobile)->orWhere('full_mobile',$complete_phone)->orWhere('email',$data['email'])->first();
        if($check_agent){
            $error = ['error'=>[__('Agent already exist')]];
            return ApiHelpers::validation($error);
        }
        $userName = make_username($data['firstname'],$data['lastname'],'agents');
        $check_user_name = Agent::where('username',$userName)->first();
        if($check_user_name){
            $userName = $userName.'-'.rand(123,456);
        }

        //Agent Create
        $user = new Agent();
        $user->firstname = isset($data['firstname']) ? $data['firstname'] : null;
        $user->lastname = isset($data['lastname']) ? $data['lastname'] : null;
        $user->store_name = isset($data['store_name']) ? $data['store_name'] : null;
        $user->email = strtolower(trim($data['email']));
        $user->mobile =  $mobile;
        $user->mobile_code =  $mobile_code;
        $user->full_mobile =    $complete_phone;
        $user->password = Hash::make($data['password']);
        $user->username = $userName;
        $user->address = [
            'address' => isset($data['address']) ? $data['address'] : '',
            'city' => isset($data['city']) ? $data['city'] : '',
            'zip' => isset($data['zip_code']) ? $data['zip_code'] : '',
            'country' =>isset($data['country']) ? $data['country'] : '',
            'state' => isset($data['state']) ? $data['state'] : '',
        ];
        $user->status = 1;
        $user->email_verified =  true;
        $user->sms_verified =  ($basic_settings->sms_verification == true) ? true : true;
        $user->kyc_verified =  ($basic_settings->agent_kyc_verification == true) ? false : true;
        $user->save();
        if( $user && $basic_settings->agent_kyc_verification == true){
            $create = [
                'agent_id'       => $user->id,
                'data'          => json_encode($get_values),
                'created_at'    => now(),
            ];

            DB::beginTransaction();
            try{
                DB::table('agent_kyc_data')->updateOrInsert(["agent_id" => $user->id],$create);
                $user->update([
                    'kyc_verified'  => GlobalConst::PENDING,
                ]);
                DB::commit();
            }catch(Exception $e) {
                DB::rollBack();
                $user->update([
                    'kyc_verified'  => GlobalConst::DEFAULT,
                ]);
                $error = ['error'=>[__('Something went wrong! Please try again.')]];
                return ApiHelpers::validation($error);
            }

           }
        $token = $user->createToken('agent_token')->accessToken;
        $this->createUserWallets($user);
        $this->registerNotificationToAdminApi($user,'agent_api',"AGENT");
        $this->createQr($user);

        $data = ['token' => $token, 'agent' => $user, ];
        $message =  ['success'=>[__('Registration Successful')]];
        return ApiHelpers::success($data,$message);

    }
    public function logout(){
        Auth::user()->token()->revoke();
        $message = ['success'=>[__('Logout Successfully!')]];
        return ApiHelpers::onlysuccess($message);

    }
    public function createQr($user){
		$user = $user;
	    $qrCode = $user->qrCode()->first();
        $in['agent_id'] = $user->id;;
        $in['qr_code'] =  $user->email;
	    if(!$qrCode){
            AgentQrCode::create($in);
	    }else{
            $qrCode->fill($in)->save();
        }
	    return $qrCode;
	}
    protected function guard()
    {
        return Auth::guard("agent_api");
    }

}
