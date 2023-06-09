<?php

namespace App\Models;

use App\Scopes\StoreScope;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $casts = [
        'min_purchase' => 'float',
        'max_discount' => 'float',
        'discount' => 'float',
        'limit'=>'integer',
        'store_id'=>'integer',
        // 'customer_id'=>'integer',
        'status'=>'integer',
        'id'=>'integer',
        'total_uses'=>'integer',
    ];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class, 'store_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', '=', 1);
    }

    public function scopeModule($query, $module_id)
    {
        return $query->where('module_id', $module_id);
    }

    // protected static function booted()
    // {
    //     if(auth('vendor')->check())
    //     {
    //         static::addGlobalScope(new StoreScope);
    //     }
    // }
}
