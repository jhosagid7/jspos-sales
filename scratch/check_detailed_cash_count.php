<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

config(['database.connections.mysql.database' => 'pruebabderrorcobranza']);
\DB::purge('mysql');

use Carbon\Carbon;
use App\Models\Payment;
use App\Models\CollectionSheet;

$dateFrom = '2026-06-04';
$dateTo = '2026-06-04';

$dFrom = Carbon::parse($dateFrom)->startOfDay();
$dTo = Carbon::parse($dateTo)->endOfDay();

$sheets = CollectionSheet::whereBetween('opened_at', [$dFrom, $dTo])->get();
$sheetIds = $sheets->pluck('id');

echo "Sheets found: " . implode(', ', $sheetIds->toArray()) . "\n";

$creditPayments = Payment::with(['sale.customer', 'zelleRecord', 'bankRecord'])
    ->whereIn('collection_sheet_id', $sheetIds)
    ->where('status', 'approved')
    ->get();

echo "Credit Payments Count: " . $creditPayments->count() . "\n";

$targetIds = [1275, 1295, 1296, 1297, 1305, 1310];
foreach ($targetIds as $tid) {
    $found = $creditPayments->contains('id', $tid);
    $payment = $creditPayments->firstWhere('id', $tid);
    echo "Payment ID $tid found in Cash Count query: " . ($found ? "YES" : "NO");
    if ($found) {
        $vDate = 'N/A';
        if ($payment->pay_way === 'zelle' && $payment->zelleRecord) {
            $vDate = Carbon::parse($payment->zelleRecord->zelle_date ?? $payment->payment_date ?? $payment->created_at)->format('d/m/Y');
        } elseif (($payment->pay_way === 'bank' || $payment->pay_way === 'deposit') && $payment->bankRecord) {
            $vDate = Carbon::parse($payment->bankRecord->payment_date ?? $payment->payment_date ?? $payment->created_at)->format('d/m/Y');
        } else {
            $vDate = Carbon::parse($payment->payment_date ?? $payment->created_at)->format('d/m/Y');
        }
        echo " | Pay Way: {$payment->pay_way} | Ref: " . ($payment->deposit_number ?? $payment->zelleRecord->reference ?? 'N/A') . " | Voucher Date: $vDate\n";
    } else {
        echo "\n";
    }
}
