<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceAuthorization extends Model
{
    protected $fillable = [
        'uuid',
        'name',
        'ip_address',
        'user_agent',
        'status',
        'last_accessed_at',
        'printer_name',
        'printer_width',
    ];

    protected $casts = [
        'last_accessed_at' => 'datetime',
    ];
}
