<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = ['total', 'discount', 'items', 'customer_id', 'user_id', 'status', 'notes', 'order_number', 'apply_commissions', 'apply_freight', 'is_freight_broken_down', 'invoice_currency_id'];
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

    public function getResolvedCommissionPercentAttribute()
    {
        if (!$this->apply_commissions) {
            return 0.00;
        }
        $customer = $this->customer;
        $customerConfig = $customer ? $customer->latestCustomerConfig : null;
        $seller = ($customer && $customer->seller) ? $customer->seller : $this->user;
        $sellerConfig = $seller ? $seller->latestSellerConfig : null;
        
        $customerHasConfig = $customerConfig && (
            $customerConfig->commission_percent > 0 ||
            $customerConfig->freight_percent > 0 ||
            $customerConfig->exchange_diff_percent > 0
        );
        
        if ($customerHasConfig) {
            return floatval($customerConfig->commission_percent);
        }
        return $sellerConfig ? floatval($sellerConfig->commission_percent) : 0;
    }

    public function getResolvedFreightPercentAttribute()
    {
        if (!$this->apply_freight) {
            return 0.00;
        }
        $customer = $this->customer;
        $customerConfig = $customer ? $customer->latestCustomerConfig : null;
        $seller = ($customer && $customer->seller) ? $customer->seller : $this->user;
        $sellerConfig = $seller ? $seller->latestSellerConfig : null;
        
        $customerHasConfig = $customerConfig && (
            $customerConfig->commission_percent > 0 ||
            $customerConfig->freight_percent > 0 ||
            $customerConfig->exchange_diff_percent > 0
        );
        
        if ($customerHasConfig) {
            return floatval($customerConfig->freight_percent);
        }
        return $sellerConfig ? floatval($sellerConfig->freight_percent) : 0;
    }

    public function getResolvedExchangeDiffPercentAttribute()
    {
        if (!$this->apply_commissions) {
            return 0.00;
        }
        $customer = $this->customer;
        $customerConfig = $customer ? $customer->latestCustomerConfig : null;
        $seller = ($customer && $customer->seller) ? $customer->seller : $this->user;
        $sellerConfig = $seller ? $seller->latestSellerConfig : null;
        
        $customerHasConfig = $customerConfig && (
            $customerConfig->commission_percent > 0 ||
            $customerConfig->freight_percent > 0 ||
            $customerConfig->exchange_diff_percent > 0
        );
        
        if ($customerHasConfig) {
            return floatval($customerConfig->exchange_diff_percent);
        }
        return $sellerConfig ? floatval($sellerConfig->exchange_diff_percent) : 0;
    }
}
