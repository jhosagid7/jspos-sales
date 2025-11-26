<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashRegisterDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'cash_register_id',
        'currency_code',
        'type', // opening, closing
        'amount',
        'amount_in_primary_currency',
        'exchange_rate',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_in_primary_currency' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
    ];

    public function cashRegister()
    {
        return $this->belongsTo(CashRegister::class);
    }
}
