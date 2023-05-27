<?php

namespace App\Http\Controllers\Api\V1\Partner;

use App\Http\Controllers\Controller;
use App\Models\DeliveryCompanySchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\Validator;


class BusinessSettingsController extends Controller
{

    public function update_delivery_company_setup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'address' => 'required',
            'contact_number' => 'required',
            'delivery' => 'required|boolean',
            'take_away' => 'required|boolean',
            'schedule_order' => 'required|boolean',
            'veg' => 'required|boolean',
            'non_veg' => 'required|boolean',
            'minimum_order' => 'required|numeric',
            'gst' => 'required_if:gst_status,1',
            'minimum_delivery_time' => 'required|numeric',
            'maximum_delivery_time' => 'required|numeric',
            'delivery_time_type'=>'required|in:min,hours,days'

        ],[
            'gst.required_if' => translate('messages.gst_can_not_be_empty'),
        ]);
        $delivery_company = $request['partner']->delivery_companies[0];
        $validator->sometimes('per_km_delivery_charge', 'required_with:minimum_delivery_charge', function ($request) use($delivery_company) {
            return ($delivery_company->self_delivery_system);
        });
        $validator->sometimes('minimum_delivery_charge', 'required_with:per_km_delivery_charge', function ($request) use($delivery_company) {
            return ($delivery_company->self_delivery_system);
        });
        // $validator->sometimes('delivery_charge', 'required', function ($request) use($delivery_company) {
        //     return ($delivery_company->self_delivery_system);
        // });

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        if(!$request->take_away && !$request->delivery)
        {
            return response()->json([
                'errors'=>[
                    ['code'=>'delivery_or_take_way', 'message'=>translate('messages.can_not_disable_both_take_away_and_delivery')]
                ]
            ],403);
        }

        if(!$request->veg && !$request->non_veg)
        {
            return response()->json([
                'errors'=>[
                    ['code'=>'veg_non_veg', 'message'=>translate('messages.veg_non_veg_disable_warning')]
                ]
            ],403);
        }

        $delivery_company->delivery = $request->delivery;
        $delivery_company->take_away = $request->take_away;
        $delivery_company->schedule_order = $request->schedule_order;
        $delivery_company->veg = $request->veg;
        $delivery_company->non_veg = $request->non_veg;
        $delivery_company->minimum_order = $request->minimum_order;
        $delivery_company->gst = json_encode(['status'=>$request->gst_status, 'code'=>$request->gst]);
        // $delivery_company->delivery_charge = $delivery_company->self_delivery_system?$request->delivery_charge: $delivery_company->delivery_charge;
        $delivery_company->minimum_shipping_charge = $delivery_company->self_delivery_system?$request->minimum_delivery_charge??0: $delivery_company->minimum_shipping_charge;
        $delivery_company->per_km_shipping_charge = $delivery_company->self_delivery_system?$request->per_km_delivery_charge??0: $delivery_company->per_km_shipping_charge;
        $delivery_company->maximum_shipping_charge = $delivery_company?$request->maximum_delivery_charge??0: $delivery_company->maximum_delivery_charge;
        $delivery_company->delivery_time = $request->minimum_delivery_time .'-'. $request->maximum_delivery_time.' '.$request->delivery_time_type;
        $delivery_company->name = $request->name;
        $delivery_company->address = $request->address;
        $delivery_company->phone = $request->contact_number;
        $delivery_company->order_place_to_schedule_interval = $request->order_place_to_schedule_interval;

        $delivery_company->logo = $request->has('logo') ? Helpers::update('delivery_company/', $delivery_company->logo, 'png', $request->file('logo')) : $delivery_company->logo;
        $delivery_company->cover_photo = $request->has('cover_photo') ? Helpers::update('delivery_company/cover/', $delivery_company->cover_photo, 'png', $request->file('cover_photo')) : $delivery_company->cover_photo;

        $delivery_company->save();

        return response()->json(['message'=>translate('messages.delivery_company_settings_updated')], 200);
    }

    public function add_schedule(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(),[
            'opening_time'=>'required|date_format:H:i:s',
            'closing_time'=>'required|date_format:H:i:s|after:opening_time',
        ],[
            'closing_time.after'=>translate('messages.End time must be after the start time')
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)],400);
        }
        $delivery_company = $request['partner']->delivery_companies[0];
        $temp = DeliveryCompanySchedule::where('day', $request->day)->where('delivery_company_id',$delivery_company->id)
        ->where(function($q)use($request){
            return $q->where(function($query)use($request){
                return $query->where('opening_time', '<=' , $request->opening_time)->where('closing_time', '>=', $request->opening_time);
            })->orWhere(function($query)use($request){
                return $query->where('opening_time', '<=' , $request->closing_time)->where('closing_time', '>=', $request->closing_time);
            });
        })
        ->first();

        if(isset($temp))
        {
            return response()->json(['errors' => [
                ['code'=>'time', 'message'=>translate('messages.schedule_overlapping_warning')]
            ]], 400);
        }

        $delivery_company_schedule = DeliveryCompanySchedule::insertGetId(['delivery_company_id'=>$delivery_company->id,'day'=>$request->day,'opening_time'=>$request->opening_time,'closing_time'=>$request->closing_time]);
        return response()->json(['message'=>translate('messages.Schedule added successfully'), 'id'=>$delivery_company_schedule], 200);
    }

    public function remove_schedule(Request $request, $delivery_company_schedule): JsonResponse
    {
        $delivery_company = $request['partner']->delivery_companies[0];
        $schedule = DeliveryCompanySchedule::where('delivery_company_id', $delivery_company->id)->find($delivery_company_schedule);
        if(!$schedule)
        {
            return response()->json([
                'error'=>[
                    ['code'=>'not-fond', 'message'=>translate('messages.Schedule not found')]
                ]
            ],404);
        }
        $schedule->delete();
        return response()->json(['message'=>translate('messages.Schedule removed successfully')], 200);
    }
}
