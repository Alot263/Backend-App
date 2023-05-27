<?php

namespace App\Http\Controllers\Api\V1\Partner;

use App\CentralLogics\CouponLogic;
use App\CentralLogics\DeliveryCompanyLogic;
use App\CentralLogics\Helpers;
use App\CentralLogics\OrderLogic;
use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Models\DeliveryCompany;
use App\Models\PartnerEmployee;
use App\Models\Partner;
use App\Models\Order;
use App\Models\Notification;
use App\Models\UserNotification;
use App\Models\Campaign;
use App\Models\Coupon;
use App\Models\WithdrawRequest;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

class PartnerController extends Controller
{
    public function get_profile(Request $request)
    {
        $partner = $request['partner'];
        $delivery_companies = Helpers::delivery_company_data_formatting($partner->delivery_companies[0], false);
        $discount=Helpers::get_delivery_company_discount($partner->delivery_companies[0]);
        unset($delivery_companies['discount']);
        $delivery_companies['discount']=$discount;
        $delivery_companies['schedules']=$delivery_companies->schedules()->get();
        $delivery_companies['module']=$delivery_companies->module;

        $partner['order_count'] =$partner->orders->where('order_type','!=','pos')->whereNotIn('order_status',['canceled','failed'])->count();
        $partner['todays_order_count'] =$partner->todaysorders->where('order_type','!=','pos')->whereNotIn('order_status',['canceled','failed'])->count();
        $partner['this_week_order_count'] =$partner->this_week_orders->where('order_type','!=','pos')->whereNotIn('order_status',['canceled','failed'])->count();
        $partner['this_month_order_count'] =$partner->this_month_orders->where('order_type','!=','pos')->whereNotIn('order_status',['canceled','failed'])->count();
        $partner['member_since_days'] =$partner->created_at->diffInDays();
        $partner['cash_in_hands'] =$partner->wallet?(float)$partner->wallet->collected_cash:0;
        $partner['balance'] =$partner->wallet?(float)$partner->wallet->balance:0;
        $partner['total_earning'] =$partner->wallet?(float)$partner->wallet->total_earning:0;
        $partner['todays_earning'] =(float)$partner->todays_earning()->sum('delivery_company_amount');
        $partner['this_week_earning'] =(float)$partner->this_week_earning()->sum('delivery_company_amount');
        $partner['this_month_earning'] =(float)$partner->this_month_earning()->sum('delivery_company_amount');
        $partner["delivery_companies"] = $delivery_companies;
        if ($request['partner_employee']) {
            $partner_employee = $request['partner_employee'];
            $role = $partner_employee->role ? json_decode($partner_employee->role->modules):[];
            $partner["roles"] = $role;
            $partner["employee_info"] = json_decode($request['partner_employee']);
        }
        unset($partner['orders']);
        unset($partner['rating']);
        unset($partner['todaysorders']);
        unset($partner['this_week_orders']);
        unset($partner['wallet']);
        unset($partner['todaysorders']);
        unset($partner['this_week_orders']);
        unset($partner['this_month_orders']);

        return response()->json($partner, 200);
    }

    public function active_status(Request $request)
    {
        $delivery_company = $request->partner->delivery_companies[0];
        $delivery_company->active = $delivery_company->active?0:1;
        $delivery_company->save();
        return response()->json(['message' => $delivery_company->active?translate('messages.delivery_company_opened'):translate('messages.delivery_company_temporarily_closed')], 200);
    }

    public function get_earning_data(Request $request)
    {
        $partner = $request['partner'];
        $data= DeliveryCompanyLogic::get_earning_data($partner->id);
        return response()->json($data, 200);
    }

    public function update_profile(Request $request)
    {
        $partner = $request['partner'];
        $validator = Validator::make($request->all(), [
            'f_name' => 'required',
            'l_name' => 'required',
            'phone' => 'required|unique:partners,phone,'.$partner->id,
            'password'=>'nullable|min:6',
        ], [
            'f_name.required' => translate('messages.first_name_is_required'),
            'l_name.required' => translate('messages.Last name is required!'),
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $image = $request->file('image');

        if ($request->has('image')) {
            $imageName = Helpers::update('partner/', $partner->image, 'png', $request->file('image'));
        } else {
            $imageName = $partner->image;
        }

        if ($request['password'] != null && strlen($request['password']) > 5) {
            $pass = bcrypt($request['password']);
        } else {
            $pass = $partner->password;
        }
        $partner->f_name = $request->f_name;
        $partner->l_name = $request->l_name;
        $partner->phone = $request->phone;
        $partner->image = $imageName;
        $partner->password = $pass;
        $partner->updated_at = now();
        $partner->save();

        return response()->json(['message' => translate('messages.profile_updated_successfully')], 200);
    }

    public function get_current_orders(Request $request)
    {
        $partner = $request['partner'];

        $orders = Order::whereHas('delivery_company.partner', function($query) use($partner){
            $query->where('id', $partner->id);
        })
        ->with('customer')

        ->where(function($query)use($partner){
            if(config('order_confirmation_model') == 'delivery_company' || $partner->delivery_companies[0]->self_delivery_system)
            {
                $query->whereIn('order_status', ['accepted','pending','confirmed', 'processing', 'handover','picked_up']);
            }
            else
            {
                $query->whereIn('order_status', ['confirmed', 'processing', 'handover','picked_up'])
                ->orWhere(function($query){
                    $query->where('payment_status','paid')->where('order_status', 'accepted');
                })
                ->orWhere(function($query){
                    $query->where('order_status','pending')->where('order_type', 'take_away');
                });
            }
        })
        ->Notpos()
        ->orderBy('schedule_at', 'desc')
        ->get();
        $orders= Helpers::order_data_formatting($orders, true);
        return response()->json($orders, 200);
    }

    public function get_completed_orders(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required',
            'offset' => 'required',
            'status' => 'required|in:all,refunded,delivered',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $partner = $request['partner'];

        $paginator = Order::whereHas('delivery_company.partner', function($query) use($partner){
            $query->where('id', $partner->id);
        })
        ->with('customer')
        ->when($request->status == 'all', function($query){
            return $query->whereIn('order_status', ['refunded', 'delivered']);
        })
        ->when($request->status != 'all', function($query)use($request){
            return $query->where('order_status', $request->status);
        })
        ->Notpos()
        ->latest()
        ->paginate($request['limit'], ['*'], 'page', $request['offset']);
        $orders= Helpers::order_data_formatting($paginator->items(), true);
        $data = [
            'total_size' => $paginator->total(),
            'limit' => $request['limit'],
            'offset' => $request['offset'],
            'orders' => $orders
        ];
        return response()->json($data, 200);
    }

    public function get_canceled_orders(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required',
            'offset' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $partner = $request['partner'];

        $paginator = Order::whereHas('delivery_company.partner', function($query) use($partner){
            $query->where('id', $partner->id);
        })
        ->with('customer')
        ->where('order_status', 'canceled')
        ->Notpos()
        ->latest()
        ->paginate($request['limit'], ['*'], 'page', $request['offset']);
        $orders= Helpers::order_data_formatting($paginator->items(), true);
        $data = [
            'total_size' => $paginator->total(),
            'limit' => $request['limit'],
            'offset' => $request['offset'],
            'orders' => $orders
        ];
        return response()->json($data, 200);
    }

    public function update_order_status(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
            'reason' =>'required_if:status,canceled',
            'status' => 'required|in:confirmed,processing,handover,delivered,canceled'
        ]);

        $validator->sometimes('otp', 'required', function ($request) {
            return (Config::get('order_delivery_verification')==1 && $request['status']=='delivered');
        });

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $partner = $request['partner'];

        $order = Order::whereHas('delivery_company.partner', function($query) use($partner){
            $query->where('id', $partner->id);
        })
        ->where('id', $request['order_id'])
        ->Notpos()
        ->first();

        if($request['order_status']=='canceled')
        {
            if(!config('canceled_by_delivery_company'))
            {
                return response()->json([
                    'errors' => [
                        ['code' => 'status', 'message' => translate('messages.you_can_not_cancel_a_order')]
                    ]
                ], 403);
            }
            else if($order->confirmed)
            {
                return response()->json([
                    'errors' => [
                        ['code' => 'status', 'message' => translate('messages.you_can_not_cancel_after_confirm')]
                    ]
                ], 403);
            }
        }

        if($request['status'] =="confirmed" && !$partner->delivery_companies[0]->self_delivery_system && config('order_confirmation_model') == 'deliveryman' && $order->order_type != 'take_away')
        {
            return response()->json([
                'errors' => [
                    ['code' => 'order-confirmation-model', 'message' => translate('messages.order_confirmation_warning')]
                ]
            ], 403);
        }

        if($order->picked_up != null)
        {
            return response()->json([
                'errors' => [
                    ['code' => 'status', 'message' => translate('messages.You_can_not_change_status_after_picked_up_by_delivery_man')]
                ]
            ], 403);
        }

        if($request['status']=='delivered' && $order->order_type != 'take_away' && !$partner->delivery_companies[0]->self_delivery_system)
        {
            return response()->json([
                'errors' => [
                    ['code' => 'status', 'message' => translate('messages.you_can_not_delivered_delivery_order')]
                ]
            ], 403);
        }
        if(Config::get('order_delivery_verification')==1 && $request['status']=='delivered' && $order->otp != $request['otp'])
        {
            return response()->json([
                'errors' => [
                    ['code' => 'otp', 'message' => 'Not matched']
                ]
            ], 401);
        }

        if ($request->status == 'delivered' && $order->transaction == null) {
            if($order->payment_method == 'cash_on_delivery')
            {
                $ol = OrderLogic::create_transaction($order,'delivery_company', null);
            }
            else
            {
                $ol = OrderLogic::create_transaction($order,'admin', null);
            }

            $order->payment_status = 'paid';
        }

        if($request->status == 'delivered')
        {
            $order->details->each(function($item, $key){
                if($item->item)
                {
                    $item->item->increment('order_count');
                }
            });
            $order->customer->increment('order_count');
            $order->delivery_company->increment('order_count');
        }
        if($request->status == 'canceled' || $request->status == 'delivered')
        {
            if($order->delivery_man)
            {
                $dm = $order->delivery_man;
                $dm->current_orders = $dm->current_orders>1?$dm->current_orders-1:0;
                $dm->save();
            }
            $order->cancellation_reason=$request->reason;
            $order->canceled_by='delivery_company';
        }

        $order->order_status = $request['status'];
        $order[$request['status']] = now();
        $order->save();
        Helpers::send_order_notification($order);

        return response()->json(['message' => 'Status updated'], 200);
    }

    public function get_order_details(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $partner = $request['partner'];

        $order = Order::whereHas('delivery_company.partner', function($query) use($partner){
            $query->where('id', $partner->id);
        })
        ->with(['customer','details'])
        ->where('id', $request['order_id'])
        ->Notpos()
        ->first();
        if(!$order){
            return response()->json(['errors'=>[['code'=>'order_id', 'message'=>trans('messages.order_data_not_found')]]],404);
        }
        $details = isset($order->details)?$order->details:null;
        if ($details != null && $details->count() > 0) {
            $details = $details = Helpers::order_details_data_formatting($details);
            return response()->json($details, 200);
        } else if ($order->order_type == 'parcel' || $order->prescription_order == 1) {
            $order->delivery_address = json_decode($order->delivery_address, true);
            if($order->prescription_order && $order->order_attachment){
                $order->order_attachment = json_decode($order->order_attachment, true);
            }
            return response()->json(($order), 200);
        }

        return response()->json([
            'errors' => [
                ['code' => 'order', 'message' => translate('messages.not_found')]
            ]
        ], 404);
    }

    public function get_order(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $partner = $request['partner'];

        $order = Order::whereHas('delivery_company.partner', function($query) use($partner){
            $query->where('id', $partner->id);
        })
        ->with(['customer','details','delivery_man'])
        ->where('id', $request['order_id'])
        ->first();
        if(!$order){
            return response()->json(['errors'=>[['code'=>'order_id', 'message'=>trans('messages.order_data_not_found')]]],404);
        }
        return response()->json(Helpers::order_data_formatting($order),200);
    }

    public function get_all_orders(Request $request)
    {
        $partner = $request['partner'];

        $orders = Order::whereHas('delivery_company.partner', function($query) use($partner){
            $query->where('id', $partner->id);
        })
        ->with('customer')
        ->Notpos()
        ->orderBy('schedule_at', 'desc')
        ->get();
        $orders= Helpers::order_data_formatting($orders, true);
        return response()->json($orders, 200);
    }

    public function update_fcm_token(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fcm_token' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        if (!$request->hasHeader('partnerType')) {
            $errors = [];
            array_push($errors, ['code' => 'partner_type', 'message' => translate('messages.partner_type_required')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }
        $partner_type= $request->header('partnerType');
        $partner = $request['partner'];
        if($partner_type == 'owner'){
            Partner::where(['id' => $partner['id']])->update([
                'firebase_token' => $request['fcm_token']
            ]);
        }else{
            PartnerEmployee::where(['id' => $partner['id']])->update([
                'firebase_token' => $request['fcm_token']
            ]);

        }

        return response()->json(['message'=>'successfully updated!'], 200);
    }

    public function get_notifications(Request $request){
        $partner = $request['partner'];

        $notifications = Notification::active()->where(function($q) use($partner){
            $q->whereNull('zone_id')->orWhere('zone_id', $partner->delivery_companies[0]->zone_id);
        })->where('tergat', 'delivery_company')->where('created_at', '>=', \Carbon\Carbon::today()->subDays(7))->get();

        $notifications->append('data');

        $user_notifications = UserNotification::where('partner_id', $partner->id)->where('created_at', '>=', \Carbon\Carbon::today()->subDays(7))->get();

        $notifications =  $notifications->merge($user_notifications);

        try {
            return response()->json($notifications, 200);
        } catch (\Exception $e) {
            return response()->json([], 200);
        }
    }

    public function get_basic_campaigns(Request $request)
    {
        $partner = $request['partner'];
        $delivery_company_id = $partner->delivery_companies[0]->id;
        $module_id = $partner->delivery_companies[0]->module_id;

        $campaigns=Campaign::with('delivery_companies')->module($module_id)->Running()->latest()->get();
        $data = [];

        foreach ($campaigns as $item) {
            $delivery_company_ids = count($item->delivery_companies)?$item->delivery_companies->pluck('id')->toArray():[];
            $delivery_company_joining_status = count($item->delivery_companies)?$item->delivery_companies->pluck('pivot')->toArray():[];
            if($item->start_date)
            {
                $item['available_date_starts']=$item->start_date->format('Y-m-d');
                unset($item['start_date']);
            }
            if($item->end_date)
            {
                $item['available_date_ends']=$item->end_date->format('Y-m-d');
                unset($item['end_date']);
            }

            if (count($item['translations'])>0 ) {
                $translate = array_column($item['translations']->toArray(), 'value', 'key');
                $item['title'] = $translate['title'];
                $item['description'] = $translate['description'];
            }

            $item['partner_status'] = null;
            foreach($delivery_company_joining_status as $status){
                if($status['delivery_company_id'] == $delivery_company_id){
                    $item['partner_status'] =  $status['campaign_status'];
                }

            }

            $item['is_joined'] = in_array($delivery_company_id, $delivery_company_ids)?true:false;
            unset($item['delivery_companies']);
            array_push($data, $item);
        }
        // $data = CampaignLogic::get_basic_campaigns($partner->delivery_companies[0]->id, $request['limite'], $request['offset']);
        return response()->json($data, 200);
    }

    public function remove_delivery_company(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'campaign_id' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $campaign = Campaign::where('status', 1)->find($request->campaign_id);
        if(!$campaign)
        {
            return response()->json([
                'errors'=>[
                    ['code'=>'campaign', 'message'=>'Campaign not found or upavailable!']
                ]
            ]);
        }
        $delivery_company = $request['partner']->delivery_companies[0];
        $campaign->delivery_companies()->detach($delivery_company);
        $campaign->save();
        return response()->json(['message'=>translate('messages.you_are_successfully_removed_from_the_campaign')], 200);
    }
    public function adddelivery_company(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'campaign_id' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $campaign = Campaign::where('status', 1)->find($request->campaign_id);
        if(!$campaign)
        {
            return response()->json([
                'errors'=>[
                    ['code'=>'campaign', 'message'=>'Campaign not found or upavailable!']
                ]
            ]);
        }
        $delivery_company = $request['partner']->delivery_companies[0];
        $campaign->delivery_companies()->attach($delivery_company);
        $campaign->save();
        return response()->json(['message'=>translate('messages.you_are_successfully_joined_to_the_campaign')], 200);
    }

    public function get_items(Request $request)
    {
        $limit=$request->limit?$request->limit:25;
        $offset=$request->offset?$request->offset:1;

        $type = $request->query('type', 'all');

        $paginator = Item::withoutGlobalScope('translate')->with('tags')->type($type)->where('delivery_company_id', $request['partner']->delivery_companies[0]->id)->latest()->paginate($limit, ['*'], 'page', $offset);
        $data = [
            'total_size' => $paginator->total(),
            'limit' => $limit,
            'offset' => $offset,
            'items' => Helpers::product_data_formatting($paginator->items(), true, true, app()->getLocale())
        ];

        return response()->json($data, 200);
    }

    public function update_bank_info(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bank_name' => 'required|max:191',
            'branch' => 'required|max:191',
            'holder_name' => 'required|max:191',
            'account_no' => 'required|max:191'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $bank = $request['partner'];
        $bank->bank_name = $request->bank_name;
        $bank->branch = $request->branch;
        $bank->holder_name = $request->holder_name;
        $bank->account_no = $request->account_no;
        $bank->save();

        return response()->json(['message'=>translate('messages.bank_info_updated_successfully'),200]);
    }

    public function withdraw_list(Request $request)
    {
        $withdraw_req = WithdrawRequest::where('partner_id', $request['partner']->id)->latest()->get();

        $temp = [];
        $status = [
            0=>'Pending',
            1=>'Approved',
            2=>'Denied'
        ];
        foreach($withdraw_req as $item)
        {
            $item['status'] = $status[$item->approved];
            $item['requested_at'] = $item->created_at->format('Y-m-d H:i:s');
            $item['bank_name'] = $request['partner']->bank_name;
            unset($item['created_at']);
            unset($item['approved']);
            $temp[] = $item;
        }

        return response()->json($temp, 200);
    }

    public function request_withdraw(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $w = $request['partner']->wallet;
        if ($w->balance >= $request['amount']) {
            $data = [
                'partner_id' => $w->partner_id,
                'amount' => $request['amount'],
                'transaction_note' => null,
                'approved' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ];
            try
            {
                DB::table('withdraw_requests')->insert($data);
                $w->increment('pending_withdraw', $request['amount']);
                return response()->json(['message'=>translate('messages.withdraw_request_placed_successfully')],200);
            }
            catch(\Exception $e)
            {
                return response()->json($e);
            }
        }
        return response()->json([
            'errors'=>[
                ['code'=>'amount', 'message'=>translate('messages.insufficient_balance')]
            ]
        ],403);
    }

    public function remove_account(Request $request)
    {
        $partner = $request['partner'];

        if(Order::where('delivery_company_id', $partner->delivery_companies[0]->id)->whereIn('order_status', ['pending','accepted','confirmed','processing','handover','picked_up'])->count())
        {
            return response()->json(['errors'=>[['code'=>'on-going', 'message'=>translate('messages.user_account_delete_warning')]]],203);
        }

        if($partner->wallet && $partner->wallet->collected_cash > 0)
        {
            return response()->json(['errors'=>[['code'=>'on-going', 'message'=>translate('messages.user_account_wallet_delete_warning')]]],203);
        }

        if (Storage::disk('public')->exists('partner/' . $partner['image'])) {
            Storage::disk('public')->delete('partner/' . $partner['image']);
        }
        if (Storage::disk('public')->exists('delivery_company/' . $partner->delivery_companies[0]->logo)) {
            Storage::disk('public')->delete('delivery_company/' . $partner->delivery_companies[0]->logo);
        }

        if (Storage::disk('public')->exists('delivery_company/cover/' . $partner->delivery_companies[0]->cover_photo)) {
            Storage::disk('public')->delete('delivery_company/cover/' . $partner->delivery_companies[0]->cover_photo);
        }
        foreach($partner->delivery_companies[0]->deliverymen as $dm) {
            if (Storage::disk('public')->exists('delivery-man/' . $dm['image'])) {
                Storage::disk('public')->delete('delivery-man/' . $dm['image']);
            }

            foreach (json_decode($dm['identity_image'], true) as $img) {
                if (Storage::disk('public')->exists('delivery-man/' . $img)) {
                    Storage::disk('public')->delete('delivery-man/' . $img);
                }
            }
        }
        $partner->delivery_companies[0]->deliverymen()->delete();
        $partner->delivery_companies()->delete();
        if($partner->userinfo){
            $partner->userinfo->delete();
        }
        $partner->delete();
        return response()->json([]);
    }
    public function edit_order_amount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        if($request->order_amount){
            $partner = $request['partner'];
            $partner_delivery_company = Helpers::delivery_company_data_formatting($partner->delivery_companies[0], false);
            $order = Order::find($request->order_id);
            if ($order->delivery_company_id != $partner_delivery_company->id) {
                return response()->json([
                    'errors' => [
                        ['code' => 'order', 'message' => translate('Order not found')]
                    ]
                ], 403);
            }
            $delivery_company = DeliveryCompany::find($order->delivery_company_id);
            $coupon = null;
            $free_delivery_by = null;
            if ($order->coupon_code) {
                $coupon = Coupon::active()->where(['code' => $order->coupon_code])->first();
                if (isset($coupon)) {
                    $staus = CouponLogic::is_valide($coupon, $order->user_id, $order->delivery_company_id);
                    if ($staus == 407) {
                        return response()->json([
                            'errors' => [
                                ['code' => 'coupon', 'message' => translate('messages.coupon_expire')]
                            ]
                        ], 407);
                    } else if ($staus == 406) {
                        return response()->json([
                            'errors' => [
                                ['code' => 'coupon', 'message' => translate('messages.coupon_usage_limit_over')]
                            ]
                        ], 406);
                    } else if ($staus == 404) {
                        return response()->json([
                            'errors' => [
                                ['code' => 'coupon', 'message' => translate('messages.not_found')]
                            ]
                        ], 404);
                    }
                } else {
                    return response()->json([
                        'errors' => [
                            ['code' => 'coupon', 'message' => translate('messages.not_found')]
                        ]
                    ], 404);
                }
            }
            $product_price = $request->order_amount;
            $total_addon_price = 0;
            $delivery_company_discount_amount = $order->delivery_company_discount_amount;
            if($delivery_company_discount_amount == 0){
                $delivery_company_discount = Helpers::get_delivery_company_discount($delivery_company);
                if (isset($delivery_company_discount)) {
                    if ($product_price + $total_addon_price < $delivery_company_discount['min_purchase']) {
                        $delivery_company_discount_amount = 0;
                    }

                    if ($delivery_company_discount['max_discount'] != 0 && $delivery_company_discount_amount > $delivery_company_discount['max_discount']) {
                        $delivery_company_discount_amount = $delivery_company_discount['max_discount'];
                    }
                }
            }

            $coupon_discount_amount = $coupon ? CouponLogic::get_discount($coupon, $product_price + $total_addon_price - $delivery_company_discount_amount) : 0;
            $total_price = $product_price + $total_addon_price - $delivery_company_discount_amount - $coupon_discount_amount;

            $tax = ($delivery_company->tax > 0)?$delivery_company->tax:0;
            $order->tax_status = 'excluded';

            $tax_included =BusinessSetting::where(['key'=>'tax_included'])->first() ?  BusinessSetting::where(['key'=>'tax_included'])->first()->value : 0;
            if ($tax_included ==  1){
                $order->tax_status = 'included';
            }

            $total_tax_amount=Helpers::product_tax($total_price,$tax,$order->tax_status =='included');

            $tax_a=$order->tax_status =='included'?0:$total_tax_amount;

            $free_delivery_over = BusinessSetting::where('key', 'free_delivery_over')->first()->value;
            if (isset($free_delivery_over)) {
                if ($free_delivery_over <= $product_price + $total_addon_price - $coupon_discount_amount - $delivery_company_discount_amount) {
                    $order->delivery_charge = 0;
                    $free_delivery_by = 'admin';
                }
            }

            if ($delivery_company->free_delivery) {
                $order->delivery_charge = 0;
                $free_delivery_by = 'partner';
            }

            if ($coupon) {
                if ($coupon->coupon_type == 'free_delivery') {
                    if ($coupon->min_purchase <= $product_price + $total_addon_price - $delivery_company_discount_amount) {
                        $order->delivery_charge = 0;
                        $free_delivery_by = 'admin';
                    }
                }
                $coupon->increment('total_uses');
            }

            $order->coupon_discount_amount = round($coupon_discount_amount, config('round_up_to_digit'));
            $order->coupon_discount_title = $coupon ? $coupon->title : '';

            $order->delivery_company_discount_amount = round($delivery_company_discount_amount, config('round_up_to_digit'));
            $order->total_tax_amount = round($total_tax_amount, config('round_up_to_digit'));
            $order->order_amount = round($total_price + $tax_a + $order->delivery_charge, config('round_up_to_digit'));
            $order->free_delivery_by = $free_delivery_by;
            $order->order_amount = $order->order_amount + $order->dm_tips;
            $order->save();
        }

        if($request->discount_amount){
            $partner = $request['partner'];
            $partner_delivery_company = Helpers::delivery_company_data_formatting($partner->delivery_companies[0], false);
            $order = Order::find($request->order_id);
            if ($order->delivery_company_id != $partner_delivery_company->id) {
                return response()->json([
                    'errors' => [
                        ['code' => 'order', 'message' => translate('Order not found')]
                    ]
                ], 403);
            }
            $order = Order::find($request->order_id);
            $product_price = $order['order_amount']-$order['delivery_charge']-$order['total_tax_amount']-$order['dm_tips']+$order->delivery_company_discount_amount;
            if($request->discount_amount > $product_price)
            {
                return response()->json([
                    'errors' => [
                        ['code' => 'order', 'message' => translate('messages.discount_amount_is_greater_then_product_amount')]
                    ]
                ], 403);
            }
            $order->delivery_company_discount_amount = round($request->discount_amount, config('round_up_to_digit'));
            $order->order_amount = $product_price+$order['delivery_charge']+$order['total_tax_amount']+$order['dm_tips'] -$order->delivery_company_discount_amount;
            $order->save();
        }


        return response()->json(['message'=>translate('messages.order_updated_successfully')],200);
    }
}
