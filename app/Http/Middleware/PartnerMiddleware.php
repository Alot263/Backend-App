<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PartnerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::guard('partner')->check()) {
            if(!auth('partner')->user()->status)
            {
                auth()->guard('partner')->logout();
                return redirect()->route('partner.auth.login');
            }
            return $next($request);
        }
        else if (Auth::guard('partner_employee')->check()) {
            if(Auth::guard('partner_employee')->user()->is_logged_in == 0)
            {
                auth()->guard('partner_employee')->logout();
                return redirect()->route('partner.auth.login');
            }
            if(!auth('partner_employee')->user()->delivery_company->status)
            {
                auth()->guard('partner_employee')->logout();
                return redirect()->route('partner.auth.login');
            }
            return $next($request);
        }
        return redirect()->route('partner.auth.login');
    }
}
