<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverLocation extends Model
{
    protected $fillable = ['driver_id', 'latitude', 'longitude'];

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}
