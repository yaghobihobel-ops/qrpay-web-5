<?php

namespace App\Http\Middleware;

use App\Models\Admin\Language;
use App\Support\Localization\LocaleManager;
use Closure;

class LanguageMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $code = $this->getCode();

        session()->put('lang', $code);

        if (! session()->has('local')) {
            session()->put('local', $code);
        }

        app()->setLocale(session('local', $code));
        return $next($request);
    }

    public function getCode()
    {
        if (session()->has('lang')) {
            return session('lang');
        }
        $language = Language::where('status', 1)->first();

        if ($language) {
            return $language->code;
        }

        return app(LocaleManager::class)->default();
    }


}
