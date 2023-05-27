<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\DeliveryCompanyLogic;
use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\AccountTransaction;
use App\Models\AddOn;
use App\Models\Conversation;
use App\Models\DeliveryCompany;
use App\Models\DeliveryCompanySchedule;
use App\Models\DeliveryCompanyWallet;
use App\Models\Message;
use App\Models\Module;
use App\Models\OrderTransaction;
use App\Models\Partner;
use App\Models\UserInfo;
use App\Models\WithdrawRequest;
use App\Models\Zone;
use App\Scopes\DeliveryCompanyScope;
use Brian2694\Toastr\Facades\Toastr;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Rap2hpoutre\FastExcel\FastExcel;

class DeliveryPartnerController extends Controller
{
    public function index()
    {
        return view('admin-views.delivery-partner.index');
    }

    public function delivery_company(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'f_name' => 'required|max:100',
            'l_name' => 'nullable|max:100',
            'name' => 'required|max:191',
            'address' => 'required|max:1000',
            'latitude' => 'required',
            'longitude' => 'required',
            'email' => 'required|unique:partners',
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10|max:20|unique:partners',
            'minimum_delivery_time' => 'required',
            'maximum_delivery_time' => 'required',
            'delivery_time_type'=>'required',
            'password' => 'required|min:6',
            'zone_id' => 'required',
            // 'module_id' => 'required',
            'logo' => 'required',
            'tax' => 'required'
        ], [
            'f_name.required' => translate('messages.first_name_is_required')
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
        $delivery_company->tax = $request->tax;
        $delivery_company->delivery_time = $request->minimum_delivery_time .'-'. $request->maximum_delivery_time.' '.$request->delivery_time_type;
        $delivery_company->module_id = Config::get('module.current_module_id');
        $delivery_company->save();
        $delivery_company->module->increment('delivery_companies_count');
        if(config('module.'.$delivery_company->module->module_type)['always_open'])
        {
            DeliveryCompanyLogic::insert_schedule($delivery_company->id);
        }
        // $delivery_company->zones()->attach($request->zone_ids);
        Toastr::success(translate('messages.delivery_company').translate('messages.added_successfully'));
        return redirect('admin/delivery-company/list');
    }

    public function edit($id)
    {
        if(env('APP_MODE')=='demo' && $id == 2)
        {
            Toastr::warning(translate('messages.you_can_not_edit_this_delivery_company_please_add_a_new_delivery_company_to_edit'));
            return back();
        }
        $delivery_company = DeliveryCompany::findOrFail($id);
        return view('admin-views.delivery-partner.edit', compact('delivery_company'));
    }

    public function update(Request $request, DeliveryCompany $delivery_company)
    {

        $validator = Validator::make($request->all(), [
            'f_name' => 'required|max:100',
            'l_name' => 'nullable|max:100',
            'name' => 'required|max:191',
            'email' => 'required|unique:partners,email,'.$delivery_company->partner->id,
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10|max:20|unique:partners,phone,'.$delivery_company->partner->id,
            'zone_id'=>'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'tax' => 'required',
            'password' => 'nullable|min:6',
            'minimum_delivery_time' => 'required',
            'maximum_delivery_time' => 'required',
            'delivery_time_type'=>'required'
        ], [
            'f_name.required' => translate('messages.first_name_is_required')
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
        $Partner = Partner::findOrFail($delivery_company->partner->id);
        $Partner->f_name = $request->f_name;
        $Partner->l_name = $request->l_name;
        $Partner->email = $request->email;
        $Partner->phone = $request->phone;
        $Partner->password = strlen($request->password)>1?bcrypt($request->password):$delivery_company->partner->password;
        $Partner->save();

        $slug = Str::slug($request->name);
        $delivery_company->slug = $delivery_company->slug? $delivery_company->slug :"{$slug}{$delivery_company->id}";
        $delivery_company->email = $request->email;
        $delivery_company->phone = $request->phone;
        $delivery_company->logo = $request->has('logo') ? Helpers::update('delivery_company/', $delivery_company->logo, 'png', $request->file('logo')) : $delivery_company->logo;
        $delivery_company->cover_photo = $request->has('cover_photo') ? Helpers::update('delivery_company/cover/', $delivery_company->cover_photo, 'png', $request->file('cover_photo')) : $delivery_company->cover_photo;
        $delivery_company->name = $request->name;
        $delivery_company->address = $request->address;
        $delivery_company->latitude = $request->latitude;
        $delivery_company->longitude = $request->longitude;
        $delivery_company->zone_id = $request->zone_id;
        $delivery_company->tax = $request->tax;
        $delivery_company->delivery_time = $request->minimum_delivery_time .'-'. $request->maximum_delivery_time.' '.$request->delivery_time_type;
        $delivery_company->save();
        if ($Partner->userinfo) {
            $userinfo = $Partner->userinfo;
            $userinfo->f_name = $delivery_company->name;
            $userinfo->l_name = '';
            $userinfo->email = $delivery_company->email;
            $userinfo->image = $delivery_company->logo;
            $userinfo->save();
        }
        Toastr::success(translate('messages.delivery_company').translate('messages.updated_successfully'));
        return redirect('admin/delivery-company/list');
    }

    public function destroy(Request $request, DeliveryCompany $delivery_company)
    {
        if(env('APP_MODE')=='demo' && $delivery_company->id == 2)
        {
            Toastr::warning(translate('messages.you_can_not_delete_this_delivery_company_please_add_a_new_delivery_company_to_delete'));
            return back();
        }
        if (Storage::disk('public')->exists('delivery_company/' . $delivery_company['logo'])) {
            Storage::disk('public')->delete('delivery_company/' . $delivery_company['logo']);
        }
        $delivery_company->delete();

        $partner = Partner::findOrFail($delivery_company->partner()->id);
        if($partner->userinfo){
            $partner->userinfo->delete();
        }
        $partner->delete();
        Toastr::success(translate('messages.delivery_company').' '.translate('messages.removed'));
        return back();
    }

    public function view(DeliveryCompany $delivery_company, $tab=null, $sub_tab='cash')
    {
        $wallet = $delivery_company->partner()->wallet;
        if(!$wallet)
        {
            $wallet= new DeliveryCompanyWallet();
            $wallet->partner_id = $delivery_company->partner()->id;
            $wallet->total_earning= 0.0;
            $wallet->total_withdrawn=0.0;
            $wallet->pending_withdraw=0.0;
            $wallet->created_at=now();
            $wallet->updated_at=now();
            $wallet->save();
        }
        if($tab == 'settings')
        {
            return view('admin-views.deliver-partner.view.settings', compact('delivery_company'));
        }
        else if($tab == 'order')
        {
            return view('admin-views.delivery-prtner.view.order', compact('delivery_company'));
        }
        else if($tab == 'item')
        {
            return view('admin-views.delivery-partner.view.product', compact('delivery_company'));
        }
        else if($tab == 'discount')
        {
            return view('admin-views.delivery-partner.view.discount', compact('delivery_company'));
        }
        else if($tab == 'transaction')
        {
            return view('admin-views.delivery-partner.view.transaction', compact('delivery_company', 'sub_tab'));
        }

        else if($tab == 'reviews')
        {
            return view('admin-views.delivery-partner.view.review', compact('delivery_company', 'sub_tab'));

        } else if ($tab == 'conversations') {
            $user = UserInfo::where(['partner_id' => $delivery_company->partner->id])->first();
            if ($user) {
                $conversations = Conversation::with(['sender', 'receiver', 'last_message'])->WhereUser($user->id)
                    ->paginate(8);
            } else {
                $conversations = [];
            }
            return view('admin-views.delivery-partner.view.conversations', compact('delivery_company', 'sub_tab', 'conversations'));
        }
        return view('admin-views.delivery-partner.view.index', compact('delivery_company', 'wallet'));
    }

    public function view_tab(DeliveryCompany $delivery_company)
    {

        Toastr::error(translate('messages.unknown_tab'));
        return back();
    }

    public function list(Request $request)
    {
        $zone_id = $request->query('zone_id', 'all');
        $type = $request->query('type', 'all');
        $module_id = $request->query('module_id', 'all');
        $delivery_companies = DeliveryCompany::with('partner','module')->whereHas('partner', function($query){
            return $query->where('status', 1);
        })
            ->when(is_numeric($zone_id), function($query)use($zone_id){
                return $query->where('zone_id', $zone_id);
            })
            ->when(is_numeric($module_id), function($query)use($request){
                return $query->module($request->query('module_id'));
            })
            ->module(Config::get('module.current_module_id'))
            ->with('partner','module')->type($type)->latest()->paginate(config('default_pagination'));
        $zone = is_numeric($zone_id)?Zone::findOrFail($zone_id):null;
        return view('admin-views.delivery-partner.list', compact('delivery_companies', 'zone','type'));
    }

    public function pending_requests(Request $request)
    {
        $zone_id = $request->query('zone_id', 'all');
        $search_by = $request->query('search_by');
        $key = explode(' ', $search_by);
        $type = $request->query('type', 'all');
        $module_id = $request->query('module_id', 'all');
        $delivery_companies = DeliveryCompany::with('vpartner','module')->whereHas('partner', function($query){
            return $query->where('status', null);
        })
            ->when(is_numeric($zone_id), function($query)use($zone_id){
                return $query->where('zone_id', $zone_id);
            })
            ->when(is_numeric($module_id), function($query)use($request){
                return $query->module($request->query('module_id'));
            })
            ->when($search_by, function($query)use($key){
                return $query->where(function($query)use($key){
                    $query->orWhereHas('partner',function ($q) use ($key) {
                        $q->where(function($q)use($key){
                            foreach ($key as $value) {
                                $q->orWhere('f_name', 'like', "%{$value}%")
                                    ->orWhere('l_name', 'like', "%{$value}%")
                                    ->orWhere('email', 'like', "%{$value}%")
                                    ->orWhere('phone', 'like', "%{$value}%");
                            }
                        });
                    })->orWhere(function ($q) use ($key) {
                        foreach ($key as $value) {
                            $q->orWhere('name', 'like', "%{$value}%")
                                ->orWhere('email', 'like', "%{$value}%")
                                ->orWhere('phone', 'like', "%{$value}%");
                        }
                    });
                });
            })
            ->module(Config::get('module.current_module_id'))
            ->type($type)->latest()->paginate(config('default_pagination'));
        $zone = is_numeric($zone_id)?Zone::findOrFail($zone_id):null;
        return view('admin-views.delivery-partner.pending_requests', compact('delivery_companies', 'zone','type', 'search_by'));
    }

    public function deny_requests(Request $request)
    {
        $search_by = $request->query('search_by');
        $key = explode(' ', $search_by);
        $zone_id = $request->query('zone_id', 'all');
        $type = $request->query('type', 'all');
        $module_id = $request->query('module_id', 'all');
        $delivery_companies = DeliveryCompany::with('partner','module')->whereHas('partner', function($query){
            return $query->where('status', 0);
        })
            ->when(is_numeric($zone_id), function($query)use($zone_id){
                return $query->where('zone_id', $zone_id);
            })
            ->when(is_numeric($module_id), function($query)use($request){
                return $query->module($request->query('module_id'));
            })
            ->when($search_by, function($query)use($key){
                return $query->where(function($query)use($key){
                    $query->orWhereHas('partner',function ($q) use ($key) {
                        $q->where(function($q)use($key){
                            foreach ($key as $value) {
                                $q->orWhere('f_name', 'like', "%{$value}%")
                                    ->orWhere('l_name', 'like', "%{$value}%")
                                    ->orWhere('email', 'like', "%{$value}%")
                                    ->orWhere('phone', 'like', "%{$value}%");
                            }
                        });
                    })->orWhere(function ($q) use ($key) {
                        foreach ($key as $value) {
                            $q->orWhere('name', 'like', "%{$value}%")
                                ->orWhere('email', 'like', "%{$value}%")
                                ->orWhere('phone', 'like', "%{$value}%");
                        }
                    });
                });
            })
            ->module(Config::get('module.current_module_id'))
            ->type($type)->latest()->paginate(config('default_pagination'));
        $zone = is_numeric($zone_id)?Zone::findOrFail($zone_id):null;
        return view('admin-views.delivery-partner.deny_requests', compact('delivery_companies', 'zone','type', 'search_by'));
    }

    public function export(Request $request){
        $zone_id = $request->query('zone_id', 'all');
        $module_id = $request->query('module_id', 'all');
        $delieryCompanies = DeliveryCompany::whereHas('partner', function($query){
            return $query->where('status', 1);
        })
            ->when(is_numeric($zone_id), function($query)use($zone_id){
                return $query->where('zone_id', $zone_id);
            })
            ->when(is_numeric($module_id), function($query)use($request){
                return $query->module($request->query('module_id'));
            })
            ->module(Config::get('module.current_module_id'))
            ->with('partner','module')
            ->orderBy('id','DESC')
            ->get();
        if($request->type == 'excel'){
            return (new FastExcel(Helpers::export_delivery_companies($delieryCompanies)))->download('DeliveryCompanies.xlsx');
        }elseif($request->type == 'csv'){
            return (new FastExcel(Helpers::export_delivery_companies($delieryCompanies)))->download('DeliveryCompanies.csv');
        }
    }

    public function search(Request $request){
        $key = explode(' ', $request['search']);
        $delieryCompanies=DeliveryCompany::whereHas('partner',function ($q) {
            $q->where('status', 1);
        })->where(function($query)use($key){
            $query->orWhereHas('partner',function ($q) use ($key) {
                $q->where(function($q)use($key){
                    foreach ($key as $value) {
                        $q->orWhere('f_name', 'like', "%{$value}%")
                            ->orWhere('l_name', 'like', "%{$value}%")
                            ->orWhere('email', 'like', "%{$value}%")
                            ->orWhere('phone', 'like', "%{$value}%");
                    }
                });
            })->orWhere(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('name', 'like', "%{$value}%")
                        ->orWhere('email', 'like', "%{$value}%")
                        ->orWhere('phone', 'like', "%{$value}%");
                }
            });
        })
            ->module(Config::get('module.current_module_id'))
            ->get();
        $total=$delieryCompanies->count();
        return response()->json([
            'view'=>view('admin-views.delivery-partner.partials._table',compact('delieryCompanies'))->render(), 'total'=>$total
        ]);
    }

    public function get_delivery_companies(Request $request){
        $zone_ids = isset($request->zone_ids)?(count($request->zone_ids)>0?$request->zone_ids:[]):0;
        $data = DeliveryCompany::withOutGlobalScopes()->join('zones', 'zones.id', '=', 'delivery_companies.zone_id')
            ->when($zone_ids, function($query) use($zone_ids){
                $query->whereIn('delivery_companies.zone_id', $zone_ids);
            })
            ->when($request->module_id, function($query)use($request){
                $query->where('module_id', $request->module_id);
            })
            ->when($request->module_type, function($query)use($request){
                $query->whereHas('module', function($q)use($request){
                    $q->where('module_type', $request->module_type);
                });
            })
            ->where('delivery_companies.name', 'like', '%'.$request->q.'%')
            ->limit(8)->get([DB::raw('delivery_companies.id as id, CONCAT(delivery_companies.name, " (", zones.name,")") as text')]);
        if(isset($request->all))
        {
            $data[]=(object)['id'=>'all', 'text'=>'All'];
        }
        return response()->json($data);
    }

    public function status(DeliveryCompany $delivery_company, Request $request)
    {
        $delivery_company->status = $request->status;
        $delivery_company->save();
        $partner = $delivery_company->partner();

        try
        {
            if($request->status == 0)
            {   $partner->auth_token = null;
                if(isset($partner->fcm_token))
                {
                    $data = [
                        'title' => translate('messages.suspended'),
                        'description' => translate('messages.your_account_has_been_suspended'),
                        'order_id' => '',
                        'image' => '',
                        'type'=> 'block'
                    ];
                    Helpers::send_push_notif_to_device($partner->fcm_token, $data);
                    DB::table('user_notifications')->insert([
                        'data'=> json_encode($data),
                        'partner_id'=>$partner->id,
                        'created_at'=>now(),
                        'updated_at'=>now()
                    ]);
                }

            }

        }
        catch (\Exception $e) {
            Toastr::warning(translate('messages.push_notification_faild'));
        }

        Toastr::success(translate('messages.delivery_company').translate('messages.status_updated'));
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
        if($request->menu == "self_delivery_system" && $request->status == '0') {
            $delivery_company['free_delivery'] = 0;
        }

        $delivery_company[$request->menu] = $request->status;
        $delivery_company->save();
        Toastr::success(translate('messages.delivery_company').translate('messages.settings_updated'));
        return back();
    }

    public function discountSetup(DeliveryCompany $delivery_company, Request $request)
    {
        $message=translate('messages.discount');
        $message .= $delivery_company->discount?translate('messages.updated_successfully'):translate('messages.added_successfully');
        $delivery_company->discount()->updateOrinsert(
            [
                'delivery_company_id' => $delivery_company->id
            ],
            [
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'min_purchase' => $request->min_purchase != null ? $request->min_purchase : 0,
                'max_discount' => $request->max_discount != null ? $request->max_discount : 0,
                'discount' => $request->discount_type == 'amount' ? $request->discount : $request['discount'],
                'discount_type' => 'percent'
            ]
        );
        return response()->json(['message'=>$message], 200);
    }

    public function updateDeliveryCompanySettings(DeliveryCompany $delivery_company, Request $request)
    {
        $request->validate([
            'minimum_order'=>'required',
            'comission'=>'required',
            'tax'=>'required',
            'minimum_delivery_time' => 'required|regex:/^([0-9]{1})$/|min:1|max:2',
            'maximum_delivery_time' => 'required|regex:/^([0-9]{1})$/|min:1|max:2|gt:minimum_delivery_time',
        ]);

        if($request->comission_status)
        {
            $delivery_company->comission = $request->comission;
        }
        else{
            $delivery_company->comission = null;
        }

        $delivery_company->minimum_order = $request->minimum_order;
        $delivery_company->tax = $request->tax;
        $delivery_company->order_place_to_schedule_interval = $request->order_place_to_schedule_interval;
        $delivery_company->delivery_time = $request->minimum_delivery_time .'-'. $request->maximum_delivery_time.' '.$request->delivery_time_type;
        $delivery_company->veg = (bool)($request->veg_non_veg == 'veg' || $request->veg_non_veg == 'both');
        $delivery_company->non_veg = (bool)($request->veg_non_veg == 'non_veg' || $request->veg_non_veg == 'both');

        $delivery_company->save();
        Toastr::success(translate('messages.delivery_company').translate('messages.settings_updated'));
        return back();
    }

    public function update_application(Request $request)
    {
        $delivery_company = DeliveryCompany::findOrFail($request->id);
        $delivery_company->partner()->status = $request->status;
        $delivery_company->partner()->save();
        if($request->status) $delivery_company->status = 1;
        $delivery_company->save();
        try{
            if ( config('mail.status') ) {
                Mail::to($request['email'])->send(new \App\Mail\SelfRegistration($request->status==1?'approved':'denied', $delivery_company->partner()->f_name.' '.$delivery_company->partner()->l_name));
            }
        }catch(\Exception $ex){
            info($ex);
        }
        Toastr::success(translate('messages.application_status_updated_successfully'));
        return back();
    }

    public function cleardiscount(DeliveryCompany $delivery_company)
    {
        $delivery_company->discount->delete();
        Toastr::success(translate('messages.delivery_company').translate('messages.discount_cleared'));
        return back();
    }

    public function withdraw()
    {
        $all = session()->has('withdraw_status_filter') && session('withdraw_status_filter') == 'all' ? 1 : 0;
        $active = session()->has('withdraw_status_filter') && session('withdraw_status_filter') == 'approved' ? 1 : 0;
        $denied = session()->has('withdraw_status_filter') && session('withdraw_status_filter') == 'denied' ? 1 : 0;
        $pending = session()->has('withdraw_status_filter') && session('withdraw_status_filter') == 'pending' ? 1 : 0;

        $withdraw_req =WithdrawRequest::with(['partner'])
            ->when($all, function ($query) {
                return $query;
            })
            ->when($active, function ($query) {
                return $query->where('approved', 1);
            })
            ->when($denied, function ($query) {
                return $query->where('approved', 2);
            })
            ->when($pending, function ($query) {
                return $query->where('approved', 0);
            })
            ->latest()
            ->paginate(config('default_pagination'));

        if(!Helpers::module_permission_check('withdraw_list')){
            return view('admin-views.wallet.withdraw-dashboard');
        }

        return view('admin-views.wallet.withdraw', compact('withdraw_req'));
    }
    public function withdraw_export(Request $request)
    {
        $all = session()->has('withdraw_status_filter') && session('withdraw_status_filter') == 'all' ? 1 : 0;
        $active = session()->has('withdraw_status_filter') && session('withdraw_status_filter') == 'approved' ? 1 : 0;
        $denied = session()->has('withdraw_status_filter') && session('withdraw_status_filter') == 'denied' ? 1 : 0;
        $pending = session()->has('withdraw_status_filter') && session('withdraw_status_filter') == 'pending' ? 1 : 0;

        $withdraw_req =WithdrawRequest::with(['partner'])
            ->when($all, function ($query) {
                return $query;
            })
            ->when($active, function ($query) {
                return $query->where('approved', 1);
            })
            ->when($denied, function ($query) {
                return $query->where('approved', 2);
            })
            ->when($pending, function ($query) {
                return $query->where('approved', 0);
            })
            ->latest()->get();
        if($request->type == 'excel'){
            return (new FastExcel(Helpers::export_delivery_company_withdraw($withdraw_req)))->download('WithdrawRequests.xlsx');
        }elseif($request->type == 'csv'){
            return (new FastExcel(Helpers::export_delivery_company_withdraw($withdraw_req)))->download('WithdrawRequests.csv');
        }
    }

    public function withdraw_search(Request $request){
        $key = explode(' ', $request['search']);
        $withdraw_req = WithdrawRequest::whereHas('partner', function ($query) use ($key) {
            $query->whereHas('delivery_companies', function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->where('name', 'like', "%{$value}%");
                }
            });
        })->get();
        $total=$withdraw_req->count();
        return response()->json([
            'view'=>view('admin-views.wallet.partials._table',compact('withdraw_req'))->render(), 'total'=>$total
        ]);
    }

    public function withdraw_view($withdraw_id, $seller_id)
    {
        $wr = WithdrawRequest::with(['partner'])->where(['id' => $withdraw_id])->first();
        return view('admin-views.wallet.withdraw-view', compact('wr'));
    }

    public function status_filter(Request $request){
        session()->put('withdraw_status_filter',$request['withdraw_status_filter']);
        return response()->json(session('withdraw_status_filter'));
    }

    public function withdrawStatus(Request $request, $id)
    {
        $withdraw = WithdrawRequest::findOrFail($id);
        $withdraw->approved = $request->approved;
        $withdraw->transaction_note = $request['note'];
        if ($request->approved == 1) {
            DeliveryCompanyWallet::where('partner_id', $withdraw->partner_id)->increment('total_withdrawn', $withdraw->amount);
            DeliveryCompanyWallet::where('partner_id', $withdraw->partner_id)->decrement('pending_withdraw', $withdraw->amount);
            $withdraw->save();
            Toastr::success(translate('messages.seller_payment_approved'));
            return redirect()->route('admin.transactions.delivery_company.withdraw_list');
        } else if ($request->approved == 2) {
            DeliveryCompanyWallet::where('partner_id', $withdraw->partner_id)->decrement('pending_withdraw', $withdraw->amount);
            $withdraw->save();
            Toastr::info(translate('messages.seller_payment_denied'));
            return redirect()->route('admin.transactions.delivery_company.withdraw_list');
        } else {
            Toastr::error(translate('messages.not_found'));
            return back();
        }
    }

    public function get_addons(Request $request)
    {
        $cat = AddOn::withoutGlobalScope(DeliveryCompanyScope::class)->withoutGlobalScope('translate')->where(['delivery_company_id' => $request->delivery_company_id])->active()->get();
        $res = '';
        foreach ($cat as $row) {
            $res .= '<option value="' . $row->id.'"';
            if(count($request->data))
            {
                $res .= in_array($row->id, $request->data)?'selected':'';
            }
            $res .=  '>' . $row->name . '</option>';
        }
        return response()->json([
            'options' => $res,
        ]);
    }

    public function get_delivery_company_data(DeliveryCompany $delivery_company)
    {
        return response()->json($delivery_company);
    }

    public function delivery_company_filter($id)
    {
        if ($id == 'all') {
            if (session()->has('delivery_company_filter')) {
                session()->forget('delivery_company_filter');
            }
        } else {
            session()->put('delivery_company_filter', DeliveryCompany::where('id', $id)->first(['id', 'name']));
        }
        return back();
    }

    public function get_account_data(DeliveryCompany $delivery_company)
    {
        $wallet = $delivery_company->partner()->wallet;
        $cash_in_hand = 0;
        $balance = 0;

        if($wallet)
        {
            $cash_in_hand = $wallet->collected_cash;
            $balance = $wallet->total_earning;
        }
        return response()->json(['cash_in_hand'=>$cash_in_hand, 'earning_balance'=>$balance], 200);

    }

    public function bulk_import_index()
    {
        return view('admin-views.delivery-partner.bulk-import');
    }

    public function bulk_import_data(Request $request)
    {
        $request->validate([
            'module_id'=>'required_if:stackfood,1',
            'products_file'=>'required|file'
        ]);
        try {
            $collections = (new FastExcel)->import($request->file('products_file'));
        } catch (\Exception $exception) {
            Toastr::error(translate('messages.you_have_uploaded_a_wrong_format_file'));
            return back();
        }
        $duplicate_phones = $collections->duplicates('phone');
        $duplicate_emails = $collections->duplicates('email');

        // dd(['Phone'=>$duplicate_phones, 'Email'=>$duplicate_emails]);
        if($duplicate_emails->isNotEmpty())
        {
            Toastr::error(translate('messages.duplicate_data_on_column',['field'=>translate('messages.email')]));
            return back();
        }

        if($duplicate_phones->isNotEmpty())
        {
            Toastr::error(translate('messages.duplicate_data_on_column',['field'=>translate('messages.phone')]));
            return back();
        }

        $partners = [];
        $delieryCompanies = [];
        $partner = Partner::orderBy('id', 'desc')->first('id');
        $partner_id = $partner?$partner->id:0;
        $delivery_company = DeliveryCompany::orderBy('id', 'desc')->first('id');
        $deliery_company_id = $delivery_company?$delivery_company->id:0;
        $deliery_company_ids = [];
        foreach ($collections as $key=>$collection) {
            if ($collection['ownerFirstName'] === "" || $collection['deliveryCompanyName'] === "" || $collection['phone'] === "" || $collection['email'] === "" || $collection['latitude'] === "" || $collection['longitude'] === "" || $collection['zone_id'] === "" || $collection['module_id'] === "") {
                Toastr::error(translate('messages.please_fill_all_required_fields'));
                return back();
            }

            if (!is_numeric($collection['latitude']) || $collection['latitude'] > 90 || $collection['latitude'] < -90 || !is_numeric($collection['longitude']) || $collection['longitude'] > 180 || $collection['longitude'] < -180) {
                Toastr::error(translate('messages.invalid_latitude_or_longtitude'));
                return back();
            }


            array_push($partners, [
                'id'=>$partner_id+$key+1,
                'f_name' => $collection['ownerFirstName'],
                'l_name' => $collection['ownerLastName'],
                'password' => bcrypt(12345678),
                'phone' => $collection['phone'],
                'email' => $collection['email'],
                'created_at'=>now(),
                'updated_at'=>now()
            ]);
            array_push($delieryCompanies, [
                'id'=>$deliery_company_id+$key+1,
                'name' => $request->stackfood?$collection['deliveryCompanyName']:$collection['deliveryCompanyName'],
                'logo' => $collection['logo'],
                'phone' => $collection['phone'],
                'email' => $collection['email'],
                'latitude' => $collection['latitude'],
                'longitude' => $collection['longitude'],
                'partner_id' => $partner_id+$key+1,
                'zone_id' => $collection['zone_id'],
                'delivery_time' => (isset($collection['delivery_time']) && preg_match('([0-9]+[\-][0-9]+\s[min|hours|days])', $collection['delivery_time'])) ? $collection['delivery_time'] :'30-40 min',
                'module_id' => $request->stackfood?$request->module_id:$collection['module_id'],
                'created_at'=>now(),
                'updated_at'=>now()
            ]);
            if($module = Module::select('module_type')->where('id', $collection['module_id'])->first())
            {
                if(config('module.'.$module->module_type))
                {
                    $deliery_company_ids[] = $deliery_company_id+$key+1;
                }
            }

        }

        $data = array_map(function($id){
            return array_map(function($item)use($id){
                return     ['delivery_company_id'=>$id,'day'=>$item,'opening_time'=>'00:00:00','closing_time'=>'23:59:59'];
            },[0,1,2,3,4,5,6]);
        },$deliery_company_ids);

        try{
            DB::beginTransaction();
            DB::table('partners')->insert($partners);
            DB::table('delivery_companies')->insert($delieryCompanies);
            DB::table('delivery_company_schedule')->insert(array_merge(...$data));
            DB::commit();
        }catch(\Exception $e)
        {
            DB::rollBack();
            info($e);
            Toastr::error(translate('messages.failed_to_import_data'));
            return back();
        }

        Toastr::success(translate('messages.delivery_company_imported_successfully',['count'=>count($delieryCompanies)]));
        return back();
    }

    public function bulk_export_index()
    {
        return view('admin-views.delivery-partner.bulk-export');
    }

    public function bulk_export_data(Request $request)
    {
        $request->validate([
            'type'=>'required',
            'start_id'=>'required_if:type,id_wise',
            'end_id'=>'required_if:type,id_wise',
            'from_date'=>'required_if:type,date_wise',
            'to_date'=>'required_if:type,date_wise'
        ]);

        DB::enableQueryLog();
        $partners = Partner::with('delivery_companies')
            ->when($request['type']=='date_wise', function($query)use($request){
                $query->whereBetween('created_at', [$request['from_date'].' 00:00:00', $request['to_date'].' 23:59:59']);
            })
            ->when($request['type']=='id_wise', function($query)use($request){
                $query->whereBetween('id', [$request['start_id'], $request['end_id']]);
            })->whereHas('delivery_companies', function ($q) use ($request) {
                return $q->where('module_id', Config::get('module.current_module_id'));
            })
            ->get();
       // dd(DB::getQueryLog());



        return (new FastExcel(DeliveryCompanyLogic::format_export_delivery_companies($partners)))->download('DeliveryCompanies.xlsx');
    }

    public function add_schedule(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'start_time'=>'required|date_format:H:i',
            'end_time'=>'required|date_format:H:i|after:start_time',
            'delivery_company_id'=>'required',
        ],[
            'end_time.after'=>translate('messages.End time must be after the start time')
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }

        $temp = DeliveryCompanySchedule::where('day', $request->day)->where('delivery_company_id',$request->delivery_company_id)
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

        $delivery_company = DeliveryCompany::find($request->delivery_company_id);
        $delivery_company_schedule = DeliveryCompanyLogic::insert_schedule($request->delivery_company_id, [$request->day], $request->start_time, $request->end_time.':59');

        return response()->json([
            'view' => view('admin-views.delivery-partner.view.partials._schedule', compact('delivery_company'))->render(),
        ]);
    }

    public function remove_schedule($delivery_company_schedule)
    {
        $schedule = DeliveryCompanySchedule::find($delivery_company_schedule);
        if(!$schedule)
        {
            return response()->json([],404);
        }
        $delivery_company = $schedule->delivery_company;
        $schedule->delete();
        return response()->json([
            'view' => view('admin-views.delivery-partner.view.partials._schedule', compact('delivery_company'))->render(),
        ]);
    }

    public function featured(Request $request): RedirectResponse
    {
        $delivery_company = DeliveryCompany::findOrFail($request->delivery_company);
        $delivery_company->featured = $request->status;
        $delivery_company->save();
        Toastr::success(translate('messages.delivery_company_featured_status_updated'));
        return back();
    }

    public function conversation_list(Request $request): JsonResponse
    {

        $user = UserInfo::where('partner_id', $request->user_id)->first();

        $conversations = Conversation::WhereUser($user->id);

        if ($request->query('key') != null) {
            $key = explode(' ', $request->get('key'));
            $conversations = $conversations->where(function ($qu) use ($key) {

                $qu->whereHas('sender', function ($query) use ($key) {
                    foreach ($key as $value) {
                        $query->where('f_name', 'like', "%{$value}%")->orWhere('l_name', 'like', "%{$value}%")->orWhere('phone', 'like', "%{$value}%");
                    }
                })->orWhereHas('receiver', function ($query1) use ($key) {
                    foreach ($key as $value) {
                        $query1->where('f_name', 'like', "%{$value}%")->orWhere('l_name', 'like', "%{$value}%")->orWhere('phone', 'like', "%{$value}%");
                    }
                });
            });
        }

        $conversations = $conversations->paginate(8);

        $view = view('admin-views.delivery-partner.view.partials._conversation_list', compact('conversations'))->render();
        return response()->json(['html' => $view]);
    }

    public function conversation_view($conversation_id, $user_id)
    {
        $convs = Message::where(['conversation_id' => $conversation_id])->get();
        $conversation = Conversation::find($conversation_id);
        $receiver = UserInfo::find($conversation->receiver_id);
        $sender = UserInfo::find($conversation->sender_id);
        $user = UserInfo::find($user_id);
        return response()->json([
            'view' => view('admin-views.delivery-partner.view.partials._conversations', compact('convs', 'user', 'receiver'))->render()
        ]);
    }


    public function cash_export($type,$deliery_company_id)
    {
        $delivery_company = DeliveryCompany::find($deliery_company_id);
        $account = AccountTransaction::where('from_type', 'delivery_company')->where('from_id', $delivery_company->id)->get();
        if($type == 'excel'){
            return (new FastExcel($account))->download('CashTransaction.xlsx');
        }elseif($type == 'csv'){
            return (new FastExcel($account))->download('CashTransaction.csv');
        }
    }

    public function order_export($type,$deliery_company_id)
    {
        $delivery_company = DeliveryCompany::find($deliery_company_id);
        $account = OrderTransaction::where('partner_id', $delivery_company->partner()->id)->get();
        if($type == 'excel'){
            return (new FastExcel($account))->download('OrderTransaction.xlsx');
        }elseif($type == 'csv'){
            return (new FastExcel($account))->download('OrderTransaction.csv');
        }
    }

    public function withdraw_trans_export($type,$deliery_company_id)
    {
        $delivery_company = DeliveryCompany::find($deliery_company_id);
        $account = WithdrawRequest::where('partner_id', $delivery_company->partner()->id)->get();
        if($type == 'excel'){
            return (new FastExcel($account))->download('WithdrawTransaction.xlsx');
        }elseif($type == 'csv'){
            return (new FastExcel($account))->download('WithdrawTransaction.csv');
        }
    }
}
