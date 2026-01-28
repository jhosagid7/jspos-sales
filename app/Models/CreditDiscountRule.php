<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditDiscountRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'entity_type',
        'entity_id',
        'days_from',
        'days_to',
        'discount_percentage',
        'rule_type',
        'description',
    ];

    protected $casts = [
        'discount_percentage' => 'decimal:2',
    ];

    /**
     * Relación polimórfica con Customer o User
     */
    public function entity()
    {
        if ($this->entity_type === 'customer') {
            return $this->belongsTo(\App\Models\Customer::class, 'entity_id');
        } elseif ($this->entity_type === 'seller') {
            return $this->belongsTo(\App\Models\User::class, 'entity_id');
        }
        return null;
    }
}
