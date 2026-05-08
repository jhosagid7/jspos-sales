<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionOutput extends Model
{
    use HasFactory;

    protected $fillable = ['production_log_id', 'product_id', 'quantity', 'quality'];

    public function productionLog()
    {
        return $this->belongsTo(ProductionLog::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
