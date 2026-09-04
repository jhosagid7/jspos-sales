<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RawMaterial extends Model
{
    use HasFactory;

    protected $table = 'raw_materials';

    protected $fillable = [
        'name',
        'code',
        'description',
        'base_price',
        'transport_cost',
        'surcharge',
        'final_price',
        'is_active',
    ];

    protected $casts = [
        'base_price'     => 'decimal:4',
        'transport_cost' => 'decimal:4',
        'surcharge'      => 'decimal:4',
        'final_price'    => 'decimal:4',
        'is_active'      => 'boolean',
    ];

    public function priceHistories(): HasMany
    {
        return $this->hasMany(RawMaterialPriceHistory::class, 'raw_material_id')->orderBy('valid_from', 'desc');
    }

    public function formulaItems(): HasMany
    {
        return $this->hasMany(FormulaVersionItem::class, 'raw_material_id');
    }

    /**
     * Calcula el Precio Final: PrecioBase + Transporte + Recargo
     */
    public static function calculateFinalPrice(float $base, float $transport, float $surcharge): float
    {
        return round($base + $transport + $surcharge, 4);
    }

    /**
     * Actualiza el precio registrando un histórico inmutable (Auditoría Temporal)
     */
    public function updatePrice(float $base, float $transport, float $surcharge, ?int $userId = null, ?string $notes = null): RawMaterialPriceHistory
    {
        $newFinal = self::calculateFinalPrice($base, $transport, $surcharge);

        // 1. Cerrar vigencia del precio anterior
        $now = now();
        RawMaterialPriceHistory::where('raw_material_id', $this->id)
            ->whereNull('valid_to')
            ->update(['valid_to' => $now]);

        // 2. Insertar nuevo histórico inmutable
        $history = RawMaterialPriceHistory::create([
            'raw_material_id' => $this->id,
            'base_price'      => $base,
            'transport_cost'  => $transport,
            'surcharge'       => $surcharge,
            'final_price'     => $newFinal,
            'valid_from'      => $now,
            'valid_to'        => null,
            'created_by'      => $userId,
            'notes'           => $notes,
        ]);

        // 3. Actualizar material actual
        $this->update([
            'base_price'     => $base,
            'transport_cost' => $transport,
            'surcharge'      => $surcharge,
            'final_price'    => $newFinal,
        ]);

        return $history;
    }

    /**
     * Obtiene el precio vigente a una fecha histórica específica
     */
    public function getPriceAtDate($dateTime): float
    {
        $history = RawMaterialPriceHistory::where('raw_material_id', $this->id)
            ->where('valid_from', '<=', $dateTime)
            ->where(function ($q) use ($dateTime) {
                $q->whereNull('valid_to')->orWhere('valid_to', '>', $dateTime);
            })
            ->latest('valid_from')
            ->first();

        return $history ? (float)$history->final_price : (float)$this->final_price;
    }
}
