<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'plate',
        'brand',
        'model',
        'year',
        'color',
        'vin',
        'engine_serial',
        'displacement',
        'transmission',
        'fuel_type',
        'current_mileage',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'year' => 'integer',
        'current_mileage' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Mutator to guarantee uppercase, trimmed license plates.
     */
    public function setPlateAttribute($value): void
    {
        $this->attributes['plate'] = mb_strtoupper(trim((string)$value), 'UTF-8');
    }

    /**
     * Relationship: A vehicle belongs to a Customer.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relationship: A vehicle has many Work Orders.
     */
    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class);
    }

    /**
     * Accessor: Full descriptive vehicle title.
     */
    public function getFullNameAttribute(): string
    {
        $details = array_filter([$this->brand, $this->model, $this->year]);
        $main = implode(' ', $details);
        return $main ? "{$main} [{$this->plate}]" : $this->plate;
    }

    /**
     * Scope to search vehicles by plate, brand, model, VIN, or owner name/taxpayer ID.
     */
    public function scopeSearch($query, $term)
    {
        if (blank($term)) {
            return $query;
        }

        $term = trim((string)$term);

        return $query->where(function ($q) use ($term) {
            $q->where('plate', 'like', "%{$term}%")
              ->orWhere('brand', 'like', "%{$term}%")
              ->orWhere('model', 'like', "%{$term}%")
              ->orWhere('vin', 'like', "%{$term}%")
              ->orWhereHas('customer', function ($cq) use ($term) {
                  $cq->where('name', 'like', "%{$term}%")
                     ->orWhere('taxpayer_id', 'like', "%{$term}%");
              });
        });
    }
}
