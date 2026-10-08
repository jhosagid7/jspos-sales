<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_order_id',
        'product_id',
        'mechanic_id',
        'type',
        'description',
        'quantity',
        'unit_cost',
        'unit_price',
        'subtotal',
        'mechanic_commission_percentage',
        'mechanic_commission_amount',
        'is_approved',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'mechanic_commission_percentage' => 'decimal:2',
        'mechanic_commission_amount' => 'decimal:2',
        'is_approved' => 'boolean',
    ];

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function mechanic()
    {
        return $this->belongsTo(User::class, 'mechanic_id');
    }

    protected static function booted()
    {
        static::saving(function ($item) {
            $item->quantity = (float)($item->quantity ?: 1);
            $item->unit_price = (float)($item->unit_price ?: 0);
            $item->subtotal = round($item->quantity * $item->unit_price, 2);

            if ($item->type === 'labor' && (float)$item->mechanic_commission_percentage > 0) {
                $item->mechanic_commission_amount = round(
                    ($item->subtotal * (float)$item->mechanic_commission_percentage) / 100,
                    2
                );
            } else {
                $item->mechanic_commission_amount = (float)($item->mechanic_commission_amount ?: 0);
            }
        });

        static::saved(function ($item) {
            if ($item->workOrder) {
                $item->workOrder->recalculateTotals();
            }
        });

        static::deleted(function ($item) {
            if ($item->workOrder) {
                $item->workOrder->recalculateTotals();
            }
        });
    }
}
