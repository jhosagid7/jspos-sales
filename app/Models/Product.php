<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\OrderDetail;

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
        'category_id',
        'max_stock',
        'brand',
        'presentation',
        'is_pre_assembled',
        'additional_cost'
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

    public function warehouses()
    {
        return $this->belongsToMany(Warehouse::class, 'product_warehouse')
            ->withPivot('stock_qty')
            ->withTimestamps();
    }

    public function productSuppliers()
    {
        return $this->hasMany(ProductSupplier::class);
    }

    public function productWarehouses()
    {
        return $this->hasMany(ProductWarehouse::class);
    }

    public function units()
    {
        return $this->hasMany(ProductUnit::class);
    }

    public function stockIn($warehouseId)
    {
        $warehouse = $this->warehouses()->where('warehouse_id', $warehouseId)->first();
        return $warehouse ? $warehouse->pivot->stock_qty : 0;
    }

    public function getReservedStock($warehouseId)
    {
        return OrderDetail::where('product_id', $this->id)
            ->where('warehouse_id', $warehouseId)
            ->whereHas('order', function ($query) {
                $query->where('status', 'pending');
            })
            ->sum('quantity');
    }


    public function getCheapestSupplier()
    {
        return $this->productSuppliers()->orderBy('cost', 'asc')->first();
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


    public function components()
    {
        return $this->belongsToMany(Product::class, 'product_components', 'parent_product_id', 'child_product_id')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function parents()
    {
        return $this->belongsToMany(Product::class, 'product_components', 'child_product_id', 'parent_product_id')
            ->withPivot('quantity')
            ->withTimestamps();
    }
}
