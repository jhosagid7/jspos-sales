<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryCollectionPayment extends Model
{
    protected $fillable = ['delivery_collection_id', 'currency_id', 'amount', 'exchange_rate'];

    public function collection()
    {
        return $this->belongsTo(DeliveryCollection::class, 'delivery_collection_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }
}
