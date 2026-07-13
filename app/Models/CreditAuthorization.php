<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class CreditAuthorization extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'requested_by_id',
        'approved_by_id',
        'pin_code',
        'status', // 'pending', 'used', 'expired'
        'amount_requested',
        'sale_id',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'amount_requested' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function isExpired()
    {
        return $this->expires_at && Carbon::now()->greaterThan($this->expires_at);
    }
}
