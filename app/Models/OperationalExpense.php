<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperationalExpense extends Model
{
    use HasFactory;

    protected $table = 'operational_expenses';

    protected $fillable = [
        'year_month',
        'category',
        'description',
        'amount',
    ];

    protected $casts = [
        'amount' => 'float',
    ];
}
