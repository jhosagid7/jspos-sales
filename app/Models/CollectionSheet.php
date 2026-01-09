<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollectionSheet extends Model
{
    use HasFactory;

    protected $fillable = [
        'sheet_number',
        'total_amount',
        'status',
        'opened_at',
        'closed_at'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime'
    ];

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
