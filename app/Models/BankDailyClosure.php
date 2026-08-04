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
        'manual_opening_balance',
        'opening_proof_image',
        'total_income',
        'total_income_count',
        'total_expenses',
        'total_expenses_count',
        'closing_balance',
        'manual_closing_balance',
        'closing_proof_image',
        'opening_difference',
        'closing_difference',
        'status',
        'opened_at',
        'opened_by',
        'closed_at',
        'closed_by',
        'notes',
    ];

    protected $casts = [
        'closure_date' => 'date',
        'opening_balance' => 'decimal:2',
        'manual_opening_balance' => 'decimal:2',
        'total_income' => 'decimal:2',
        'total_income_count' => 'integer',
        'total_expenses' => 'decimal:2',
        'total_expenses_count' => 'integer',
        'closing_balance' => 'decimal:2',
        'manual_closing_balance' => 'decimal:2',
        'opening_difference' => 'decimal:2',
        'closing_difference' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function openedBy()
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
