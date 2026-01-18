<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'latitude',
        'longitude',
        'status_at_capture',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
