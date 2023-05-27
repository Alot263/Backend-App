<?php

namespace App\Http\Controllers\Partner;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\DeliveryCompany;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RestaurantController extends Controller
{
    public function view()
    {
        $shop = Helpers::get_delivery_company_data();
        return view('delivery-partner-views.shop.shopInfo', compact('shop'));
    }

    public function edit()
    {
        $shop = Helpers::get_delivery_company_data();
        return view('delivery-partner-views.shop.edit', compact('shop'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|max:191',
            'address' => 'nullable|max:1000',
            'contact' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10|max:20|unique:delivery_companies,phone,'.Helpers::get_delivery_company_id(),
        ], [
            'f_name.required' => translate('messages.first_name_is_required'),
        ]);
        $shop = DeliveryCompany::findOrFail(Helpers::get_delivery_company_id());
        $shop->name = $request->name;
        $shop->address = $request->address;
        $shop->phone = $request->contact;

        $shop->logo = $request->has('image') ? Helpers::update('delivery_company/', $shop->logo, 'png', $request->file('image')) : $shop->logo;

        $shop->cover_photo = $request->has('photo') ? Helpers::update('delivery_company/cover/', $shop->cover_photo, 'png', $request->file('photo')) : $shop->cover_photo;

        $shop->save();

        if($shop->partner->userinfo) {
            $userinfo = $shop->partner->userinfo;
            $userinfo->f_name = $shop->name;
            $userinfo->image = $shop->logo;
            $userinfo->save();
        }

        Toastr::success(translate('messages.delivery_company_data_updated'));
        return redirect()->route('partner.shop.view');
    }

}
