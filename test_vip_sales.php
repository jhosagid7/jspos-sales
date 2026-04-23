<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\Customer::find(115);
$sales = \App\Models\Sale::where('customer_id', $user->id)
    ->where('type', 'credit')
    ->where('status', 'pending')
    ->with(['payments', 'returns', 'customer'])
    ->orderBy('id', 'desc')
    ->get();

$primaryCurrency = \App\Models\Currency::where('is_primary', true)->first();

$formattedSales = $sales->map(function($sale) use ($primaryCurrency) {
    try {
    $totalPaidUSD = $sale->payments->whereNotIn('status', ['pending', 'rejected'])->sum(function($p) {
        $rate = $p->exchange_rate > 0 ? $p->exchange_rate : 1;
        $amountUSD = $p->amount / $rate; 
        $discountVal = $p->discount_applied ?? 0;
        return ($p->rule_type === 'overdue') ? ($amountUSD - $discountVal) : ($amountUSD + $discountVal);
    });
    
    $initialPaidUSD = $sale->paymentDetails->sum(function($detail) {
        $rate = $detail->exchange_rate > 0 ? $detail->exchange_rate : 1;
        return $detail->amount / $rate;
    });

    $totalReturnsUSD = $sale->returns->where('refund_method', 'debt_reduction')->sum('total_returned') / ($sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1);
    
    $totalUSD = $sale->total_usd ?: ($sale->total / ($sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1));
    
    $debtUSD = max(0, $totalUSD - ($totalPaidUSD + $initialPaidUSD + $totalReturnsUSD));

    $startDate = $sale->delivered_at ? \Carbon\Carbon::parse($sale->delivered_at) : \Carbon\Carbon::parse($sale->created_at);
    $creditDays = $sale->credit_days ?? ($sale->customer->credit_days ?? 0);
    $dueDate = $startDate->copy()->addDays($creditDays);
    
    $now = \Carbon\Carbon::now()->startOfDay();
    $due = $dueDate->copy()->startOfDay();
    $daysOverdue = (int) $due->diffInDays($now, false);

    return [
        'id' => $sale->id,
        'invoice_number' => $sale->invoice_number ?? "F-" . str_pad($sale->id, 6, '0', STR_PAD_LEFT),
        'date' => $sale->created_at->format('Y-m-d'),
        'due_date' => $dueDate->format('Y-m-d'),
        'days_overdue' => $daysOverdue,
        'total_usd' => round($totalUSD, 2),
        'debt_usd' => round($debtUSD, 2),
        'total_display' => round($totalUSD * $primaryCurrency->exchange_rate, 2),
        'debt_display' => round($debtUSD * $primaryCurrency->exchange_rate, 2),
        'currency_symbol' => $primaryCurrency->symbol
    ];
    } catch (\Exception $e) {
        return "ERROR: " . $e->getMessage();
    }
});

echo json_encode($formattedSales, JSON_PRETTY_PRINT);
