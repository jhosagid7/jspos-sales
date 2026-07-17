<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'account_holder',
        'account_number',
        'cedula',
        'phone',
        'state',
        'sort',
        'currency_code',
        'is_tracked',
        'initial_balance',
        'initial_balance_date',
        'current_balance',
    ];

    protected $casts = [
        'state' => 'boolean',
        'is_tracked' => 'boolean',
        'initial_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'initial_balance_date' => 'date',
        'sort' => 'integer',
    ];

    public function expenses()
    {
        return $this->hasMany(BankExpense::class);
    }

    public function dailyClosures()
    {
        return $this->hasMany(BankDailyClosure::class);
    }

    public function transfersFrom()
    {
        return $this->hasMany(BankTransfer::class, 'from_bank_id');
    }

    public function transfersTo()
    {
        return $this->hasMany(BankTransfer::class, 'to_bank_id');
    }

    public function bankRecords()
    {
        return $this->hasMany(BankRecord::class);
    }

    public function scopeTracked($query)
    {
        return $query->where('is_tracked', true);
    }
}
