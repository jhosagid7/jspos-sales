<?php

namespace App\Models;

use App\Models\Delivery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'city',
        'email',
        'phone',
        'taxpayer_id',
        'type',
        'seller_id',
        'customer_commission_1_threshold',
        'customer_commission_1_percentage',
        'customer_commission_1_percentage',
        'customer_commission_2_threshold',
        'customer_commission_2_percentage',
        'zone',
        'wallet_balance',
        // Credit configuration fields
        'allow_credit',
        'credit_days',
        'credit_limit',
        'usd_payment_discount',
        'usd_payment_discount_tag',
        // WhatsApp configuration fields
        'whatsapp_notify_sales',
        'whatsapp_notify_payments',
        'email_notify_sales',
        'email_notify_payments',
        'wa_dispatch_mode',
        'email_dispatch_mode',
    ];

    protected $casts = [
        'allow_credit' => 'boolean',
        'credit_days' => 'integer',
        'credit_limit' => 'decimal:2',
        'usd_payment_discount' => 'decimal:2',
        'whatsapp_notify_sales' => 'boolean',
        'whatsapp_notify_payments' => 'boolean',
        'email_notify_sales' => 'boolean',
        'email_notify_payments' => 'boolean',
    ];

    function sales()
    {
        return $this->hasMany(Sale::class);
    }

    function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function customerConfigs()
    {
        return $this->hasMany(CustomerConfig::class);
    }

    public function latestCustomerConfig()
    {
        return $this->hasOne(CustomerConfig::class)->latestOfMany();
    }
}
