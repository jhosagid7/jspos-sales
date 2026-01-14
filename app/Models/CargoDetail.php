<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CargoDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'cargo_id',
        'product_id',
        'quantity',
        'cost'
    ];

    public function cargo()
    {
        return $this->belongsTo(Cargo::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
