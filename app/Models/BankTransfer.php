<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankTransfer extends Model
{
    use HasFactory;

    protected $table = 'bank_transfers';

    protected $fillable = [
        'from_bank_id',
        'to_bank_id',
        'amount_from',
        'amount_to',
        'exchange_rate',
        'transfer_date',
        'reference',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'amount_from' => 'decimal:2',
        'amount_to' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'transfer_date' => 'date',
    ];

    public function fromBank()
    {
        return $this->belongsTo(Bank::class, 'from_bank_id');
    }

    public function toBank()
    {
        return $this->belongsTo(Bank::class, 'to_bank_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted()
    {
        static::saved(function ($bankTransfer) {
            \App\Services\BankTreasuryService::recalculateBalance($bankTransfer->from_bank_id);
            \App\Services\BankTreasuryService::recalculateBalance($bankTransfer->to_bank_id);
        });

        static::deleted(function ($bankTransfer) {
            \App\Services\BankTreasuryService::recalculateBalance($bankTransfer->from_bank_id);
            \App\Services\BankTreasuryService::recalculateBalance($bankTransfer->to_bank_id);
        });
    }
}
