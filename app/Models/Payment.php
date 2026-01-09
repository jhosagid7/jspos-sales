<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'sale_id', 'amount', 'currency', 'exchange_rate', 'primary_exchange_rate', 'pay_way', 'type', 'bank', 'account_number', 'deposit_number', 'phone_number', 'payment_date', 'zelle_record_id', 'collection_sheet_id'];

    function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    function payments()
    {
        return $this->hasMany(Payment::class)->orderBy('id', 'desc');
    }

    public function zelleRecord()
    {
        return $this->belongsTo(ZelleRecord::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getIsOnTimeAttribute()
    {
        if (!$this->sale) return true;
        
        $creditDays = $this->sale->credit_days ?? 0;
        // Start of day for sale date to be consistent with due date calculation if needed, 
        // but usually credit days are added to the timestamp. 
        // Let's match Sale's logic: $dueDate = Carbon::parse($this->created_at)->addDays($creditDays);
        
        $dueDate = \Carbon\Carbon::parse($this->sale->created_at)->addDays($creditDays)->endOfDay();
        $paymentDate = \Carbon\Carbon::parse($this->created_at);

        return $paymentDate->lte($dueDate);
    }
}
