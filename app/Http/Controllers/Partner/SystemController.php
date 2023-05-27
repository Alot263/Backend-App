<?php

namespace App\Http\Controllers\Partner;

use App\Models\WithdrawRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\CentralLogics\Helpers;

class SystemController extends Controller
{
    public function dashboard()
    {
        $withdraw_req=WithdrawRequest::where('partner_id',Helpers::get_delivery_company_id())->latest()->paginate(10);
        return view('delivery-partner-views.dashboard', compact('withdraw_req'));
    }

    public function restaurant_data(): JsonResponse
    {
        $new_order = DB::table('orders')->where(['checked' => 0])->where('delivery_company_id', Helpers::get_delivery_company_id())->count();
        return response()->json([
            'success' => 1,
            'data' => ['new_order' => $new_order]
        ]);
    }
}
