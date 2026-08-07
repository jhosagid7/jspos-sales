<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommissionGoal extends Model
{
    use HasFactory;

    protected $table = 'commission_goals';

    protected $fillable = [
        'name',
        'target_amount',
        'reward_amount',
        'periodicity',
        'start_day_of_week',
        'end_day_of_week',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'reward_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_commission_goals')->withTimestamps();
    }
}
