<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class Localization
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
        if ($request->is('store-panel/*')) {
            if (session()->has('vendor_local')) {
                App::setLocale(session()->get('vendor_local'));
            }
        }
        else if ($request->is('delivery-company-panel/*')) {
            if (session()->has('partner_local')) {
                App::setLocale(session()->get('partner_local'));
            }
        }
        elseif($request->is('admin/*')){
            if (session()->has('local')) {
                App::setLocale(session()->get('local'));
            }
        }else{
            if (session()->has('landing_local')) {
                App::setLocale(session()->get('landing_local'));
            }
        }
        return $next($request);
    }
}
