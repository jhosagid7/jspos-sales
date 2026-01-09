<?php

namespace App\Models;

use App\Models\Delivery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'city',
        'email',
        'phone',
        'taxpayer_id',
        'type',
        'seller_id',
        'customer_commission_1_threshold',
        'customer_commission_1_percentage',
        'customer_commission_1_percentage',
        'customer_commission_2_threshold',
        'customer_commission_2_percentage',
        'zone'
    ];

    function sales()
    {
        return $this->hasMany(Sale::class);
    }

    function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
}
