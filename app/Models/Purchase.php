<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'total',
        'flete',
        'discount',
        'items',
        'status',
        'type',
        'supplier_id',
        'user_id',
        'notes',
    ];

    function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }


    function details()
    {
        return $this->hasMany(PurchaseDetail::class);
    }

    function user()
    {
        return $this->belongsTo(User::class)->select('id', 'name');
    }

    function payables()
    {
        return $this->hasMany(Payable::class)->orderBy('id', 'desc');
    }

    //scopes
    // public function scopeWithDebt($query)
    // {
    //     return $query->addSelect([
    //         'debt' => DB::raw('total - total_payments')
    //     ])->withSum('payments', 'amount');
    // }

    //accessors
    public function getDebtAttribute()
    {
        $totalPays = $this->payables->sum('amount');

        $debt = $this->total - $totalPays;

        return $debt;
    }
}
