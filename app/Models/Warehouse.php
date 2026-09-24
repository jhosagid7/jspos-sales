<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'address', 'is_active', 'is_partner_warehouse', 'partner_name'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_partner_warehouse' => 'boolean',
    ];

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = \App\Helpers\NameNormalizer::uppercase($value);
    }

    public function setPartnerNameAttribute($value)
    {
        $this->attributes['partner_name'] = \App\Helpers\NameNormalizer::personOrEntityName($value);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_warehouse')
            ->withPivot('stock_qty')
            ->withTimestamps();
    }
}
