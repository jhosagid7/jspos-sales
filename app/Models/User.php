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
        'weekly_salary',
        'work_days_per_week',
        'pay_partial_packages',
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
        'weekly_salary' => 'decimal:2',
        'work_days_per_week' => 'integer',
        'pay_partial_packages' => 'boolean',
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
        $config = \App\Models\Configuration::first();
        $calcMode = $config->commission_calculation_mode ?? 'percentage_threshold';

        if ($calcMode === 'tiered_goals') {
            return $query->whereHas('commissionGoals', function($subQ) {
                $subQ->where('is_active', true);
            })->distinct();
        }

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

    public function isSuperAdmin(): bool
    {
        return in_array(strtolower($this->role ?? ''), ['superadmin', 'super admin', 'super_admin'])
            || (method_exists($this, 'hasRole') && $this->hasRole(['Super Admin', 'superadmin']));
    }

    public function isAdmin(): bool
    {
        return in_array(strtolower($this->role ?? ''), ['admin', 'administrador'])
            || (method_exists($this, 'hasRole') && $this->hasRole(['Admin', 'admin']))
            || $this->isSuperAdmin();
    }

    public function isSupervisor(): bool
    {
        return strtolower($this->role ?? '') === 'supervisor'
            || (method_exists($this, 'hasRole') && $this->hasRole(['Supervisor', 'supervisor']));
    }

    public function isOperator(): bool
    {
        return in_array(strtolower($this->role ?? ''), ['operario', 'operator'])
            || (method_exists($this, 'hasRole') && $this->hasRole(['Operario', 'operario', 'Operator']));
    }

    public function isOperario(): bool
    {
        return $this->isOperator();
    }

    public function isWarehouse(): bool
    {
        return in_array(strtolower($this->role ?? $this->profile ?? ''), ['almacen', 'warehouse'])
            || (method_exists($this, 'hasRole') && $this->hasRole(['Almacen', 'almacen', 'Warehouse']));
    }

    public function isAlmacen(): bool
    {
        return $this->isWarehouse();
    }

    public function getRoleAttribute()
    {
        return $this->attributes['role'] ?? $this->attributes['profile'] ?? null;
    }

    public function setRoleAttribute($value)
    {
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'role')) {
            $this->attributes['role'] = $value;
        }
        $this->attributes['profile'] = $value;
    }

    /**
     * Calculate daily salary based on weekly salary and configured work days (5, 6 or 7).
     */
    public function getDailySalaryAttribute(): float
    {
        $days = max(1, (int)($this->work_days_per_week ?: 6));
        $salary = (float)($this->weekly_salary ?: 90.00);
        return round($salary / $days, 4);
    }

    /**
     * Calculate labor tariffs per bulto and per millar for a given bag product.
     */
    public function calculateLaborTariff($product): array
    {
        $dailySalary = $this->daily_salary;
        $targetUnits = max(1, (int)($product->target_units_per_shift ?? 1));
        $packageTariff = round($dailySalary / $targetUnits, 4);
        $millarPerBulto = max(1, (float)($product->millar_per_bulto ?? 1));
        $fractionTariff = round($packageTariff / $millarPerBulto, 4);

        return [
            'daily_salary'    => $dailySalary,
            'package_tariff'  => $packageTariff,
            'fraction_tariff' => $fractionTariff,
        ];
    }

    /**
     * Get operator shift earnings breakdown.
     */
    public function getShiftEarnings($shiftId): array
    {
        $productions = \App\Models\BagProduction::where('user_id', $this->id)
            ->where('bag_shift_id', $shiftId)
            ->get();

        $earned = (float)$productions->sum('labor_earned_amount');
        $retained = (float)$productions->sum('labor_retained_amount');
        $available = (float)($earned - $retained);
        $completedPackages = (float)$productions->sum('completed_packages_count');
        $fractionalUnits = (float)$productions->sum('fractional_units');

        return [
            'earned'             => round($earned, 2),
            'available'          => round($available, 2),
            'retained'           => round($retained, 2),
            'completed_packages' => $completedPackages,
            'fractional_units'   => $fractionalUnits,
            'count'              => $productions->count(),
        ];
    }

    /**
     * Get operator weekly earnings breakdown.
     */
    public function getWeeklyEarnings($startDate = null, $endDate = null): array
    {
        $start = $startDate ? \Carbon\Carbon::parse($startDate)->startOfDay() : now()->startOfWeek(\Carbon\Carbon::MONDAY)->startOfDay();
        $end = $endDate ? \Carbon\Carbon::parse($endDate)->endOfDay() : now()->endOfWeek(\Carbon\Carbon::SUNDAY)->endOfDay();

        $productions = \App\Models\BagProduction::where('user_id', $this->id)
            ->whereBetween('recorded_at', [$start, $end])
            ->get();

        $earned = (float)$productions->sum('labor_earned_amount');
        $retained = (float)$productions->sum('labor_retained_amount');
        $available = (float)($earned - $retained);
        $completedPackages = (float)$productions->sum('completed_packages_count');
        $fractionalUnits = (float)$productions->sum('fractional_units');

        return [
            'start_date'         => $start->toDateString(),
            'end_date'           => $end->toDateString(),
            'weekly_salary'      => (float)($this->weekly_salary ?: 90.00),
            'work_days'          => (int)($this->work_days_per_week ?: 6),
            'daily_salary'       => $this->daily_salary,
            'earned'             => round($earned, 2),
            'available'          => round($available, 2),
            'retained'           => round($retained, 2),
            'completed_packages' => $completedPackages,
            'fractional_units'   => $fractionalUnits,
        ];
    }
}
