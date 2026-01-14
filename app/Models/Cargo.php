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
        'status'
    ];

    protected $casts = [
        'date' => 'datetime'
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
