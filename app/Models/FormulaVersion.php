<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormulaVersion extends Model
{
    use HasFactory;

    protected $table = 'formula_versions';

    protected $fillable = [
        'production_formula_id',
        'version_number',
        'total_kg',
        'total_cost',
        'cost_per_kg',
        'is_active',
        'valid_from',
        'valid_to',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'version_number' => 'integer',
        'total_kg'       => 'decimal:4',
        'total_cost'     => 'decimal:4',
        'cost_per_kg'    => 'decimal:4',
        'is_active'      => 'boolean',
        'valid_from'     => 'datetime',
        'valid_to'       => 'datetime',
    ];

    public function formula(): BelongsTo
    {
        return $this->belongsTo(ProductionFormula::class, 'production_formula_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(FormulaVersionItem::class, 'formula_version_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
