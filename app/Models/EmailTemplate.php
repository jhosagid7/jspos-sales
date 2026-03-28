<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_type',
        'subject',
        'body',
        'is_active',
        'dispatch_mode',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
