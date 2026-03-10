<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleReturnDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_return_id',
        'sale_detail_id',
        'product_id',
        'quantity_returned',
        'unit_price',
        'subtotal',
        'stock_action'
    ];

    public function saleReturn()
    {
        return $this->belongsTo(SaleReturn::class);
    }

    public function saleDetail()
    {
        return $this->belongsTo(SaleDetail::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
