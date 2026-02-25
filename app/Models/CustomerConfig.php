<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'commission_percent',
        'freight_percent',
        'exchange_diff_percent',
        'current_batch',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
