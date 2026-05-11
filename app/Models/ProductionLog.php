<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionLog extends Model
{
    use HasFactory;

    protected $fillable = ['shift_id', 'user_id', 'notes'];

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function materials()
    {
        return $this->hasMany(ProductionMaterial::class);
    }

    public function outputs()
    {
        return $this->hasMany(ProductionOutput::class);
    }

    public function getStatsAttribute()
    {
        $good = $this->outputs->whereIn('quality', ['1st', '2nd'])->sum('quantity');
        $damaged = $this->outputs->where('quality', 'damaged')->sum('quantity');
        $materials = $this->materials->sum('quantity');
        
        $yield = $materials > 0 ? ($good / $materials) * 100 : 100;
        
        return [
            'good' => $good,
            'damaged' => $damaged,
            'materials' => $materials,
            'yield' => $yield
        ];
    }
}
