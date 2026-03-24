<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cargo extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'user_id',
        'authorized_by',
        'motive',
        'date',
        'comments',
        'status',
        'approved_by',
        'approval_date',
        'rejection_reason',
        'rejected_by',
        'rejection_date',
        'deletion_reason',
        'deleted_by',
        'deletion_date'
    ];

    protected $casts = [
        'date' => 'datetime',
        'approval_date' => 'datetime',
        'rejection_date' => 'datetime',
        'deletion_date' => 'datetime'
    ];

    public function details()
    {
        return $this->hasMany(CargoDetail::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
