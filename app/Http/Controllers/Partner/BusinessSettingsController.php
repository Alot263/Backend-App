<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\DeliveryCompany;
use App\Models\DeliveryCompanySchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\Validator;

class BusinessSettingsController extends Controller
{

    private $delivery_company;

    public function delivery_company_index()
    {
        $delivery_company = Helpers::get_delivery_company_data();
        return view('delivery-partner-views.business-settings.restaurant-index', compact('delivery_company'));
    }

    public function delivery_company_setup(DeliveryCompany $delivery_company, Request $request): RedirectResponse
    {
        $request->validate([
            'gst' => 'required_if:gst_status,1',
            'per_km_delivery_charge'=>'required_with:minimum_delivery_charge',
            'minimum_delivery_charge'=>'required_with:per_km_delivery_charge'
        ], [
            'gst.required_if' => translate('messages.gst_can_not_be_empty'),
        ]);

        if(isset($request->maximum_shipping_charge) && ($request->minimum_delivery_charge > $request->maximum_shipping_charge)){
            Toastr::error(translate('Maximum delivery charge must be greater than minimum delivery charge.'));
                return back();
        }

        $delivery_company->minimum_order = $request->minimum_order;
        $delivery_company->gst = json_encode(['status'=>$request->gst_status, 'code'=>$request->gst]);
        // $delivery_company->delivery_charge = $delivery_company->self_delivery_system?$request->delivery_charge??0: $delivery_company->delivery_charge;
        $delivery_company->minimum_shipping_charge = $delivery_company->self_delivery_system?$request->minimum_delivery_charge??0: $delivery_company->minimum_shipping_charge;
        $delivery_company->per_km_shipping_charge = $delivery_company->self_delivery_system?$request->per_km_delivery_charge??0: $delivery_company->per_km_shipping_charge;
        $delivery_company->per_km_shipping_charge = $delivery_company->self_delivery_system?$request->per_km_delivery_charge??0: $delivery_company->per_km_shipping_charge;
        $delivery_company->maximum_shipping_charge = $delivery_company->self_delivery_system?$request->maximum_shipping_charge??0: $delivery_company->maximum_shipping_charge;
        $delivery_company->order_place_to_schedule_interval = $request->order_place_to_schedule_interval;
        $delivery_company->delivery_time = $request->minimum_delivery_time .'-'. $request->maximum_delivery_time.' '.$request->delivery_time_type;
        $delivery_company->save();
        Toastr::success(translate('messages.delivery_company_settings_updated'));
        return back();
    }

    public function delivery_company_status(DeliveryCompany $delivery_company, Request $request)
    {
        if($request->menu == "schedule_order" && !Helpers::schedule_order())
        {
            Toastr::warning(translate('messages.schedule_order_disabled_warning'));
            return back();
        }

        if((($request->menu == "delivery" && $delivery_company->take_away==0) || ($request->menu == "take_away" && $delivery_company->delivery==0)) &&  $request->status == 0 )
        {
            Toastr::warning(translate('messages.can_not_disable_both_take_away_and_delivery'));
            return back();
        }

        if((($request->menu == "veg" && $delivery_company->non_veg==0) || ($request->menu == "non_veg" && $delivery_company->veg==0)) &&  $request->status == 0 )
        {
            Toastr::warning(translate('messages.veg_non_veg_disable_warning'));
            return back();
        }

        $delivery_company[$request->menu] = $request->status;
        $delivery_company->save();
        Toastr::success(translate('messages.delivery_company settings updated!'));
        return back();
    }

    public function active_status(Request $request)
    {
        $delivery_company = Helpers::get_delivery_company_data();
        $delivery_company->active = $delivery_company->active?0:1;
        $delivery_company->save();
        return response()->json(['message' => $delivery_company->active?translate('messages.delivery_company_opened'):translate('messages.delivery_company_temporarily_closed')], 200);
    }

    public function add_schedule(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'start_time'=>'required|date_format:H:i',
            'end_time'=>'required|date_format:H:i|after:start_time',
        ],[
            'end_time.after'=>translate('messages.End time must be after the start time')
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }
        $temp = DeliveryCompanySchedule::where('day', $request->day)->where('delivery_company_id',Helpers::get_delivery_company_id())
        ->where(function($q)use($request){
            return $q->where(function($query)use($request){
                return $query->where('opening_time', '<=' , $request->start_time)->where('closing_time', '>=', $request->start_time);
            })->orWhere(function($query)use($request){
                return $query->where('opening_time', '<=' , $request->end_time)->where('closing_time', '>=', $request->end_time);
            });
        })
        ->first();

        if(isset($temp))
        {
            return response()->json(['errors' => [
                ['code'=>'time', 'message'=>translate('messages.schedule_overlapping_warning')]
            ]]);
        }

        $delivery_company = Helpers::get_delivery_company_data();
        $delivery_company_schedule = DeliveryCompanySchedule::insert(['delivery_company_id'=>Helpers::get_delivery_company_id(),'day'=>$request->day,'opening_time'=>$request->start_time,'closing_time'=>$request->end_time]);
        return response()->json([
            'view' => view('delivery-partner-views.business-settings.partials._schedule', compact('delivery_company'))->render(),
        ]);
    }

    public function remove_schedule($delivery_company_schedule): JsonResponse
    {
        $delivery_company = Helpers::get_delivery_company_data();
        $schedule = DeliveryCompanySchedule::where('delivery_company_id', $delivery_company->id)->find($delivery_company_schedule);
        if(!$schedule)
        {
            return response()->json([],404);
        }
        $schedule->delete();
        return response()->json([
            'view' => view('delivery-partner-views.business-settings.partials._schedule', compact('delivery_company'))->render(),
        ]);
    }


    public function site_direction_partner(Request $request): JsonResponse
    {
        session()->put('site_direction_partner', ($request->status == 1?'ltr':'rtl'));
        return response()->json();
    }
}
