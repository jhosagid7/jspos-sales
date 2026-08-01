<?php

// OrderDetail.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    use HasFactory;

    protected $fillable = ['order_id', 'product_id', 'quantity', 'regular_price', 'sale_price', 'discount', 'warehouse_id', 'metadata'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function getCustomNameAttribute()
    {
        if (!empty($this->metadata)) {
            $meta = is_array($this->metadata) ? $this->metadata : json_decode($this->metadata, true);
            if (!empty($meta['custom_name'])) {
                return $meta['custom_name'];
            }
        }
        return $this->product ? $this->product->name : '';
    }
}
