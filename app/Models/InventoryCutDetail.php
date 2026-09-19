<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryCutDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_cut_id',
        'warehouse_id',
        'counted_stock',
        'previous_stock',
    ];

    protected $casts = [
        'counted_stock' => 'decimal:2',
        'previous_stock' => 'decimal:2',
    ];

    public function cut()
    {
        return $this->belongsTo(InventoryCut::class, 'inventory_cut_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
