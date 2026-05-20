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
}
