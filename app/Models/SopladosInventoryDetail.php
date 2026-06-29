<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SopladosInventoryDetail extends Model
{
    use HasFactory;

    protected $table = 'soplados_inventory_details';

    protected $fillable = [
        'soplados_inventory_id',
        'product_id',
        'type',
        'system_stock_primera',
        'counted_primera',
        'difference_primera',
        'system_stock_segunda',
        'counted_segunda',
        'difference_segunda',
        'counted_merma'
    ];

    public function inventory()
    {
        return $this->belongsTo(SopladosInventory::class, 'soplados_inventory_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
