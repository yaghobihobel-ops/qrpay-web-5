<?php

namespace App\Traits\User;

use App\Models\Admin\Currency;
use App\Models\DeviceFingerprint;
use App\Models\UserLoginLog;
use App\Models\UserWallet;
use App\Traits\Security\LogsSecurityEvents;
use Exception;
use Jenssegers\Agent\Agent;

trait LoggedInUsers {

    use LogsSecurityEvents;

    protected function refreshUserWallets($user) {
        $user_wallets = $user->wallets->pluck("currency_id")->toArray();
        $currencies = Currency::active()->roleHasOne()->pluck("id")->toArray();
        $new_currencies = array_diff($currencies,$user_wallets);
        $new_wallets = [];
        foreach($new_currencies as $item) {
            $new_wallets[] = [
                'user_id'       => $user->id,
                'currency_id'   => $item,
                'balance'       => 0,
                'status'        => true,
                'created_at'    => now(),
            ];
        }

        try{
            UserWallet::insert($new_wallets);
        }catch(Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    protected function createLoginLog($user, ?DeviceFingerprint $fingerprint = null) {
        $client_ip = request()->ip() ?? false;
        $location = geoip()->getLocation($client_ip);

        $agent = new Agent();

        // $mac = exec('getmac');
        // $mac = explode(" ",$mac);
        // $mac = array_shift($mac);
        $mac = "";

        $data = [
            'user_id'       => $user->id,
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
            UserLoginLog::create($data);
            $this->logSecurityInfo('user_login_success', [
                'user_id' => $user->id,
                'fingerprint_id' => $fingerprint?->id,
                'ip' => $client_ip,
                'city' => $data['city'],
                'country' => $data['country'],
                'browser' => $data['browser'],
                'os' => $data['os'],
                'context' => 'user_web',
            ]);
        }catch(Exception $e) {
            $this->logSecurityError('user_login_log_failed', [
                'user_id' => $user->id,
                'ip' => $client_ip,
                'message' => $e->getMessage(),
            ]);
        }
    }
}