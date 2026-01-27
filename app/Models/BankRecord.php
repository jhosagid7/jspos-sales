<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_id',
        'payment_date',
        'amount',
        'reference',
        'image_path',
        'note',
        'sale_id',
        'payment_id',
        'status', 
        'remaining_balance'
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'remaining_balance' => 'decimal:2',
    ];

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }
}
