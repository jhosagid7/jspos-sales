<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionDetail extends Model
{
    protected $fillable = [
        'production_id',
        'product_id',
        'production_date',
        'warehouse_id',
        'material_type',
        'quantity',
        'weight',
        'operator_name',
        'metadata',
        'cost'
    ];

    protected $casts = [
        'metadata' => 'array',
        'production_date' => 'date',
        'cost' => 'float'
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
