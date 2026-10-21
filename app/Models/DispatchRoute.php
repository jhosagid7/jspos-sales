<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DispatchRoute extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'route_type',
        'dispatch_day',
        'cutoff_day',
        'cutoff_time',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }
}
