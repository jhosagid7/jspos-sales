<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BagMachine extends Model
{
    use HasFactory;

    protected $table = 'bag_machines';

    protected $fillable = [
        'name',
        'code',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function shifts()
    {
        return $this->hasMany(BagShift::class, 'machine_id');
    }

    public function productions()
    {
        return $this->hasManyThrough(BagProduction::class, BagShift::class, 'machine_id', 'bag_shift_id');
    }
}
