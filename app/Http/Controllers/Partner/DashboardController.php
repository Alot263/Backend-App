<?php

namespace App\Http\Controllers\Partner;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Order;
use App\Models\Partner;
use App\Models\OrderTransaction;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        $params = [
            'statistics_type' => $request['statistics_type'] ?? 'overall'
        ];
        session()->put('dash_params', $params);

        $data = self::dashboard_order_stats_data();
        $earning = [];
        $commission = [];
        $from = Carbon::now()->startOfYear()->format('Y-m-d');
        $to = Carbon::now()->endOfYear()->format('Y-m-d');
        $delivery_company_earnings = OrderTransaction::NotRefunded()->where(['partner_id' => Helpers::get_partner_id()])->select(
            DB::raw('IFNULL(sum(delivery_company_amount),0) as earning'),
            DB::raw('IFNULL(sum(admin_commission + admin_expense - delivery_fee_comission),0) as commission'),
            DB::raw('YEAR(created_at) year, MONTH(created_at) month')
        )->whereBetween('created_at', [$from, $to])->groupby('year', 'month')->get()->toArray();
        for ($inc = 1; $inc <= 12; $inc++) {
            $earning[$inc] = 0;
            $commission[$inc] = 0;
            foreach ($delivery_company_earnings as $match) {
                if ($match['month'] == $inc) {
                    $earning[$inc] = $match['earning'];
                    $commission[$inc] = $match['commission'];
                }
            }
        }

        $top_sell = Item::orderBy("order_count", 'desc')
            ->take(6)
            ->get();
        $most_rated_items = Item::
        orderBy('rating_count','desc')
        ->take(6)
        ->get();
        $data['top_sell'] = $top_sell;
        $data['most_rated_items'] = $most_rated_items;

        return view('delivery-partner-views.dashboard', compact('data', 'earning', 'commission', 'params'));
    }

    public function delivery_company_data(): JsonResponse
    {
        $new_pending_order = DB::table('orders')->where(['checked' => 0])->where('delivery_company_id', Helpers::get_delivery_company_id())->where('order_status','pending');;
        if(config('order_confirmation_model') != 'delivery_company' && !Helpers::get_delivery_company_data()->self_delivery_system)
        {
            $new_pending_order = $new_pending_order->where('order_type', 'take_away');
        }
        $new_pending_order = $new_pending_order->count();
        $new_confirmed_order = DB::table('orders')->where(['checked' => 0])->where('delivery_company_id', Helpers::get_delivery_company_id())->whereIn('order_status',['confirmed', 'accepted'])->whereNotNull('confirmed')->count();

        return response()->json([
            'success' => 1,
            'data' => ['new_pending_order' => $new_pending_order, 'new_confirmed_order' => $new_confirmed_order]
        ]);
    }

    public function order_stats(Request $request)
    {
        $params = session('dash_params');
        foreach ($params as $key => $value) {
            if ($key == 'statistics_type') {
                $params['statistics_type'] = $request['statistics_type'];
            }
        }
        session()->put('dash_params', $params);

        $data = self::dashboard_order_stats_data();
        return response()->json([
            'view' => view('delivery-partner-views.partials._dashboard-order-stats', compact('data'))->render()
        ], 200);
    }

    public function dashboard_order_stats_data(): array
    {
        $params = session('dash_params');
        $today = $params['statistics_type'] == 'today' ? 1 : 0;
        $this_month = $params['statistics_type'] == 'this_month' ? 1 : 0;

        $confirmed = Order::when($today, function ($query) {
            return $query->whereDate('created_at', Carbon::today());
        })->when($this_month, function ($query) {
            return $query->whereMonth('created_at', Carbon::now());
        })->where(['delivery_company_id' => Helpers::get_delivery_company_id()])->whereIn('order_status',['confirmed', 'accepted'])->whereNotNull('confirmed')->StoreOrder()->OrderScheduledIn(30)->count();

        $cooking = Order::when($today, function ($query) {
            return $query->whereDate('created_at', Carbon::today());
        })->when($this_month, function ($query) {
            return $query->whereMonth('created_at', Carbon::now());
        })->where(['order_status' => 'processing', 'delivery_company_id' => Helpers::get_delivery_company_id()])->StoreOrder()->count();

        $ready_for_delivery = Order::when($today, function ($query) {
            return $query->whereDate('created_at', Carbon::today());
        })->when($this_month, function ($query) {
            return $query->whereMonth('created_at', Carbon::now());
        })->where(['order_status' => 'handover', 'delivery_company_id' => Helpers::get_delivery_company_id()])->StoreOrder()->count();

        $item_on_the_way = Order::when($today, function ($query) {
            return $query->whereDate('created_at', Carbon::today());
        })->when($this_month, function ($query) {
            return $query->whereMonth('created_at', Carbon::now());
        })->ItemOnTheWay()->where(['delivery_company_id' => Helpers::get_delivery_company_id()])->StoreOrder()->count();

        $delivered = Order::when($today, function ($query) {
            return $query->whereDate('created_at', Carbon::today());
        })->when($this_month, function ($query) {
            return $query->whereMonth('created_at', Carbon::now());
        })->where(['order_status' => 'delivered', 'delivery_company_id' => Helpers::get_delivery_company_id()])->StoreOrder()->count();

        $refunded = Order::when($today, function ($query) {
            return $query->whereDate('created_at', Carbon::today());
        })->when($this_month, function ($query) {
            return $query->whereMonth('created_at', Carbon::now());
        })->where(['order_status' => 'refunded', 'delivery_company_id' => Helpers::get_delivery_company_id()])->StoreOrder()->count();

        $scheduled = Order::when($today, function ($query) {
            return $query->whereDate('created_at', Carbon::today());
        })->when($this_month, function ($query) {
            return $query->whereMonth('created_at', Carbon::now());
        })->Scheduled()->where(['delivery_company_id' => Helpers::get_delivery_company_id()])->where(function($q){
            if(config('order_confirmation_model') == 'delivery_company')
            {
                $q->whereNotIn('order_status',['failed','canceled', 'refund_requested', 'refunded']);
            }
            else
            {
                $q->whereNotIn('order_status',['pending','failed','canceled', 'refund_requested', 'refunded'])->orWhere(function($query){
                    $query->where('order_status','pending')->where('order_type', 'take_away');
                });
            }

        })->StoreOrder()->count();

        $all = Order::when($today, function ($query) {
            return $query->whereDate('created_at', Carbon::today());
        })->when($this_month, function ($query) {
            return $query->whereMonth('created_at', Carbon::now());
        })->where(['delivery_company_id' => Helpers::get_delivery_company_id()])
        ->where(function($query){
            return $query->whereNotIn('order_status',(config('order_confirmation_model') == 'delivery_company'|| \App\CentralLogics\Helpers::get_delivery_company_data()->self_delivery_system)?['failed','canceled', 'refund_requested', 'refunded']:['pending','failed','canceled', 'refund_requested', 'refunded'])
            ->orWhere(function($query){
                return $query->where('order_status','pending')->where('order_type', 'take_away');
            });
        })
        ->StoreOrder()->count();

        $data = [
            'confirmed' => $confirmed,
            'cooking' => $cooking,
            'ready_for_delivery' => $ready_for_delivery,
            'item_on_the_way' => $item_on_the_way,
            'delivered' => $delivered,
            'refunded' => $refunded,
            'scheduled' => $scheduled,
            'all' => $all,
        ];

        return $data;
    }

    public function updateDeviceToken(Request $request)
    {
        $partner = Partner::find(Helpers::get_partner_id());
        $partner->firebase_token =  $request->token;

        $partner->save();

        return response()->json(['Token successfully delivery_companyd.']);
    }
}
