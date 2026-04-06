<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'account_holder', 'account_number', 'cedula', 'phone', 'state', 'sort', 'currency_code'];
}
