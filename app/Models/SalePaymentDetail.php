<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalePaymentDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'payment_method',
        'currency_code',
        'bank_name',
        'account_number',
        'reference_number',
        'phone_number',
        'amount',
        'exchange_rate',
        'amount_in_primary_currency'
    ];

    protected $casts = [
        'amount' => 'float',
        'exchange_rate' => 'float',
        'amount_in_primary_currency' => 'float',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
