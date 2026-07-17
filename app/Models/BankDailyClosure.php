<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankDailyClosure extends Model
{
    use HasFactory;

    protected $table = 'bank_daily_closures';

    protected $fillable = [
        'bank_id',
        'closure_date',
        'opening_balance',
        'total_income',
        'total_income_count',
        'total_expenses',
        'total_expenses_count',
        'closing_balance',
        'status',
        'closed_at',
        'closed_by',
        'notes',
    ];

    protected $casts = [
        'closure_date' => 'date',
        'opening_balance' => 'decimal:2',
        'total_income' => 'decimal:2',
        'total_income_count' => 'integer',
        'total_expenses' => 'decimal:2',
        'total_expenses_count' => 'integer',
        'closing_balance' => 'decimal:2',
        'closed_at' => 'datetime',
    ];

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
