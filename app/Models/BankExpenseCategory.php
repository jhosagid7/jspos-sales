<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankExpenseCategory extends Model
{
    use HasFactory;

    protected $table = 'bank_expense_categories';

    protected $fillable = [
        'name',
        'icon',
        'color',
        'is_essential',
        'sort',
        'is_active',
    ];

    protected $casts = [
        'is_essential' => 'boolean',
        'is_active' => 'boolean',
        'sort' => 'integer',
    ];

    public function expenses()
    {
        return $this->hasMany(BankExpense::class, 'category_id');
    }
}
