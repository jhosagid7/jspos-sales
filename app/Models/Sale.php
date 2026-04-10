<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'total',
        'total_usd',
        'discount',
        'items',
        'status',
        'customer_id',

        'user_id',
        'type',
        'cash',
        'change',
        'notes',
        'primary_currency_code',
        'primary_exchange_rate',
        'invoice_number',
        'order_number',
        'seller_config_id',
        'applied_commission_percent',
        'applied_freight_percent',
        'applied_exchange_diff_percent',
        'is_foreign_sale',
        'final_commission_amount',
        'commission_status',
        'commission_paid_at',
        'commission_payment_method',
        'commission_payment_reference',
        'commission_payment_bank_name',
        'commission_payment_currency',
        'commission_payment_rate',
        'commission_payment_amount',
        'commission_payment_notes',
        'batch_name',
        'batch_sequence',
        'credit_days',
        'driver_id',
        'delivery_status',
        'delivered_at',
        'credit_rules_snapshot',
        'deletion_requested_at',
        'deletion_reason',
        'deletion_requested_by',
        'deletion_approved_by',
        'deletion_approved_at',
        'is_freight_broken_down',
        'seller_tier_1_days',
        'seller_tier_1_percent',
        'seller_tier_2_days',
        'seller_tier_2_percent',
    ];

    protected $casts = [
        'is_foreign_sale' => 'boolean',
        'is_freight_broken_down' => 'boolean',
        'applied_commission_percent' => 'decimal:2',
        'final_commission_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'credit_rules_snapshot' => 'array',
        'deletion_requested_at' => 'datetime',
        'deletion_approved_at' => 'datetime',
    ];

    function details()
    {
        return $this->hasMany(SaleDetail::class);
    }

    function customer()
    {
        return $this->belongsTo(Customer::class)->select('id', 'name', 'address', 'city', 'phone', 'taxpayer_id', 'email', 'seller_id', 'allow_credit', 'credit_days', 'credit_limit', 'usd_payment_discount', 'whatsapp_notify_sales', 'whatsapp_notify_payments', 'wallet_balance');
    }

    function user()
    {
        return $this->belongsTo(User::class)->select('id', 'name');
    }

    public function sellerConfig()
    {
        return $this->belongsTo(SellerConfig::class);
    }

    function payments()
    {
        return $this->hasMany(Payment::class)->orderBy('id', 'desc');
    }

    function changeDetails()
    {
        return $this->hasMany(SaleChangeDetail::class);
    }

    function paymentDetails()
    {
        return $this->hasMany(SalePaymentDetail::class);
    }

    public function returns()
    {
        return $this->hasMany(SaleReturn::class);
    }

    //scopes
    // public function scopeWithDebt($query)
    // {
    //     return $query->addSelect([
    //         'debt' => DB::raw('total - total_payments')
    //     ])->withSum('payments', 'amount');
    // }

    //accessors
    public function getDebtAttribute()
    {
        // Exclude 'pending', 'rejected' or 'voided' payments (only count approved)
        $totalPays = $this->payments->where('status', 'approved')->sum('amount');
        
        // Deduct returns that were applied directly to the debt
        $totalReturns = $this->returns->where('refund_method', 'debt_reduction')->where('status', 'approved')->sum('total_returned');
        
        $debt = $this->total - $totalPays - $totalReturns;

        return $debt > 0 ? $debt : 0;
    }

    public function getDaysOverdueAttribute()
    {
        $creditDays = $this->credit_days ?? 0;
        
        if ($creditDays <= 0) {
            return 0;
        }

        // Use delivered_at if available, otherwise fallback to created_at (or maybe should be null if not delivered?)
        // For now, fallback to created_at to maintain backward compatibility for old sales.
        $startDate = $this->delivered_at ? \Carbon\Carbon::parse($this->delivered_at) : \Carbon\Carbon::parse($this->created_at);
        $dueDate = $startDate->addDays($creditDays);

        if ($this->status == 'paid') {
            $lastPayment = $this->payments->first(); // Ordered by id desc in relationship
            if ($lastPayment) {
                $paymentDate = \Carbon\Carbon::parse($lastPayment->created_at);
                return $dueDate->diffInDays($paymentDate, false);
            }
            return 0;
        }
        
        // Return signed difference: 
        // Negative = Days remaining (e.g. -5)
        // Positive = Days overdue (e.g. +5)
        // Zero = Due today
        return $dueDate->diffInDays(\Carbon\Carbon::now(), false);
    }
    public function getIsWithinEditWindowAttribute()
    {
        // Voided or returned sales cannot be edited
        if ($this->status === 'returned' || $this->status === 'voided' || $this->status === 'cancelled' || $this->status === 'anulated') {
            return false;
        }

        $config = \App\Models\Configuration::first();
        $timeoutSeconds = $config->sales_edit_timeout ?? 1800; // default 30 min = 1800s
        
        return $this->created_at->addSeconds($timeoutSeconds)->isFuture();
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'deletion_requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'deletion_approved_by');
    }

    public function deliveryLocations()
    {
        return $this->hasMany(DeliveryLocation::class);
    }

    public function deliveryCollections()
    {
        return $this->hasMany(DeliveryCollection::class);
    }

    public function history()
    {
        return $this->hasMany(SaleHistoryLog::class)->orderBy('created_at', 'desc');
    }

    public function checkSettlement()
    {
        $this->refresh();
        
        $currentTotalPaidUSD = $this->payments->where('status', 'approved')->sum(function($p) {
            $rate = $p->exchange_rate > 0 ? $p->exchange_rate : 1;
            $amountUSD = $p->amount / $rate; 
            
            $adjustmentUSD = $p->discount_applied ?? 0;
            
            if ($p->rule_type === 'overdue') {
                return $amountUSD - $adjustmentUSD;
            } else {
                return $amountUSD + $adjustmentUSD;
            }
        });
        
        $initialPaidUSD = $this->paymentDetails->sum(function($detail) {
            $rate = $detail->exchange_rate > 0 ? $detail->exchange_rate : 1;
            return $detail->amount / $rate;
        });

        $totalReturnsOrig = $this->returns->where('refund_method', 'debt_reduction')->where('status', 'approved')->sum('total_returned');
        $exchangeRateReturns = $this->primary_exchange_rate > 0 ? $this->primary_exchange_rate : 1;
        $totalReturnsUSD = $totalReturnsOrig / $exchangeRateReturns;
        
        $grandTotalPaidUSD = $currentTotalPaidUSD + $initialPaidUSD + $totalReturnsUSD;
        
        if ($grandTotalPaidUSD >= ($this->total_usd - 0.01)) {
            $this->update(['status' => 'paid']);
            
            Payment::where('sale_id', $this->id)
                ->where('status', 'approved')
                ->where('created_at', '>=', \Carbon\Carbon::now()->subMinute())
                ->update(['type' => 'settled']);

            // NEW: Update Variable Items to 'sold' if they were previously 'reserved'
            foreach ($this->details as $detail) {
                if ($detail->metadata) {
                    $meta = json_decode($detail->metadata, true);
                    if (isset($meta['product_item_id'])) {
                        $pi = \App\Models\ProductItem::find($meta['product_item_id']);
                        if ($pi && $pi->status === 'reserved') {
                            $pi->status = 'sold';
                            $pi->save();
                        }
                    }
                }
            }
            
            // COMMISSION CALCULATION (Fix: Use Payment Date)
            $lastPaymentDate = $this->payments->where('status', 'approved')->max('payment_date');
            if (!$lastPaymentDate) $lastPaymentDate = now();
                
            \App\Services\CommissionService::calculateCommission($this, $lastPaymentDate);
        } else {
            // Revert status to pending if it was previously paid but no longer meets settlement criteria
            if ($this->status === 'paid') {
                $this->update(['status' => 'pending']);
            }
        }
    }
}
