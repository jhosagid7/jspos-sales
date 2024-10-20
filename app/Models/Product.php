<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;


    protected $fillable = [
        'sku',
        'name',
        'description',
        'type',
        'status',
        'cost',
        'price',
        'manage_stock',
        'stock_qty',
        'low_stock',
        'supplier_id',
        'category_id'
    ];

    //relationships

    public function priceList(): HasMany
    {
        return $this->hasMany(PriceList::class);
    }

    function sales()
    {
        return $this->hasMany(SaleDetail::class);
    }

    function purchases()
    {
        return $this->hasMany(PurchaseDetail::class);
    }

    function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function images()
    {
        return $this->morphMany(Image::class, 'model');
    }

    public function latestImage()
    {
        //recent image
        return $this->morphOne(Image::class, 'model')->latestOfMany();
    }

    //accessors
    public function getPhotoAttribute()
    {
        if (count($this->images)) {
            return  "storage/products/" . $this->images->last()->file;
        } else {
            return asset('noimage.jpg');
        }
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }


    //scope
    public function scopeSearch($query, $term)
    {
        return $query->with(['category', 'supplier', 'priceList'])
            ->where('name', 'like', '%' . $term . '%')
            ->orWhere('description', 'like', '%' . $term . '%')
            ->orWhere('sku', 'like', '%' . $term . '%')
            ->orWhereHas('category', function ($query) use ($term) {
                $query->where('name', 'like', '%' . $term . '%');
            });
    }


    //appends


}
