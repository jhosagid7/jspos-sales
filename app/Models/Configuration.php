<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Configuration extends Model
{
    use HasFactory;

    protected $table = 'configurations';

    protected $fillable = [
        'business_name',
        'address',
        'city',
        'phone',
        'taxpayer_id',
        'vat',
        'decimals',
        'printer_name',
        'leyend',
        'global_commission_1_threshold',
        'global_commission_1_percentage',
        'global_commission_2_threshold',
        'global_commission_2_percentage',
        'website',
        'credit_days',
        'credit_purchase_days',
        'confirmation_code',
        'invoice_sequence',
        'order_sequence',
        'global_commission_1_threshold',
        'global_commission_1_percentage',
        'global_commission_2_threshold',
        'global_commission_2_threshold',
        'global_commission_2_percentage',
        'logo',
        'default_warehouse_id',
        'check_stock_reservation'
    ];

    public function defaultWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'default_warehouse_id');
    }
}
