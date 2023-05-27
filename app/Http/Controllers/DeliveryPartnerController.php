<?php

namespace App\Http\Controllers;

use App\CentralLogics\DeliveryCompanyLogic;
use App\CentralLogics\Helpers;
use App\Models\BusinessSetting;
use App\Models\DeliveryCompany;
use App\Models\Module;
use App\Models\Partner;
use App\Models\Zone;
use Brian2694\Toastr\Facades\Toastr;
use Gregwar\Captcha\CaptchaBuilder;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class DeliveryPartnerController extends Controller
{
    public function create()
    {
        $status = BusinessSetting::where('key', 'toggle_delivery_company_registration')->first();
        if(!isset($status) || $status->value == '0')
        {
            Toastr::error(translate('messages.not_found'));
            return back();
        }
        $custome_recaptcha = new CaptchaBuilder;
        $custome_recaptcha->build();
        Session::put('six_captcha', $custome_recaptcha->getPhrase());

        return view('delivery-partner-views.auth.register', compact('custome_recaptcha'));
    }

    public function delivery_company(Request $request)
    {
        $status = BusinessSetting::where('key', 'toggle_delivery_company_registration')->first();
        if(!isset($status) || $status->value == '0')
        {
            Toastr::error(translate('messages.not_found'));
            return back();
        }

        $recaptcha = Helpers::get_business_settings('recaptcha');
        if (isset($recaptcha) && $recaptcha['status'] == 1) {
            $request->validate([
                'g-recaptcha-response' => [
                    function ($attribute, $value, $fail) {
                        $secret_key = Helpers::get_business_settings('recaptcha')['secret_key'];
                        $response = $value;
                        $url = 'https://www.google.com/recaptcha/api/siteverify?secret=' . $secret_key . '&response=' . $response;
                        $response = Http::get($url);
                        $response = $response->json();
                        if (!isset($response['success']) || !$response['success']) {
                            $fail(translate('messages.ReCAPTCHA Failed'));
                        }
                    },
                ],
            ]);
        } else if(strtolower(session('six_captcha')) != strtolower($request->custome_recaptcha))
        {
            Toastr::error(translate('messages.ReCAPTCHA Failed'));
            return back();
        }

        $validator = Validator::make($request->all(), [
            'f_name' => 'required',
            'name' => 'required',
            'address' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'email' => 'required|unique:partners',
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10|unique:partners',
            'minimum_delivery_time' => 'required',
            'maximum_delivery_time' => 'required',
            'password' => 'required|min:6',
            'zone_id' => 'required',
            'module_id' => 'required',
            'logo' => 'required',
            'tax' => 'required',
            'minimum_delivery_time' => 'required',
            'maximum_delivery_time' => 'required',
            'delivery_time_type'=>'required',
        ]);

        if($request->zone_id)
        {
            $point = new Point($request->latitude, $request->longitude);
            $zone = Zone::contains('coordinates', $point)->where('id', $request->zone_id)->first();
            if(!$zone){
                $validator->getMessageBag()->add('latitude', translate('messages.coordinates_out_of_zone'));
                return back()->withErrors($validator)
                        ->withInput();
            }
        }
        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }
        $partner = new Partner();
        $partner->f_name = $request->f_name;
        $partner->l_name = $request->l_name;
        $partner->email = $request->email;
        $partner->phone = $request->phone;
        $partner->password = bcrypt($request->password);
        $partner->status = null;
        $partner->save();

        $delivery_company = new DeliveryCompany();
        $delivery_company->name = $request->name;
        $delivery_company->phone = $request->phone;
        $delivery_company->email = $request->email;
        $delivery_company->logo = Helpers::upload('delivery_company/', 'png', $request->file('logo'));
        $delivery_company->cover_photo = Helpers::upload('delivery_company/cover/', 'png', $request->file('cover_photo'));
        $delivery_company->address = $request->address;
        $delivery_company->latitude = $request->latitude;
        $delivery_company->longitude = $request->longitude;
        $delivery_company->partner_id = $partner->id;
        $delivery_company->zone_id = $request->zone_id;
        $delivery_company->module_id = $request->module_id;
        $delivery_company->tax = $request->tax;
        $delivery_company->delivery_time = $request->minimum_delivery_time .'-'. $request->maximum_delivery_time.' '.$request->delivery_time_type;
        $delivery_company->status = 0;
        $delivery_company->save();
        try{
            if(config('mail.status')){
                Mail::to($request['email'])->send(new \App\Mail\SelfRegistration('pending', $partner->f_name.' '.$partner->l_name));
            }
        }catch(\Exception $ex){
            info($ex->getMessage());
        }


        if(config('module.'.$delivery_company->module->module_type)['always_open'])
        {
            DeliveryCompanyLogic::insert_schedule($delivery_company->id);
        }
        Toastr::success(translate('messages.application_placed_successfully'));
        return back();
    }

    public function get_all_modules(Request $request){
        $module_data = Module::whereHas('zones', function($query)use ($request){
            $query->where('zone_id', $request->zone_id);
        })->notParcel()
        ->where('modules.module_name', 'like', '%'.$request->q.'%')
        ->limit(8)->get([DB::raw('modules.id as id, modules.module_name as text')]);
        return response()->json($module_data);
    }
}
