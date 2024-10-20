<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name'];


    //relationship
    public function image()
    {
        return  $this->morphOne(Image::class, 'model'); //->withDefault();
    }

    // accesors
    public function getPictureAttribute()
    {
        $img = $this->image; //->file;

        if ($img != null) {
            if (file_exists('storage/categories/' . $img->file))
                return 'storage/categories/' . $img->file;
            else
                return 'storage/image-not-found.png';
        }

        return asset('noimage.jpg');
    }


    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
