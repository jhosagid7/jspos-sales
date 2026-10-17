<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'customer_id',
        'vehicle_id',
        'user_id',
        'mechanic_id',
        'sale_id',
        'entry_mileage',
        'exit_mileage',
        'fuel_level',
        'initial_diagnosis',
        'technical_notes',
        'status',
        'promised_at',
        'delivered_at',
        'subtotal_parts',
        'subtotal_labor',
        'tax',
        'total',
    ];

    protected $casts = [
        'entry_mileage' => 'integer',
        'exit_mileage' => 'integer',
        'subtotal_parts' => 'decimal:2',
        'subtotal_labor' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'promised_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $lastId = static::withTrashed()->max('id') ?? 0;
                $nextNumber = $lastId + 1;
                $order->order_number = 'OT-' . str_pad((string)$nextNumber, 5, '0', STR_PAD_LEFT);
            }
            if (empty($order->status)) {
                $order->status = 'reception';
            }
        });
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function mechanic()
    {
        return $this->belongsTo(User::class, 'mechanic_id');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function items()
    {
        return $this->hasMany(WorkOrderItem::class);
    }

    public function parts()
    {
        return $this->hasMany(WorkOrderItem::class)->where('type', 'part');
    }

    public function labors()
    {
        return $this->hasMany(WorkOrderItem::class)->where('type', 'labor');
    }

    public function addItem(array $attributes): WorkOrderItem
    {
        $item = $this->items()->create($attributes);
        $this->recalculateTotals();
        return $item;
    }

    public function recalculateTotals(): void
    {
        $parts = $this->items()->where('type', 'part')->where('is_approved', true)->sum('subtotal');
        $labor = $this->items()->where('type', 'labor')->where('is_approved', true)->sum('subtotal');
        $this->subtotal_parts = round((float)$parts, 2);
        $this->subtotal_labor = round((float)$labor, 2);
        $this->total = round($this->subtotal_parts + $this->subtotal_labor + (float)$this->tax, 2);
        $this->saveQuietly();
    }

    public function updateStatus(string $newStatus): bool
    {
        $this->status = $newStatus;
        if ($newStatus === 'delivered' && empty($this->delivered_at)) {
            $this->delivered_at = now();
        }
        return $this->save();
    }

    public function scopeSearch($query, $term)
    {
        if (blank($term)) {
            return $query;
        }

        $term = trim((string)$term);

        return $query->where(function ($q) use ($term) {
            $q->where('order_number', 'like', "%{$term}%")
              ->orWhereHas('vehicle', function ($vq) use ($term) {
                  $vq->where('plate', 'like', "%{$term}%")
                     ->orWhere('brand', 'like', "%{$term}%")
                     ->orWhere('model', 'like', "%{$term}%");
              })
              ->orWhereHas('customer', function ($cq) use ($term) {
                  $cq->where('name', 'like', "%{$term}%")
                     ->orWhere('taxpayer_id', 'like', "%{$term}%");
              });
        });
    }
}
