<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    use HasFactory;

    protected $fillable = [
        'license_key',
        'client_id',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];
}
