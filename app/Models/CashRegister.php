<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashRegister extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'opening_date',
        'closing_date',
        'status',
        'opening_notes',
        'closing_notes',
        'total_opening_amount',
        'total_expected_amount',
        'total_counted_amount',
        'difference_amount',
    ];

    protected $casts = [
        'opening_date' => 'datetime',
        'closing_date' => 'datetime',
        'total_opening_amount' => 'decimal:2',
        'total_expected_amount' => 'decimal:2',
        'total_counted_amount' => 'decimal:2',
        'difference_amount' => 'decimal:2',
    ];

    // Relaciones
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function details()
    {
        return $this->hasMany(CashRegisterDetail::class);
    }

    public function movements()
    {
        return $this->hasMany(CashMovement::class);
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
