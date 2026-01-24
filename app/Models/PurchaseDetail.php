<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'cost',
        'quantity',
        'product_id',
        'flete_total',
        'purchase_id',
        'flete_product',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array'
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
