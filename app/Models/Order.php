<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = ['total', 'discount', 'items', 'customer_id', 'user_id', 'status', 'notes', 'order_number', 'apply_commissions', 'apply_freight', 'is_freight_broken_down', 'invoice_currency_id', 'driver_id', 'base_amount', 'commission_amount', 'freight_amount', 'exchange_diff_amount', 'applied_base_markup_percent', 'base_markup_amount', 'payment_agreement'];
    public function details()
    {
        return $this->hasMany(OrderDetail::class);
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function getBaseAmountAttribute($value)
    {
        if ($value !== null) {
            return floatval($value);
        }
        $increments = ($this->resolved_commission_percent + $this->resolved_freight_percent + $this->resolved_exchange_diff_percent + $this->resolved_base_markup_percent) / 100;
        return $this->total / (1 + $increments);
    }

    public function getCommissionAmountAttribute($value)
    {
        if ($value !== null) {
            return floatval($value);
        }
        return $this->base_amount * ($this->resolved_commission_percent / 100);
    }

    public function getFreightAmountAttribute($value)
    {
        if ($value !== null) {
            return floatval($value);
        }
        return $this->base_amount * ($this->resolved_freight_percent / 100);
    }

    public function getExchangeDiffAmountAttribute($value)
    {
        if ($value !== null) {
            return floatval($value);
        }
        return $this->base_amount * ($this->resolved_exchange_diff_percent / 100);
    }

    public function getBaseMarkupAmountAttribute($value)
    {
        if ($value !== null) {
            return floatval($value);
        }
        return $this->base_amount * ($this->resolved_base_markup_percent / 100);
    }

    public function getSurchargePercentageAttribute()
    {
        return $this->resolved_commission_percent + $this->resolved_freight_percent + $this->resolved_exchange_diff_percent + $this->resolved_base_markup_percent;
    }

    public function getResolvedCommissionPercentAttribute()
    {
        if (!$this->apply_commissions) {
            return 0.00;
        }
        $customer = $this->customer;
        $customerConfig = $customer ? $customer->latestCustomerConfig : null;
        return $customerConfig ? floatval($customerConfig->commission_percent) : 0;
    }

    public function getResolvedFreightPercentAttribute()
    {
        if (!$this->apply_freight) {
            return 0.00;
        }
        $customer = $this->customer;
        $customerConfig = $customer ? $customer->latestCustomerConfig : null;
        return $customerConfig ? floatval($customerConfig->freight_percent) : 0;
    }

    public function getResolvedExchangeDiffPercentAttribute()
    {
        if (!$this->apply_commissions) {
            return 0.00;
        }
        $customer = $this->customer;
        $customerConfig = $customer ? $customer->latestCustomerConfig : null;
        return $customerConfig ? floatval($customerConfig->exchange_diff_percent) : 0;
    }

    public function getResolvedBaseMarkupPercentAttribute()
    {
        if (isset($this->attributes['applied_base_markup_percent'])) {
            return floatval($this->attributes['applied_base_markup_percent']);
        }
        $customer = $this->customer;
        $customerConfig = $customer ? $customer->latestCustomerConfig : null;
        return $customerConfig ? floatval($customerConfig->base_markup_percent) : 0;
    }

    public function getPaymentAgreementAttribute($value)
    {
        if (!empty($value)) {
            return $value;
        }
        return $this->exchange_diff_amount > 0 ? 'BCV' : 'USD';
    }
}
