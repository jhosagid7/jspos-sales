<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankExpense extends Model
{
    use HasFactory;

    protected $table = 'bank_expenses';

    protected $fillable = [
        'bank_id',
        'category_id',
        'amount',
        'expense_date',
        'description',
        'reference',
        'beneficiary',
        'receipt_path',
        'user_id',
        'is_recurring',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
        'is_recurring' => 'boolean',
    ];

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function category()
    {
        return $this->belongsTo(BankExpenseCategory::class, 'category_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted()
    {
        static::saved(function ($bankExpense) {
            \App\Services\BankTreasuryService::recalculateBalance($bankExpense->bank_id);
        });

        static::deleted(function ($bankExpense) {
            \App\Services\BankTreasuryService::recalculateBalance($bankExpense->bank_id);
        });
    }
}
