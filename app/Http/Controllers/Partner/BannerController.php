<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\Banner;
use Brian2694\Toastr\Facades\Toastr;


class BannerController extends Controller
{
    function list()
    {
        $banners=Banner::latest()->paginate(config('default_pagination'));
        return view('delivery-partner-views.banner.list',compact('banners'));
    }


    public function status(Request $request): RedirectResponse
    {
        $banner = Banner::findOrFail($request->id);
        $delivery_company_id = $request->status;
        $delivery_company_ids = json_decode($banner->restaurant_ids);
        if(in_array($delivery_company_id, $delivery_company_ids))
        {
            unset($delivery_company_ids[array_search($delivery_company_id, $delivery_company_ids)]);
        }
        else
        {
            array_push($delivery_company_ids, $delivery_company_id);
        }

        $banner->restaurant_ids = json_encode($delivery_company_ids);
        $banner->save();
        Toastr::success(translate('messages.capmaign_participation_updated'));
        return back();
    }

}
