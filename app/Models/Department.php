<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'report_type'];

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = \App\Helpers\NameNormalizer::uppercase($value);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }
}
