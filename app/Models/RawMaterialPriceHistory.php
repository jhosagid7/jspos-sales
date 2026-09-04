<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RawMaterialPriceHistory extends Model
{
    use HasFactory;

    protected $table = 'raw_material_price_histories';

    protected $fillable = [
        'raw_material_id',
        'base_price',
        'transport_cost',
        'surcharge',
        'final_price',
        'valid_from',
        'valid_to',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'base_price'     => 'decimal:4',
        'transport_cost' => 'decimal:4',
        'surcharge'      => 'decimal:4',
        'final_price'    => 'decimal:4',
        'valid_from'     => 'datetime',
        'valid_to'       => 'datetime',
    ];

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class, 'raw_material_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
