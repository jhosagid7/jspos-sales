<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryCollection extends Model
{
    protected $fillable = ['sale_id', 'driver_id', 'amount', 'note'];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function payments()
    {
        return $this->hasMany(DeliveryCollectionPayment::class);
    }
}
