<?php

namespace App\Http\Controllers\Api;

use App\Constants\GlobalConst;
use App\Http\Controllers\Controller;
use App\Http\Helpers\Api\Helpers;
use App\Models\Admin\AppOnboardScreens;
use App\Models\Admin\AppSettings;
use App\Models\Admin\BasicSettings;
use App\Services\Edge\EdgeCacheRepository;
use Exception;

class AppSettingsController extends Controller
{
    public function __construct(private EdgeCacheRepository $edgeCache)
    {
    }

    public function appSettings()
    {
        $data = $this->edgeCache->rememberSettings('app', function () {
            $appSettings = AppSettings::first();

            $appUrl = [
                'id' => optional($appSettings)->id,
                'android_url' => optional($appSettings)->android_url,
                'iso_url' => optional($appSettings)->iso_url,
                'created_at' => optional($appSettings)->created_at,
                'updated_at' => optional($appSettings)->updated_at,
            ];

            $splashScreenUser = [
                'id' => optional($appSettings)->id,
                'splash_screen_image' => optional($appSettings)->splash_screen_image,
                'version' => optional($appSettings)->version,
                'created_at' => optional($appSettings)->created_at,
                'updated_at' => optional($appSettings)->updated_at,
            ];

            $splashScreenAgent = [
                'id' => optional($appSettings)->id,
                'splash_screen_image' => optional($appSettings)->agent_splash_screen_image,
                'version' => optional($appSettings)->agent_version,
                'created_at' => optional($appSettings)->created_at,
                'updated_at' => optional($appSettings)->updated_at,
            ];

            $splashScreenMerchant = [
                'id' => optional($appSettings)->id,
                'splash_screen_image' => optional($appSettings)->merchant_splash_screen_image,
                'version' => optional($appSettings)->merchant_version,
                'created_at' => optional($appSettings)->created_at,
                'updated_at' => optional($appSettings)->updated_at,
            ];

            $onboardScreenUser = AppOnboardScreens::where('type', GlobalConst::USER)
                ->orderByDesc('id')
                ->where('status', 1)
                ->get()
                ->map(function ($data) {
                    return [
                        'id' => $data->id,
                        'title' => $data->title,
                        'sub_title' => $data->sub_title,
                        'image' => $data->image,
                        'status' => $data->status,
                        'created_at' => $data->created_at,
                        'updated_at' => $data->updated_at,
                    ];
                });

            $onboardScreenAgent = AppOnboardScreens::where('type', GlobalConst::AGENT)
                ->orderByDesc('id')
                ->where('status', 1)
                ->get()
                ->map(function ($data) {
                    return [
                        'id' => $data->id,
                        'title' => $data->title,
                        'sub_title' => $data->sub_title,
                        'image' => $data->image,
                        'status' => $data->status,
                        'created_at' => $data->created_at,
                        'updated_at' => $data->updated_at,
                    ];
                });

            $onboardScreenMerchant = AppOnboardScreens::where('type', GlobalConst::MERCHANT)
                ->orderByDesc('id')
                ->where('status', 1)
                ->get()
                ->map(function ($data) {
                    return [
                        'id' => $data->id,
                        'title' => $data->title,
                        'sub_title' => $data->sub_title,
                        'image' => $data->image,
                        'status' => $data->status,
                        'created_at' => $data->created_at,
                        'updated_at' => $data->updated_at,
                    ];
                });

            $basicSettings = BasicSettings::first();

            $basicSettingsUser = [
                'site_name' => optional($basicSettings)->site_name,
                'site_title' => optional($basicSettings)->site_title,
                'site_logo' => optional($basicSettings)->site_logo,
                'site_logo_dark' => optional($basicSettings)->site_logo_dark,
                'base_color' => optional($basicSettings)->base_color,
                'site_fav_dark' => optional($basicSettings)->site_fav_dark,
                'site_fav' => optional($basicSettings)->site_fav,
                'timezone' => optional($basicSettings)->timezone,
            ];

            $basicSettingsAgent = [
                'site_name' => optional($basicSettings)->agent_site_name,
                'site_title' => optional($basicSettings)->agent_site_title,
                'base_color' => optional($basicSettings)->agent_base_color,
                'site_logo' => optional($basicSettings)->agent_site_logo,
                'site_logo_dark' => optional($basicSettings)->agent_site_logo_dark,
                'site_fav_dark' => optional($basicSettings)->agent_site_fav_dark,
                'site_fav' => optional($basicSettings)->agent_site_fav,
                'timezone' => optional($basicSettings)->timezone,
            ];

            $basicSettingsMerchant = [
                'site_name' => optional($basicSettings)->merchant_site_name,
                'site_title' => optional($basicSettings)->merchant_site_title,
                'base_color' => optional($basicSettings)->merchant_base_color,
                'site_logo' => optional($basicSettings)->merchant_site_logo,
                'site_logo_dark' => optional($basicSettings)->merchant_site_logo_dark,
                'site_fav_dark' => optional($basicSettings)->merchant_site_fav_dark,
                'site_fav' => optional($basicSettings)->merchant_site_fav,
                'timezone' => optional($basicSettings)->timezone,
            ];

            $userAppSettings = [
                'splash_screen' => $splashScreenUser,
                'onboard_screen' => $onboardScreenUser,
                'basic_settings' => $basicSettingsUser,
            ];

            $agentAppSettings = [
                'splash_screen' => $splashScreenAgent,
                'onboard_screen' => $onboardScreenAgent,
                'basic_settings' => $basicSettingsAgent,
            ];

            $merchantAppSettings = [
                'splash_screen' => $splashScreenMerchant,
                'onboard_screen' => $onboardScreenMerchant,
                'basic_settings' => $basicSettingsMerchant,
            ];

            $appSettingsPayload = [
                'user' => (object) $userAppSettings,
                'agent' => (object) $agentAppSettings,
                'merchant' => (object) $merchantAppSettings,
            ];

            return [
                'base_url' => url('/'),
                'default_image' => files_asset_path_basename('default'),
                'screen_image_path' => files_asset_path_basename('app-images'),
                'logo_image_path' => files_asset_path_basename('image-assets'),
                'app_url' => (object) $appUrl,
                'app_settings' => (object) $appSettingsPayload,
            ];
        });

        $message =  ['success'=>[__("Data Fetch Successful")]];
        $response = Helpers::success($data,$message);

        return $this->edgeCache->withEdgeHeaders($response, EdgeCacheRepository::SCOPE_SETTINGS, 'app');
    }

    public function languages()
    {
        try{
            $api_languages = get_api_languages();
        }catch(Exception $e) {
            $error = ['error'=>[$e->getMessage()]];
            return Helpers::error($error);
        }
        $data =[
            'languages' => $api_languages,
        ];
        $message =  ['success'=>[__('Language Data Fetch Successfully!')]];
        $response = Helpers::success($data,$message);

        return $this->edgeCache->withEdgeHeaders($response, EdgeCacheRepository::SCOPE_SETTINGS, 'languages');
    }
}
