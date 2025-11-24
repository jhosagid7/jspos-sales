<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'total',
        'discount',
        'items',
        'status',
        'customer_id',

        'user_id',
        'type',
        'cash',
        'change',
        'notes'
    ];

    function details()
    {
        return $this->hasMany(SaleDetail::class);
    }

    function customer()
    {
        return $this->belongsTo(Customer::class)->select('id', 'name', 'address', 'city', 'phone', 'taxpayer_id',  'email');
    }

    function user()
    {
        return $this->belongsTo(User::class)->select('id', 'name');
    }

    function payments()
    {
        return $this->hasMany(Payment::class)->orderBy('id', 'desc');
    }

    function changeDetails()
    {
        return $this->hasMany(SaleChangeDetail::class);
    }

    function paymentDetails()
    {
        return $this->hasMany(SalePaymentDetail::class);
    }

    //scopes
    // public function scopeWithDebt($query)
    // {
    //     return $query->addSelect([
    //         'debt' => DB::raw('total - total_payments')
    //     ])->withSum('payments', 'amount');
    // }

    //accessors
    public function getDebtAttribute()
    {
        $totalPays = $this->payments->sum('amount');

        $debt = $this->total - $totalPays;

        return $debt;
    }
}
