<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'sale_id', 'amount', 'currency', 'exchange_rate', 'primary_exchange_rate', 'pay_way', 'type', 'bank', 'account_number', 'deposit_number', 'phone_number', 'payment_date'];

    function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    function payments()
    {
        return $this->hasMany(Payment::class)->orderBy('id', 'desc');
    }
}
