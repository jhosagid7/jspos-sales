<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DescargoDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'descargo_id',
        'product_id',
        'quantity',
        'cost'
    ];

    public function descargo()
    {
        return $this->belongsTo(Descargo::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
