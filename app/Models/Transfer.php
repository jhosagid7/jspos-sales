<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    use HasFactory;

    protected $fillable = ['from_warehouse_id', 'to_warehouse_id', 'user_id', 'dispatched_by_id', 'received_by_id', 'status', 'note'];

    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function dispatchedBy()
    {
        return $this->belongsTo(User::class, 'dispatched_by_id');
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by_id');
    }

    public function details()
    {
        return $this->hasMany(TransferDetail::class);
    }
}
