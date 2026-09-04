<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormulaVersionItem extends Model
{
    use HasFactory;

    protected $table = 'formula_version_items';

    protected $fillable = [
        'formula_version_id',
        'raw_material_id',
        'quantity_kg',
        'price_applied',
        'subtotal_cost',
    ];

    protected $casts = [
        'quantity_kg'   => 'decimal:4',
        'price_applied' => 'decimal:4',
        'subtotal_cost' => 'decimal:4',
    ];

    public function version(): BelongsTo
    {
        return $this->belongsTo(FormulaVersion::class, 'formula_version_id');
    }

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class, 'raw_material_id');
    }
}
