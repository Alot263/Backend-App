<?php

namespace App\Http\Controllers\Partner;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function view()
    {
        return view('delivery-partner-views.profile.index');
    }

    public function bank_view()
    {
        $data = Helpers::get_partner_data();
        return view('delivery-partner-views.profile.bankView', compact('data'));
    }

    public function edit()
    {
        $data = Helpers::get_partner_data();
        return view('delivery-partner-views.profile.edit', compact('data'));
    }

    public function update(Request $request)
    {
        $table=auth('partner')->check()?'partners':'partner_employees';
        $seller = auth('partner')->check()?auth('partner')->user():auth('partner_employee')->user();
        $request->validate([
            'f_name' => 'required|max:100',
            'l_name' => 'nullable|max:100',
            'email' => 'required|unique:'.$table.',email,'.$seller->id,
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10|max:20|unique:'.$table.',phone,'.$seller->id,
        ], [
            'f_name.required' => translate('messages.first_name_is_required'),
        ]);
        $seller = auth('partner')->check()?auth('partner')->user():auth('partner_employee')->user();
        $seller->f_name = $request->f_name;
        $seller->l_name = $request->l_name;
        $seller->phone = $request->phone;
        $seller->email = $request->email;
        if ($request->image) {
            $seller->image = Helpers::update('partner/', $seller->image, 'png', $request->file('image'));
        }
        $seller->save();

        Toastr::success(translate('messages.profile_updated_successfully'));
        return back();
    }

    public function settings_password_update(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => 'required|same:confirm_password|min:6',
            'confirm_password' => 'required',
        ]);

        $seller = auth('partner')->check()?Helpers::get_partner_data():auth('partner_employee')->user();
        $seller->password = bcrypt($request['password']);
        $seller->save();
        Toastr::success(translate('messages.partner_pasword_updated_successfully'));
        return back();
    }

    public function bank_update(Request $request): RedirectResponse
    {
        $request->validate([
            'bank_name' => 'required|max:191',
            'branch' => 'required|max:191',
            'holder_name' => 'required|max:191',
            'account_no' => 'required|max:191',
        ]);
        $bank = Helpers::get_partner_data();
        $bank->bank_name = $request->bank_name;
        $bank->branch = $request->branch;
        $bank->holder_name = $request->holder_name;
        $bank->account_no = $request->account_no;
        $bank->save();
        Toastr::success(translate('messages.bank_info_updated_successfully'));
        return redirect()->route('partner.profile.bankView');
    }

    public function bank_edit()
    {
        $data = Helpers::get_partner_data();
        return view('delivery-partner-views.profile.bankEdit', compact('data'));
    }

}
