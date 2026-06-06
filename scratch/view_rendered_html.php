<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\CollectionSheet;
use App\Models\Configuration;
use App\Models\Currency;
use App\Models\Bank;
use App\Models\SaleReturn;

$sheet = CollectionSheet::find(71);
$query = $sheet->payments()->with(['sale.customer', 'user', 'zelleRecord'])->whereIn('status', ['approved', 'voided']);
$payments = $query->get();
$returns = SaleReturn::where('collection_sheet_id', $sheet->id)->with(['sale.customer', 'user'])->get();
$config = Configuration::first();
$user = auth()->user() ?: \App\Models\User::first();
$date = \Carbon\Carbon::now()->format('d/m/Y H:i');
$currencies = Currency::all();
$banks = Bank::all();

$totalsByCategory = [];
foreach($currencies as $c) {
    $totalsByCategory["EFECTIVO " . strtoupper($c->code)] = 0;
}
foreach($banks as $b) {
    $totalsByCategory[strtoupper($b->name)] = 0;
}
$totalsByCategory['NOTAS DE CREDITO (NC)'] = $returns->sum(function($r) {
    $rate = $r->sale->primary_exchange_rate > 0 ? $r->sale->primary_exchange_rate : 1;
    return $r->total_returned / $rate;
});

foreach($payments as $p) {
    if ($p->status == 'voided') continue;
    
    $amtUSD = $p->amount / ($p->exchange_rate > 0 ? $p->exchange_rate : 1);
    if ($p->pay_way == 'cash') {
        $key = "EFECTIVO " . strtoupper($p->currency);
        $totalsByCategory[$key] = ($totalsByCategory[$key] ?? 0) + $amtUSD;
    } else {
        $bankName = $p->bank ? strtoupper($p->bank) : ($p->pay_way == 'zelle' ? 'ZELLE' : null);
        if ($bankName) {
            $totalsByCategory[$bankName] = ($totalsByCategory[$bankName] ?? 0) + $amtUSD;
        } else {
            $othersKey = 'OTROS (BANCOS/MEDIOS)';
            $totalsByCategory[$othersKey] = ($totalsByCategory[$othersKey] ?? 0) + $amtUSD;
        }
    }
}

$totalsByCurrency = [];
$uniqueCurrencies = $payments->pluck('currency')->unique();
foreach($uniqueCurrencies as $currencyCode) {
    $totalsByCurrency[$currencyCode] = $payments->where('currency', $currencyCode)->where('status', 'approved')->sum('amount');
}

$dateFrom = '2026-06-04';
$dateTo = '2026-06-04';

$html = view('reports.collection-relationship-new-pdf', compact('sheet', 'payments', 'returns', 'config', 'user', 'date', 'totalsByCategory', 'totalsByCurrency', 'dateFrom', 'dateTo'))->render();

// Let's parse the table rows using simple regex or DOMDocument
$dom = new DOMDocument();
libxml_use_internal_errors(true);
$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
libxml_clear_errors();

$xpath = new DOMXPath($dom);
$rows = $xpath->query('//table[@class="table"]/tbody/tr');

echo "Total rows found in table: " . $rows->length . "\n";
foreach ($rows as $index => $row) {
    $text = trim($row->textContent);
    $text = preg_replace('/\s+/', ' ', $text);
    echo "Row $index: $text\n";
}
