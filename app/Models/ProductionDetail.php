<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionDetail extends Model
{
    protected $fillable = [
        'production_id',
        'product_id',
        'warehouse_id',
        'material_type',
        'quantity',
        'weight',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array'
    ];

    public function production()
    {
        return $this->belongsTo(Production::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
