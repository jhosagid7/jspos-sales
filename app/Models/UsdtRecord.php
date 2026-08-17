<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class UsdtRecord extends Model
{
    use HasFactory;

    protected $table = 'usdt_records';

    protected $fillable = [
        'sender_name',
        'usdt_date',
        'amount',
        'reference',
        'image_path',
        'status', // 'used', 'partial', 'unused'
        'remaining_balance',
        'customer_id',
        'sale_id',
        'debit_note_id',
        'invoice_total'
    ];

    protected $casts = [
        'usdt_date' => 'date',
        'amount' => 'decimal:2',
        'remaining_balance' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->usdt_date)) {
                $model->usdt_date = Carbon::now()->toDateString();
            }
        });
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
}
