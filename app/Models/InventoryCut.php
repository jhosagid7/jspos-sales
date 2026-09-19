<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryCut extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'cut_date',
        'total_stock',
        'notes',
    ];

    protected $casts = [
        'cut_date' => 'datetime',
        'total_stock' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function details()
    {
        return $this->hasMany(InventoryCutDetail::class);
    }
}
