<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'cash_register_id',
        'sale_id',
        'type', // opening, sale_payment, sale_change, adjustment, closing
        'currency_code',
        'amount',
        'amount_in_primary_currency',
        'balance_after',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_in_primary_currency' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function cashRegister()
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
