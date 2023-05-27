<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class PartnerEmployee extends Authenticatable
{
    use Notifiable;

    protected $hidden = [
        'password',
        'auth_token',
        'remember_token',
    ];

    public function delivery_company()
    {
        return $this->belongsTo(DeliveryCompany::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Partner::class);
    }

    public function role(){
        return $this->belongsTo(EmployeeRole::class,'employee_role_id');
    }
}
