<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellerConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'commission_percent',
        'freight_percent',
        'exchange_diff_percent',
        'current_batch'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
