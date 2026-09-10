<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BagProduction extends Model
{
    use HasFactory;

    protected $table = 'bag_productions';

    protected $fillable = [
        'bag_shift_id',
        'user_id',
        'product_id',
        'quantity',
        'weight',
        'recorded_at',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
        'original_weight',
        'qr_code',
        'lifted_by',
        'lifted_at',
        'jspos_production_id',
        'sync_id',
        'metadata',
        'weight_quality_grade',
        'weight_deviation_percent',
        'completed_packages_count',
        'fractional_units',
        'is_package_completed',
        'labor_earned_amount',
        'labor_retained_amount',
        'completed_by_production_id',
    ];

    protected $casts = [
        'recorded_at'              => 'datetime',
        'reviewed_at'              => 'datetime',
        'lifted_at'                => 'datetime',
        'quantity'                 => 'decimal:2',
        'weight'                   => 'decimal:4',
        'original_weight'          => 'decimal:4',
        'weight_deviation_percent' => 'decimal:2',
        'completed_packages_count' => 'decimal:2',
        'fractional_units'         => 'decimal:2',
        'is_package_completed'     => 'boolean',
        'labor_earned_amount'      => 'decimal:2',
        'labor_retained_amount'    => 'decimal:2',
        'metadata'                 => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($production) {
            if (empty($production->qr_code)) {
                $production->qr_code = 'PKG-' . strtoupper(Str::random(10));
            }
            if (empty($production->weight_quality_grade)) {
                $production->weight_quality_grade = 'B';
            }
        });
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(BagShift::class, 'bag_shift_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function lifter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lifted_by');
    }

    public function jsposProduction(): BelongsTo
    {
        return $this->belongsTo(Production::class, 'jspos_production_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(BagProduct::class, 'product_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(BagProduction::class, 'completed_by_production_id');
    }

    /**
     * Machine through the shift relationship.
     */
    public function getMachineAttribute()
    {
        return $this->shift?->machine;
    }

    /**
     * Resilient product name accessor (supports BagProduct and JSPOS Sales Product).
     */
    public function getProductNameAttribute(): string
    {
        if ($this->product && !empty($this->product->name)) {
            return $this->product->name;
        }
        $salesProduct = \App\Models\Product::find($this->product_id);
        return $salesProduct ? $salesProduct->name : 'Bolsa';
    }

    /**
     * Discrete Batch Code: L{ymd}-{machineCode}-{grade} (e.g. L260910-EXT01-B).
     */
    public function getEffectiveBatchCodeAttribute(): string
    {
        $dateStr = ($this->recorded_at ?? now())->format('ymd');
        $machineCode = $this->machine?->code ?? ($this->shift?->machine?->code ?? 'PL01');
        $grade = strtoupper($this->weight_quality_grade ?: 'B');

        return "L{$dateStr}-{$machineCode}-{$grade}";
    }

    /**
     * Weight Quality Badge Label.
     */
    public function getWeightGradeLabelAttribute(): string
    {
        $grade = strtoupper($this->weight_quality_grade ?: 'B');
        $dev = $this->weight_deviation_percent !== null ? (float)$this->weight_deviation_percent : 0.0;
        $sign = $dev > 0 ? '+' : '';

        return match ($grade) {
            'A'     => "Grado A: Sobrepeso ({$sign}{$dev}%)",
            'C'     => "Grado C: Subcalibre ({$sign}{$dev}%)",
            default => "Grado B: Peso Óptimo ({$sign}{$dev}%)",
        };
    }

    /**
     * Collaborative fraction completion: releases retained labor amount.
     */
    public function completeFractionWith(BagProduction $completingProduction): void
    {
        $this->update([
            'is_package_completed'       => true,
            'labor_retained_amount'      => 0.00,
            'completed_by_production_id' => $completingProduction->id,
        ]);
    }

    /**
     * Scope for items approved in factory and ready for JSPOS warehouse lifting.
     */
    public function scopeReadyForLifting($query)
    {
        return $query->where('status', 'approved')->whereNull('lifted_at');
    }
}
