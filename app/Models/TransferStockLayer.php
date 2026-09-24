<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferStockLayer extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_id',
        'transfer_detail_id',
        'product_id',
        'origin_warehouse_id',
        'destination_warehouse_id',
        'initial_quantity',
        'remaining_quantity',
        'cost_price',
    ];

    protected $casts = [
        'initial_quantity' => 'decimal:2',
        'remaining_quantity' => 'decimal:2',
        'cost_price' => 'decimal:2',
    ];

    protected $appends = [
        'consumed_quantity',
        'unit_cost',
        'remaining_value',
    ];

    /**
     * Quantity consumed/sold from this layer.
     */
    public function getConsumedQuantityAttribute(): float
    {
        return max(0, round((float) $this->initial_quantity - (float) $this->remaining_quantity, 4));
    }

    /**
     * Unit cost snapshot of the layer or product catalog fallback.
     */
    public function getUnitCostAttribute(): float
    {
        return (float) (($this->cost_price !== null && (float) $this->cost_price > 0) ? $this->cost_price : ($this->product->cost ?? 0));
    }

    /**
     * Remaining inventory value in dollars for this layer.
     */
    public function getRemainingValueAttribute(): float
    {
        return round((float) $this->remaining_quantity * (float) $this->unit_cost, 2);
    }

    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }

    public function transferDetail()
    {
        return $this->belongsTo(TransferDetail::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function originWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'origin_warehouse_id');
    }

    public function destinationWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    public function consumptions()
    {
        return $this->hasMany(SaleLayerConsumption::class);
    }
}
