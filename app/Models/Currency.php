<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;

    /**
     * Los atributos que se pueden asignar masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',          // Código de la moneda (ISO 4217)
        'label',         // Nombre de la moneda
        'symbol',        // Símbolo de la moneda
        'exchange_rate', // Tasa de cambio respecto a la moneda principal
        'is_primary',    // Indica si es la moneda principal
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_primary' => 'boolean',
        'exchange_rate' => 'float',
    ];
}
