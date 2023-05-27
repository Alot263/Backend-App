<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryCompanyWallet extends Model
{
    use HasFactory;

     /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['partner_id'];

    public function getBalanceAttribute()
    {
        return $this->total_earning - ($this->total_withdrawn + $this->pending_withdraw + $this->collected_cash);
    }
}
