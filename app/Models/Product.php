<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\OrderDetail;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Product extends Model
{
    use HasFactory, \Illuminate\Database\Eloquent\SoftDeletes, LogsActivity;

    public $auditEventContext = 'SISTEMA';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'sku', 'name', 'description', 'cost', 'price', 
                'stock_qty', 'manage_stock', 'category_id', 'status', 'low_stock'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Este producto ha sido {$eventName}");
    }

    public function tapActivity(\Spatie\Activitylog\Models\Activity $activity, string $eventName)
    {
        $properties = $activity->properties->toArray();
        $properties['source'] = $this->auditEventContext ?? 'SISTEMA';
        $activity->properties = collect($properties);
    }

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
        'production_target_id',
        'max_stock',
        'brand',
        'presentation',
        'is_pre_assembled',
        'additional_cost',
        'allow_decimal',
        'is_variable_quantity',
        'show_in_sales',
        'freight_type',
        'freight_value',
        'price_group_id',
        'is_raw_material'
    ];

    protected $casts = [
        'show_in_sales' => 'boolean',
        'is_raw_material' => 'boolean',
    ];

    //relationships

    public function priceTiers()
    {
        return $this->hasMany(ProductPriceTier::class);
    }

    public function priceGroup()
    {
        return $this->belongsTo(PriceGroup::class);
    }

    public function items()
    {
        return $this->hasMany(ProductItem::class);
    }

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

    public function productionTarget()
    {
        return $this->belongsTo(Product::class, 'production_target_id');
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
        // Consider as reserved any item in an order that is NOT yet paid/finalized and not returned
        return OrderDetail::where('product_id', $this->id)
            ->where('warehouse_id', $warehouseId)
            ->whereHas('order', function ($query) {
                $query->whereNotIn('status', ['paid', 'returned', 'cancelled', 'annulled', 'processed', 'deleted']);
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
        $term = trim($term);
        $tokens = explode(' ', $term);

        $query->with(['category', 'supplier', 'priceList', 'tags', 'images'])
            ->where(function ($q) use ($tokens) {
                foreach ($tokens as $token) {
                    if (!empty($token)) {
                        $cleanToken = preg_replace('/[^0-9a-zA-Z]/', '', $token);
                        $numericToken = preg_replace('/[^0-9]/', '', $token);

                        $q->where(function ($subQuery) use ($token, $cleanToken, $numericToken) {
                            $subQuery->where('name', 'like', '%' . $token . '%')
                                ->orWhere('description', 'like', '%' . $token . '%')
                                ->orWhere('sku', 'like', '%' . $token . '%')
                                ->orWhereHas('category', function ($catQuery) use ($token) {
                                    $catQuery->where('name', 'like', '%' . $token . '%');
                                })
                                ->orWhereHas('tags', function ($tagQuery) use ($token) {
                                    $tagQuery->where('name', 'like', '%' . $token . '%');
                                });
                            
                            // Fuzzy match for alphanumeric (removes spaces/symbols)
                            // Only run if the token actually contained special characters/spaces that were stripped
                            if (!empty($cleanToken) && strlen($cleanToken) > 1 && $cleanToken !== $token) {
                                $subQuery->orWhereRaw("REGEXP_REPLACE(name, '[^0-9a-zA-Z]', '') LIKE ?", ["%{$cleanToken}%"])
                                         ->orWhereRaw("REGEXP_REPLACE(sku, '[^0-9a-zA-Z]', '') LIKE ?", ["%{$cleanToken}%"]);
                            }

                            // Fuzzy match for dimensions/numbers (removes letters like 'X' or 'x')
                            // Only run if the token had letters/symbols stripped (e.g. '40x50' -> '4050') OR if it is purely numeric and has at least 3 digits
                            $isPurelyNumeric = ctype_digit($token);
                            $shouldFuzzyNumeric = ($numericToken !== $token) || ($isPurelyNumeric && strlen($token) >= 3);

                            if (!empty($numericToken) && strlen($numericToken) > 1 && $shouldFuzzyNumeric) {
                                $subQuery->orWhereRaw("REGEXP_REPLACE(name, '[^0-9]', '') LIKE ?", ["%{$numericToken}%"])
                                         ->orWhereRaw("REGEXP_REPLACE(sku, '[^0-9]', '') LIKE ?", ["%{$numericToken}%"]);
                            }
                        });
                    }
                }
            });

        // Add relevance ordering
        // 1. Exact SKU match
        // 2. Name starts with term
        // 3. Name contains term
        // 4. Everything else (Category match, Description match)
        return $query->orderByRaw("CASE 
            WHEN sku LIKE ? THEN 1 
            WHEN name LIKE ? THEN 2 
            WHEN name LIKE ? THEN 3 
            ELSE 4 END", 
            ["{$term}%", "{$term}%", "%{$term}%"]
        )
        ->orderByRaw("REPLACE(name, '  ', ' ') ASC");
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

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'product_tags');
    }
}
