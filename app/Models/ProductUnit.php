<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductUnit extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'unit_name', 'conversion_factor', 'price', 'barcode'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
