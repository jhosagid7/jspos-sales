<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Production extends Model
{
    protected $fillable = [
        'user_id',
        'production_date',
        'status',
        'note'
    ];

    protected $casts = [
        'production_date' => 'date'
    ];

    public function details()
    {
        return $this->hasMany(ProductionDetail::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
