<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExchangeRateApproval extends Model
{
    use HasFactory;

    protected $table = 'exchange_rate_approvals';

    protected $fillable = [
        'user_id',
        'approver_id',
        'sale_id',
        'custom_rate',
        'reason',
        'status', // 'pending', 'approved', 'rejected', 'used'
        'token',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }
}
