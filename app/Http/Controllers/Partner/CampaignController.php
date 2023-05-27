<?php

namespace App\Http\Controllers\Partner;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\Campaign;
use App\Models\ItemCampaign;
use Brian2694\Toastr\Facades\Toastr;


class CampaignController extends Controller
{
    function list()
    {
        $campaigns=Campaign::with('delivery_companies')->running()->latest()->module(Helpers::get_delivery_company_data()->module_id)->paginate(config('default_pagination'));
        return view('delivery-partner-views.campaign.list',compact('campaigns'));
    }

    function itemlist()
    {
        $campaigns=ItemCampaign::where('delivery_company_id', Helpers::get_delivery_company_id())->latest()->paginate(config('default_pagination'));
        return view('delivery-partner-views.campaign.item_list',compact('campaigns'));
    }

    public function remove_delivery_company(Campaign $campaign, $delivery_company): RedirectResponse
    {
        $campaign->delivery_companies()->detach($delivery_company);
        $campaign->save();
        Toastr::success(translate('messages.delivery_company_remove_from_campaign'));
        return back();
    }
    public function adddelivery_company(Campaign $campaign, $delivery_company)
    {
        $campaign->delivery_companies()->attach($delivery_company,['campaign_status' => 'pending']);
        $campaign->save();
        Toastr::success(translate('messages.delivery_company_added_to_campaign'));
        return back();
    }

    public function search(Request $request){
        $key = explode(' ', $request['search']);
        $campaigns=Campaign::
        where(function ($q) use ($key) {
            foreach ($key as $value) {
                $q->orWhere('title', 'like', "%{$value}%");
            }
        })
        ->module(Helpers::get_delivery_company_data()->module_id)
        ->limit(50)->get();
        return response()->json([
            'view'=>view('delivery-partner-views.campaign.partials._table',compact('campaigns'))->render()
        ]);
    }

    public function searchItem(Request $request){
        $key = explode(' ', $request['search']);
        $campaigns=ItemCampaign::where('delivery_company_id', Helpers::get_delivery_company_id())->where(function ($q) use ($key) {
            foreach ($key as $value) {
                $q->orWhere('title', 'like', "%{$value}%");
            }
        })->limit(50)->get();
        return response()->json([
            'view'=>view('delivery-partner-views.campaign.partials._item_table',compact('campaigns'))->render(),
            'count'=>$campaigns->count(),
        ]);
    }

}
