<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SopladosProductionTarget extends Model
{
    use HasFactory;

    protected $table = 'soplados_production_targets';

    protected $fillable = [
        'product_id',
        'min_target',
        'max_target',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
