<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'taxpayer_id',
        'address',
        'password',
        'profile',
        'commission_percentage',
        'seller_commission_1_threshold',
        'seller_commission_1_percentage',
        'seller_commission_2_threshold',
        'seller_commission_2_percentage',
        'profile_photo_path',
        'warehouse_id',
        'printer_name',
        'printer_width',
        'is_network',
        'printer_user',
        'printer_user',
        'printer_password',
        'seller_allow_credit',
        'seller_credit_days',
        'seller_credit_limit',
        'seller_usd_payment_discount',
        'seller_usd_payment_discount_tag',
        'theme',
        'sales_view_mode',
        'order_deadline_at',
        'is_deadline_active',
        'monthly_goal',
        'route_goal',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_network' => 'boolean',
        'is_deadline_active' => 'boolean',
        'order_deadline_at' => 'datetime',
    ];

    /**
     * Format date for datetime-local input
     */
    public function getOrderDeadlineAtAttribute($value)
    {
        return $value ? \Carbon\Carbon::parse($value)->format('Y-m-d\TH:i') : null;
    }

    public function getThemeAttribute($value)
    {
        if (is_null($value)) return [];
        
        // Attempt to decode
        $decoded = json_decode($value, true);
        
        // Handle double encoding (string inside string)
        if (is_string($decoded)) {
            $decoded = json_decode($decoded, true);
        }
        
        return is_array($decoded) ? $decoded : [];
    }

    public function setThemeAttribute($value)
    {
        $this->attributes['theme'] = is_array($value) ? json_encode($value) : $value;
    }


    function sales()
    {
        return $this->hasMany(Sale::class);
    }

    function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function sellerConfigs()
    {
        return $this->hasMany(SellerConfig::class);
    }

    public function latestSellerConfig()
    {
        return $this->hasOne(SellerConfig::class)->latestOfMany();
    }
    public function deliveries()
    {
        return $this->hasMany(Sale::class, 'driver_id');
    }

    public function locations()
    {
        return $this->hasMany(DriverLocation::class, 'driver_id');
    }

    public function customers()
    {
        return $this->hasMany(Customer::class, 'seller_id');
    }

    public function banks()
    {
        return $this->belongsToMany(Bank::class);
    }

    public function sharedSellers()
    {
        return $this->belongsToMany(User::class, 'seller_sharing', 'user_id', 'shared_seller_id');
    }

    public function getSharedSellerIds()
    {
        $sharedIds = $this->sharedSellers()->pluck('shared_seller_id')->toArray();
        return array_merge([$this->id], $sharedIds);
    }

    /**
     * Scope to get all users considered "Sellers" based on permissions.
     */
    public function scopeSellers($query)
    {
        return $query->select('users.*')
            ->where(function($q) {
                $q->whereHas('roles', function($rq) {
                    $rq->whereIn('name', ['Vendedor Foraneo', 'Vendedor foraneo']);
                })
                ->orWhere('users.name', 'OFICINA')
                ->orWhere('users.email', 'oficina@gmail.com')
                ->orWhere('users.email', 'oficina@example.com');
            })
            ->distinct();
    }

    public function commissionGoals()
    {
        return $this->belongsToMany(CommissionGoal::class, 'user_commission_goals')->withTimestamps();
    }

    public function scopeEligibleSellers($query)
    {
        return $query->where(function($q) {
            $q->whereHas('commissionGoals', function($subQ) {
                $subQ->where('is_active', true);
            })
            ->orWhereHas('roles', function($rq) {
                $rq->whereIn('name', ['Vendedor Foraneo', 'Vendedor foraneo', 'Vendedor', 'vendedor']);
            })
            ->orWhere('users.name', 'OFICINA')
            ->orWhere('users.email', 'oficina@gmail.com');
        })
        ->distinct();
    }

    /**
     * Scope to get all users considered "Drivers" based on permissions.
     */
    public function scopeDrivers($query)
    {
        return $query->permission('distribution.map'); // Or the specific driver permission
    }
}
