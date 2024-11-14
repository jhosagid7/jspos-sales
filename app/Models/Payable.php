<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payable extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'purchase_id', 'amount', 'pay_way', 'type', 'bank', 'account_number', 'deposit_number', 'phone_number'];

    function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    function payables()
    {
        return $this->hasMany(Payable::class)->orderBy('id', 'desc');
    }
}
