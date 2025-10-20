<?php

namespace App\Http\Middleware\Merchant;

use App\Http\Helpers\Api\Helpers;
use Closure;
use Illuminate\Http\Request;

class GoogleTwoFactorApi
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        if($user->two_factor_status && $user->two_factor_verified == false) {
            $error = ['errors'=>[__('2fa verification is required')]];
                return Helpers::error($error);
        }
        return $next($request);
    }
}
