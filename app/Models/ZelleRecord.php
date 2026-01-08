<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZelleRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_name',
        'zelle_date',
        'amount',
        'reference',
        'image_path',
        'status', // 'used', 'partial', 'unused'
        'remaining_balance',
        'customer_id',
        'sale_id',
        'invoice_total',
        'payment_type'
    ];

    protected $casts = [
        'zelle_date' => 'date',
        'amount' => 'decimal:2',
        'remaining_balance' => 'decimal:2',
    ];

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
