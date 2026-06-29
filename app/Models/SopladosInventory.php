<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SopladosInventory extends Model
{
    use HasFactory;

    protected $table = 'soplados_inventories';

    protected $fillable = [
        'warehouse_id',
        'supervisor_id',
        'operator_id',
        'shift_id',
        'status',
        'notes',
        'operator_notes',
        'accepted_at'
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function details()
    {
        return $this->hasMany(SopladosInventoryDetail::class, 'soplados_inventory_id');
    }
}
