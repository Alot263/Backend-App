<?php

namespace App\CentralLogics;

use App\Models\DeliveryCompany;
use App\Models\DeliveryCompanySchedule;
use App\Models\OrderTransaction;
use Exception;

class DeliveryCompanyLogic
{
    public static function get_delivery_companies( $zone_id, $filter, $type, $limit = 10, $offset = 1, $featured=false,$longitude=0,$latitude=0): array
    {
        $paginator = DeliveryCompany::
        withOpen($longitude,$latitude)
        ->with(['discount'=>function($q){
            return $q->validate();
        }])
        ->whereHas('module',function($query){
            $query->active();
        })
        ->when($filter=='delivery', function($q){
            return $q->delivery();
        })
        ->when($filter=='take_away', function($q){
            return $q->takeaway();
        })
        ->when($featured, function($query){
            $query->featured();
        });
        if(config('module.current_module_data')) {
            $paginator = $paginator->whereHas('zone.modules', function($query){
                $query->where('modules.id', config('module.current_module_data')['id']);
            })->module(config('module.current_module_data')['id'])
            ->when(!config('module.current_module_data')['all_zone_service'], function($query)use($zone_id){
                $query->whereIn('zone_id', json_decode($zone_id,true));
            });
        } else {
            $paginator = $paginator->whereIn('zone_id', json_decode($zone_id,true));
        }
        $paginator = $paginator->Active()
        ->type($type)
        ->orderBy('open', 'desc')
        ->orderBy('distance')
        ->paginate($limit, ['*'], 'page', $offset);
        /*$paginator->count();*/
        return [
            'total_size' => $paginator->total(),
            'limit' => $limit,
            'offset' => $offset,
            'delivery_companies' => $paginator->items()
        ];
    }

    public static function get_latest_delivery_companies($zone_id, $limit = 10, $offset = 1, $type='all',$longitude=0,$latitude=0): array
    {
        $paginator = DeliveryCompany::withOpen($longitude,$latitude)
        ->with(['discount'=>function($q){
            return $q->validate();
        }])
        ->when(config('module.current_module_data'), function($query)use($zone_id){
            $query->whereHas('zone.modules', function($query){
                $query->where('modules.id', config('module.current_module_data')['id']);
            })->module(config('module.current_module_data')['id']);
            if(!config('module.current_module_data')['all_zone_service']) {
                $query->whereIn('zone_id', json_decode($zone_id, true));
            }
        })
        ->Active()
        ->type($type)
        ->latest()
        ->limit(50)
        ->get();

        return [
            'total_size' => $paginator->count(),
            'limit' => $limit,
            'offset' => $offset,
            'delivery_company' => $paginator
        ];
    }

    public static function get_popular_delivery_companies($zone_id, $limit = 10, $offset = 1, $type = 'all',$longitude=0,$latitude=0): array
    {
        $paginator = DeliveryCompany::withOpen($longitude,$latitude)
        ->with(['discount'=>function($q){
            return $q->validate();
        }])
        ->when(config('module.current_module_data'), function($query)use($zone_id){
            $query->whereHas('zone.modules', function($query){
                $query->where('modules.id', config('module.current_module_data')['id']);
            })->module(config('module.current_module_data')['id']);
            if(!config('module.current_module_data')['all_zone_service']) {
                $query->whereIn('zone_id', json_decode($zone_id, true));
            }
        })
        ->Active()
        ->type($type)
        ->withCount('orders')
        ->orderBy('orders_count', 'desc')
        ->limit(50)
        ->get();

        return [
            'total_size' => $paginator->count(),
            'limit' => $limit,
            'offset' => $offset,
            'delivery_companies' => $paginator
        ];
    }

    public static function get_delivery_company_details($delivery_company_id)
    {
        return DeliveryCompany::with(['discount'=>function($q){
            return $q->validate();
        }, 'campaigns', 'schedules'])
        ->when(config('module.current_module_data'), function($query){
            $query->module(config('module.current_module_data')['id']);
        })
        ->when(is_numeric($delivery_company_id),function ($qurey) use($delivery_company_id){
            $qurey->where('id', $delivery_company_id);
        })
        ->when(!is_numeric($delivery_company_id),function ($qurey) use($delivery_company_id){
            $qurey->where('slug', $delivery_company_id);
        })
        ->first();
    }

    public static function calculate_delivery_company_rating($ratings): array
    {
        $total_submit = $ratings[0]+$ratings[1]+$ratings[2]+$ratings[3]+$ratings[4];
        $rating = ($ratings[0]*5+$ratings[1]*4+$ratings[2]*3+$ratings[3]*2+$ratings[4])/($total_submit?$total_submit:1);
        return ['rating'=>$rating, 'total'=>$total_submit];
    }

    public static function update_delivery_company_rating($ratings, $product_rating)
    {
        $delivery_company_ratings = [1=>0 , 2=>0, 3=>0, 4=>0, 5=>0];
        if($ratings)
        {
            $delivery_company_ratings[1] = $ratings[4];
            $delivery_company_ratings[2] = $ratings[3];
            $delivery_company_ratings[3] = $ratings[2];
            $delivery_company_ratings[4] = $ratings[1];
            $delivery_company_ratings[5] = $ratings[0];
            $delivery_company_ratings[$product_rating] = $ratings[5-$product_rating] + 1;
        }
        else
        {
            $delivery_company_ratings[$product_rating] = 1;
        }
        return json_encode($delivery_company_ratings);
    }

    public static function search_delivery_companies($name, $zone_id, $category_id= null,$limit = 10, $offset = 1, $type = 'all',$longitude=0,$latitude=0): array
    {
        $key = explode(' ', $name);
        $paginator = DeliveryCompany::whereHas('zone.modules', function($query){
            $query->where('modules.id', config('module.current_module_data')['id']);
        })->withOpen($longitude,$latitude)->with(['discount'=>function($q){
            return $q->validate();
        }])->weekday()->where(function ($q) use ($key) {
            foreach ($key as $value) {
                $q->orWhere('name', 'like', "%{$value}%");
            }
        })
        ->when(config('module.current_module_data'), function($query)use($zone_id){
            $query->module(config('module.current_module_data')['id']);
            if(!config('module.current_module_data')['all_zone_service']) {
                $query->whereIn('zone_id', json_decode($zone_id, true));
            }
        })
        ->when($category_id, function($query)use($category_id){
            $query->whereHas('items.category', function($q)use($category_id){
                return $q->whereId($category_id)->orWhere('parent_id', $category_id);
            });
        })
        ->active()->orderBy('open', 'desc')->orderBy('distance')->type($type)->paginate($limit, ['*'], 'page', $offset);

        return [
            'total_size' => $paginator->total(),
            'limit' => $limit,
            'offset' => $offset,
            'delivery_companies' => $paginator->items()
        ];
    }

    public static function get_overall_rating($reviews): array
    {
        $totalRating = count($reviews);
        $rating = 0;
        foreach ($reviews as $key => $review) {
            $rating += $review->rating;
        }
        if ($totalRating == 0) {
            $overallRating = 0;
        } else {
            $overallRating = number_format($rating / $totalRating, 2);
        }

        return [$overallRating, $totalRating];
    }

    public static function get_earning_data($vendor_id)
    {
        $monthly_earning = OrderTransaction::whereMonth('created_at', date('m'))->NotRefunded()->where('partner_id', $vendor_id)->sum('delivery_company_amount');
        $weekly_earning = OrderTransaction::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->NotRefunded()->where('partner_id', $vendor_id)->sum('delivery_company_amount');
        $daily_earning = OrderTransaction::whereDate('created_at', now())->NotRefunded()->where('partner_id', $vendor_id)->sum('delivery_company_amount');

        return['monthely_earning'=>(float)$monthly_earning, 'weekly_earning'=>(float)$weekly_earning, 'daily_earning'=>(float)$daily_earning];
    }


    public static function format_export_delivery_companies($delivery_companies): array
    {
        $storage = [];
        foreach($delivery_companies as $item)
        {
            if($item->delivery_companies->count()<1)
            {
                break;
            }
            $storage[] = [
                'id'=>$item->id,
                'ownerFirstName'=>$item->f_name,
                'ownerLastName'=>$item->l_name,
                'deliveryCompanyName'=>$item->delivery_companies[0]->name,
                'logo'=>$item->delivery_companies[0]->logo,
                'phone'=>$item->phone,
                'email'=>$item->email,
                'delivery_time'=>$item->delivery_time,
                'latitude'=>$item->delivery_companies[0]->latitude,
                'longitude'=>$item->delivery_companies[0]->longitude,
                'zone_id'=>$item->delivery_companies[0]->zone_id,
                'module_id'=>$item->delivery_companies[0]->module_id,
            ];
        }

        return $storage;
    }

    public static function insert_schedule(int $delivery_company_id, array $days=[0,1,2,3,4,5,6], String $opening_time='00:00:00', String $closing_time='23:59:59')
    {
        $data = array_map(function($item)use($delivery_company_id, $opening_time, $closing_time){
            return     ['delivery_company_id'=>$delivery_company_id,'day'=>$item,'opening_time'=>$opening_time,'closing_time'=>$closing_time];
        },$days);
        try{
            DeliveryCompanySchedule::upsert($data,['delivery_company_id','day','opening_time','closing_time']);
            return true;
        }catch(Exception $e)
        {
            return $e;
        }
        return false;

    }

    public static function format_delivery_company_sales_export_data($items): array
    {
        $data = [];
        foreach($items as $key=>$item)
        {

            $data[]=[
                '#'=>$key+1,
                translate('messages.name')=>$item->name,
                translate('messages.quantity')=>$item->orders->sum('quantity'),
                translate('messages.gross_sale')=>$item->orders->sum('price'),
                translate('messages.discount_given')=>$item->orders->sum('discount_on_item'),

            ];
        }
        return $data;
    }

    public static function format_delivery_company_summary_export_data($delivery_companies): array
    {
        $data = [];
        foreach($delivery_companies as $key=>$delivery_company)
        {
            $delivered = $delivery_company->orders->where('order_status', 'delivered')->count();
            $canceled = $delivery_company->orders->where('order_status', 'canceled')->count();
            $refunded = $delivery_company->orders->where('order_status', 'refunded')->count();
            $total = $delivery_company->orders->count();
            $refund_requested = $delivery_company->orders->whereNotNull('refund_requested')->count();
            $data[]=[
                '#'=>$key+1,
                translate('delivery_company')=>$delivery_company->name,
                translate('Total Order')=>$total,
                translate('Delivered Order')=>$delivered,
                translate('Total Amount')=>$delivery_company->orders->where('order_status','delivered')->sum('order_amount'),
                translate('Completion Rate')=>($delivery_company->orders->count() > 0 && $delivered > 0)? number_format((100*$delivered)/$delivery_company->orders->count(), config('round_up_to_digit')): 0,
                translate('Ongoing Rate')=>($delivery_company->orders->count() > 0 && $delivered > 0)? number_format((100*($delivery_company->orders->count()-($delivered+$canceled)))/$delivery_company->orders->count(), config('round_up_to_digit')): 0,
                translate('Cancelation Rate')=>($delivery_company->orders->count() > 0 && $canceled > 0)? number_format((100*$canceled)/$delivery_company->orders->count(), config('round_up_to_digit')): 0,
                translate('Refund Request')=>$refunded,

            ];
        }
        return $data;
    }
}
