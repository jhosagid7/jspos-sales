<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_id',
        'payment_date',
        'amount',
        'reference',
        'image_path',
        'note',
        'sale_id',
        'debit_note_id',
        'payment_id',
        'status', 
        'remaining_balance',
        'income_type',
        'income_category',
        'created_by'
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'remaining_balance' => 'decimal:2',
    ];

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
    
    public function salePaymentDetails()
    {
        return $this->hasMany(SalePaymentDetail::class);
    }

    public function debitNote()
    {
        return $this->belongsTo(DebitNote::class);
    }

    protected static function booted()
    {
        static::saved(function ($bankRecord) {
            \App\Services\BankTreasuryService::recalculateBalance($bankRecord->bank_id);
        });

        static::deleted(function ($bankRecord) {
            \App\Services\BankTreasuryService::recalculateBalance($bankRecord->bank_id);
        });
    }
}
