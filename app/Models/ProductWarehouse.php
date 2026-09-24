<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ProductWarehouse extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'product_warehouse';
    protected $fillable = ['product_id', 'warehouse_id', 'stock_qty'];

    public $auditEventContext = 'SISTEMA';

    protected static function booted()
    {
        static::saved(function ($pw) {
            $defaultWarehouseId = \App\Models\Configuration::first()?->default_warehouse_id
                ?? \App\Models\Warehouse::first()?->id
                ?? 1;

            if ($pw->warehouse_id == $defaultWarehouseId) {
                \App\Models\Product::where('id', $pw->product_id)->update(['stock_qty' => $pw->stock_qty]);
            }
        });

        static::deleted(function ($pw) {
            $defaultWarehouseId = \App\Models\Configuration::first()?->default_warehouse_id
                ?? \App\Models\Warehouse::first()?->id
                ?? 1;

            if ($pw->warehouse_id == $defaultWarehouseId) {
                \App\Models\Product::where('id', $pw->product_id)->update(['stock_qty' => 0]);
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['stock_qty'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Stock en depósito ha sido {$eventName}");
    }

    public function tapActivity(\Spatie\Activitylog\Models\Activity $activity, string $eventName)
    {
        $properties = $activity->properties->toArray();
        $properties['source'] = $this->auditEventContext ?? 'SISTEMA';
        $activity->properties = collect($properties);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
