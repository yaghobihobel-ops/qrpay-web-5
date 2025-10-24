<?php

namespace App\Providers;

use App\Constants\GlobalConst;
use App\Constants\LanguageConst;
use Exception;
use App\Models\User;
use App\Models\Admin\Currency;
use App\Models\Admin\Language;
use App\Models\Admin\Extension;
use App\Models\UserSupportTicket;
use App\Models\Admin\BasicSettings;
use App\Models\Admin\FrontendHeaderSection;
use App\Models\Admin\GatewayAPi;
use App\Models\Admin\ModuleSetting;
use App\Models\Admin\SetupSeo;
use App\Models\Admin\SystemMaintenance;
use App\Models\VirtualCardApi;
use App\Support\Localization\LocaleManager;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use App\Providers\Admin\CurrencyProvider;
use App\Providers\Admin\BasicSettingsProvider;
use App\Providers\Admin\ExtensionProvider;

class CustomServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->startingPoint();
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        try{

            $default_language = Language::where('status',GlobalConst::ACTIVE)->first();
            $default_language_code = $default_language->code ?? LanguageConst::NOT_REMOVABLE;
            $view_share = [];
            $view_share['basic_settings']               = BasicSettings::first();
            $view_share['default_currency']             = Currency::default();
            $view_share['__languages']                  = Language::get();
            $view_share['all_user_count']               = User::count();
            $view_share['email_verified_user_count']    = User::where('email_verified', 1)->count();
            $view_share['kyc_verified_user_count']      = User::where('kyc_verified', 1)->count();
            $view_share['default_currency']             = Currency::default();
            $view_share['__extensions']                 = Extension::get();
            $view_share['pending_ticket_count']         = UserSupportTicket::pending()->get()->count();
            $view_share['module']                       = ModuleSetting::get();
            $view_share['card_limit']                   = VirtualCardApi::first()->card_limit;
            $view_share['card_api']                     = VirtualCardApi::first();
            $view_share['default_language_code']        = $default_language_code;
            $localeManager                              = $this->app->make(LocaleManager::class);
            $view_share['supported_locale_catalog']     = $localeManager->all();
            $view_share['rtl_locales']                  = $localeManager->rtlLocales();
            $view_share['country_supported_locales']    = $localeManager->supportedLocalesMapByCountry();
            $view_share['country_default_locales']      = $localeManager->defaultLocalesByCountry();
            $view_share['seo_data']                     = SetupSeo::first();
            $view_share['payLink']                      = GatewayAPi::first();
            $view_share['system_maintenance']           = SystemMaintenance::first();
            $view_share['personal']                     = FrontendHeaderSection::personal()->get();
            $view_share['business']                     = FrontendHeaderSection::business()->get();
            $view_share['enter_price']                  = FrontendHeaderSection::enterPrice()->get();
            $view_share['company']                      = FrontendHeaderSection::company()->get();



            view()->share($view_share);

            $this->app->bind(BasicSettingsProvider::class, function () use ($view_share) {
                return new BasicSettingsProvider($view_share['basic_settings']);
            });
            $this->app->bind(CurrencyProvider::class, function () use ($view_share) {
                return new CurrencyProvider($view_share['default_currency']);
            });

            $this->app->bind(ExtensionProvider::class, function () use ($view_share) {
                return new ExtensionProvider($view_share['__extensions']);
            });

        }catch(Exception $e) {
            //
        }
    }

    public function startingPoint() {
        Config::set('starting-point.point','/project/install/welcome');
        Config::set('starting-point.status', false);

        if(empty(env('PRODUCT_KEY'))) {
            Config::set('starting-point.status', true);
        }
    }
}
