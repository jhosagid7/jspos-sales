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
        'total_usd',
        'discount',
        'items',
        'status',
        'customer_id',

        'user_id',
        'type',
        'cash',
        'change',
        'notes',
        'primary_currency_code',
        'primary_exchange_rate',
        'invoice_number',
        'order_number',
        'seller_config_id',
        'applied_commission_percent',
        'applied_freight_percent',
        'applied_exchange_diff_percent',
        'is_foreign_sale',
        'final_commission_amount',
        'commission_status',
        'commission_paid_at',
        'commission_payment_method',
        'commission_payment_reference',
        'commission_payment_bank_name',
        'commission_payment_currency',
        'commission_payment_rate',
        'commission_payment_amount',
        'commission_payment_notes',
        'batch_name',
        'batch_sequence'
    ];

    protected $casts = [
        'is_foreign_sale' => 'boolean',
        'applied_commission_percent' => 'decimal:2',
        'final_commission_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    function details()
    {
        return $this->hasMany(SaleDetail::class);
    }

    function customer()
    {
        return $this->belongsTo(Customer::class)->select('id', 'name', 'address', 'city', 'phone', 'taxpayer_id', 'email', 'seller_id');
    }

    function user()
    {
        return $this->belongsTo(User::class)->select('id', 'name');
    }

    public function sellerConfig()
    {
        return $this->belongsTo(SellerConfig::class);
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
