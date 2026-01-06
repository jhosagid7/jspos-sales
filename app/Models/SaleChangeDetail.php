<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleChangeDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'currency_code',
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
