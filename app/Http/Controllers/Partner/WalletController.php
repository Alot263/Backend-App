<?php

namespace App\Http\Controllers\Partner;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\DeliveryCompanyWallet;
use App\Models\WithdrawRequest;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    public function index()
    {
        $withdraw_req = WithdrawRequest::with(['partner'])->where('partner_id', Helpers::get_partner_id())->latest()->paginate(config('default_pagination'));
        return view('delivery-partner-views.wallet.index', compact('withdraw_req'));
    }
    public function w_request(Request $request): RedirectResponse
    {
        $w = DeliveryCompanyWallet::where('partner_id', Helpers::get_partner_id())->first();
        if ($w->balance >= $request['amount'] && $request['amount'] > .01) {
            $data = [
                'partner_id' => Helpers::get_partner_id(),
                'amount' => $request['amount'],
                'transaction_note' => null,
                'approved' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ];
            DB::table('withdraw_requests')->insert($data);
            DeliveryCompanyWallet::where('partner_id', Helpers::get_partner_id())->increment('pending_withdraw', $request['amount']);
            Toastr::success('Withdraw request has been sent.');
            return redirect()->back();
        }

        Toastr::error('invalid request.!');
        return redirect()->back();
    }

    public function close_request($id): RedirectResponse
    {
        $wr = WithdrawRequest::find($id);
        if ($wr->approved == 0) {
            DeliveryCompanyWallet::where('partner_id', Helpers::get_partner_id())->decrement('pending_withdraw', $wr['amount']);
        }
        $wr->delete();
        Toastr::success('request closed!');
        return back();
    }
}
