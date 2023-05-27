<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryCompanySchedule extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'delivery_company_schedule';

    protected $casts = [
        'day'=>'integer',
        'company_id'=>'integer',
    ];

    protected $fillable = ['company_id','day','opening_time','closing_time'];

    public function delivery_company()
    {
        return $this->belongsTo(DeliveryCompany::class);
    }
}
