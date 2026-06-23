<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DebitNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'debit_number',
        'customer_id',
        'user_id',
        'sale_id',
        'amount',
        'concept',
        'currency',
        'exchange_rate',
        'status'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function getDebtAttribute()
    {
        $totalPaidUSD = $this->payments->whereNotIn('status', ['pending', 'rejected', 'voided'])->sum(function($payment) {
            $rate = $payment->exchange_rate > 0 ? $payment->exchange_rate : 1;
            return $payment->amount / $rate;
        });
        
        $totalUSD = $this->amount / ($this->exchange_rate > 0 ? $this->exchange_rate : 1);
        
        return max(0, round($totalUSD - $totalPaidUSD, 4));
    }

    public function checkSettlement()
    {
        if ($this->debt <= 0.01) {
            $this->update(['status' => 'paid']);
        } else {
            $this->update(['status' => 'pending']);
        }
    }
}
