<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleLayerConsumption extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'sale_detail_id',
        'transfer_stock_layer_id',
        'origin_warehouse_id',
        'product_id',
        'quantity',
        'unit_cost',
        'unit_price',
        'total_cost',
        'total_price',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function getProfitAttribute(): float
    {
        return round((float) ($this->total_price - $this->total_cost), 2);
    }

    public function getMarginPercentageAttribute(): float
    {
        if ((float) $this->total_price <= 0) {
            return 0.0;
        }
        return round((($this->total_price - $this->total_cost) / $this->total_price) * 100, 2);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function saleDetail()
    {
        return $this->belongsTo(SaleDetail::class);
    }

    public function transferStockLayer()
    {
        return $this->belongsTo(TransferStockLayer::class);
    }

    public function originWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'origin_warehouse_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
