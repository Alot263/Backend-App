<?php

namespace App\Http\Middleware;

use App\Models\Partner;
use App\Models\PartnerEmployee;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class PartnerTokenIsValid
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
        $token=$request->bearerToken();
        if(strlen($token)<1)
        {
            return response()->json([
                'errors' => [
                    ['code' => 'auth-001', 'message' => 'Unauthorized.']
                ]
            ], 401);
        }
        if (!$request->hasHeader('partnerType')) {
            $errors = [];
            array_push($errors, ['code' => 'partner_type', 'message' => translate('messages.partner_type_required')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }
        $partner_type= $request->header('partnerType');
        if($partner_type == 'owner'){
            $partner = Partner::where('auth_token', $token)->first();
            if(!isset($partner))
            {
                return response()->json([
                    'errors' => [
                        ['code' => 'auth-001', 'message' => 'Unauthorized.']
                    ]
                ], 401);
            }
            $request['partner']=$partner;
            Config::set('module.current_module_data', $partner->delivery_companies[0]->module);
        }elseif($partner_type == 'employee'){
            $partner = PartnerEmployee::where('auth_token', $token)->first();
            if(!isset($partner))
            {
                return response()->json([
                    'errors' => [
                        ['code' => 'auth-001', 'message' => 'Unauthorized.']
                    ]
                ], 401);
            }
            $request['partner']=$partner->partner;
            $request['partner_employee']=$partner;
            Config::set('module.current_module_data', $partner->partner->delivery_companies[0]->module);
        }
        return $next($request);
    }
}
