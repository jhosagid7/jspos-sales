<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

config(['database.connections.mysql.database' => 'pruebabderrorcobranza']);
\DB::purge('mysql');

use App\Models\Sale;
use App\Models\Payment;
use App\Models\SalePaymentDetail;
use App\Models\Currency;
use Carbon\Carbon;

$dateFrom = '2026-05-26';
$dateTo = '2026-05-26';
$user_id = 0;

$dFrom = Carbon::parse($dateFrom)->startOfDay();
$dTo = Carbon::parse($dateTo)->endOfDay();

$currencies = Currency::orderBy('is_primary', 'desc')->get();
$primaryCurrency = $currencies->firstWhere('is_primary', 1);
$primaryRate = $primaryCurrency ? $primaryCurrency->exchange_rate : 1;
$primaryCode = $primaryCurrency ? $primaryCurrency->code : 'COP';

$sales = Sale::whereBetween('created_at', [$dFrom, $dTo])
        ->where('status', '<>', 'returned')
        ->whereNull('deletion_approved_at')
        ->get();

$saleIds = $sales->pluck('id');

$salePaymentDetails = SalePaymentDetail::with(['sale', 'sale.customer', 'zelleRecord', 'bankRecord'])
    ->whereIn('sale_id', $saleIds)
    ->whereBetween('created_at', [$dFrom, $dTo])
    ->get();

$creditPayments = Payment::with(['sale', 'sale.customer', 'zelleRecord', 'bankRecord'])
    ->whereBetween('created_at', [$dFrom, $dTo])
    ->where('status', 'approved')
    ->get();

$getVoucherDate = function($payment, $method) {
    if ($method === 'zelle' && $payment->zelleRecord) {
        return Carbon::parse($payment->zelleRecord->zelle_date ?? $payment->payment_date ?? $payment->created_at)->format('d/m/Y');
    }
    if (($method === 'bank' || $method === 'deposit') && $payment->bankRecord) {
        return Carbon::parse($payment->bankRecord->payment_date ?? $payment->payment_date ?? $payment->created_at)->format('d/m/Y');
    }
    $date = $payment->payment_date ?? $payment->created_at;
    return Carbon::parse($date)->format('d/m/Y');
};

$configuredBanks = \App\Models\Bank::all();
$getBankAccountSuffix = function($bankName, $recordAccountNumber = null) use ($configuredBanks) {
    $accNum = null;
    if (!empty($recordAccountNumber)) {
        $cleanRec = preg_replace('/[^0-9]/', '', $recordAccountNumber);
        if (strlen($cleanRec) >= 6) {
            $accNum = $recordAccountNumber;
        }
    }
    if (empty($accNum) && !empty($bankName)) {
        $normalizedInput = preg_replace('/[^a-zA-Z0-9]/', '', strtolower(trim($bankName)));
        $match = $configuredBanks->first(function($b) use ($normalizedInput) {
            $normalizedBankName = preg_replace('/[^a-zA-Z0-9]/', '', strtolower(trim($b->name)));
            return $normalizedBankName === $normalizedInput;
        });
        if ($match && !empty($match->account_number)) {
            $accNum = $match->account_number;
        }
    }
    if (!empty($accNum)) {
        $cleanAcc = preg_replace('/[^0-9]/', '', $accNum);
        if (strlen($cleanAcc) >= 6) {
            return ' (*' . substr($cleanAcc, -6) . ')';
        }
        return ' (*' . $accNum . ')';
    }
    return '';
};

$digitalPayments = [
    'sales' => ['bank' => [], 'zelle' => []],
    'credits' => ['bank' => [], 'zelle' => []],
    'unified' => ['bank' => [], 'zelle' => []]
];

// Process Sales
foreach($salePaymentDetails->whereIn('payment_method', ['bank', 'deposit', 'zelle']) as $pd) {
    $method = $pd->payment_method === 'zelle' ? 'zelle' : 'bank';
    $bankName = $pd->bank_name ?? 'Banco / Otros';
    $curr = $pd->currency_code;
    $voucherDate = $getVoucherDate($pd, $pd->payment_method);
    
    $suffix = $getBankAccountSuffix($bankName, $pd->account_number);
    $bankKey = $bankName . $suffix;

    $item = [
        'date' => $voucherDate,
        'ref' => $pd->reference_number ?? ($pd->zelleRecord->reference ?? 'N/A'),
        'customer' => $pd->sale->customer->name ?? 'Consumidor Final',
        'amount' => $pd->amount,
        'currency' => $curr,
    ];

    if ($method === 'bank') {
        $digitalPayments['sales']['bank'][$bankKey][$curr][] = $item;
        $digitalPayments['unified']['bank'][$bankKey][$curr][] = $item;
    } else {
        $digitalPayments['sales']['zelle'][] = $item;
        $digitalPayments['unified']['zelle'][] = $item;
    }
}

// Process Credits
foreach($creditPayments->whereIn('pay_way', ['bank', 'deposit', 'zelle']) as $p) {
    $method = $p->pay_way === 'zelle' ? 'zelle' : 'bank';
    $bankName = $p->bank ?? 'Banco / Otros';
    $curr = $p->currency ?? $primaryCode;
    $voucherDate = $getVoucherDate($p, $p->pay_way);

    $suffix = $getBankAccountSuffix($bankName, $p->account_number);
    $bankKey = $bankName . $suffix;

    $item = [
        'date' => $voucherDate,
        'ref' => $p->deposit_number ?? ($p->zelleRecord->reference ?? 'N/A'),
        'customer' => $p->sale->customer->name ?? 'Cliente Crédito',
        'amount' => $p->amount,
        'currency' => $curr,
    ];

    if ($method === 'bank') {
        $digitalPayments['credits']['bank'][$bankKey][$curr][] = $item;
        $digitalPayments['unified']['bank'][$bankKey][$curr][] = $item;
    } else {
        $digitalPayments['credits']['zelle'][] = $item;
        $digitalPayments['unified']['zelle'][] = $item;
    }
}

echo "--- DUMPING digitalPayments['unified']['bank'] ---\n";
print_r($digitalPayments['unified']['bank']);
