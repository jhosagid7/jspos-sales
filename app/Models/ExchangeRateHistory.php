<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExchangeRateHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'rate_type', // 'BCV', 'Binance'
        'rate',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
