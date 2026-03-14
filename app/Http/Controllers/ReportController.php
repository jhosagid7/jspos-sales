<?php

namespace App\Http\Controllers;

use App\Models\CollectionSheet;
use App\Models\SaleReturn;
use App\Models\Configuration;
use App\Models\Bank;
use App\Models\Currency;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function collectionRelationshipPdf(CollectionSheet $sheet, Request $request)
    {
        $dateFrom = $request->get('dateFrom');
        $dateTo = $request->get('dateTo');
        $operatorId = $request->get('operator_id');
        $sellerId = $request->get('seller_id');
        $batchName = $request->get('batch_name');
        $zone = $request->get('zone');
        $invoiceFrom = $request->get('invoice_from');
        $invoiceTo = $request->get('invoice_to');

        $query = $sheet->payments()->with(['sale.customer', 'user', 'zelleRecord'])->where('status', 'approved');

        if ($operatorId) {
            $query->where('user_id', $operatorId);
        }

        if ($sellerId || $batchName || $zone || ($invoiceFrom && $invoiceTo)) {
            $query->whereHas('sale', function($q) use ($sellerId, $batchName, $zone, $invoiceFrom, $invoiceTo) {
                if ($sellerId) $q->where('seller_id', $sellerId);
                if ($batchName) $q->where('batch_name', 'like', "%{$batchName}%");
                if ($zone) {
                    $q->whereHas('customer', function($c) use ($zone) {
                        $c->where('zone', 'like', "%{$zone}%");
                    });
                }
                if ($invoiceFrom && $invoiceTo) {
                    $invFrom = 0; $invTo = 0;
                    if (is_numeric($invoiceFrom)) $invFrom = (int)$invoiceFrom;
                    elseif (preg_match('/^[Ff]0*([1-9][0-9]*)$/', $invoiceFrom, $matches)) $invFrom = (int)$matches[1];
                    
                    if (is_numeric($invoiceTo)) $invTo = (int)$invoiceTo;
                    elseif (preg_match('/^[Ff]0*([1-9][0-9]*)$/', $invoiceTo, $matches)) $invTo = (int)$matches[1];

                    if ($invFrom > 0 && $invTo > 0) $q->whereBetween('id', [$invFrom, $invTo]);
                }
            });
        }

        $payments = $query->get();
        $returns = SaleReturn::where('collection_sheet_id', $sheet->id)->with(['sale.customer', 'user'])->get();
        $config = Configuration::first();
        $user = auth()->user();
        $date = Carbon::now()->format('d/m/Y H:i');

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
            $totalsByCurrency[$currencyCode] = $payments->where('currency', $currencyCode)->sum('amount');
        }

        $dateFromFormatted = $dateFrom ?: $sheet->opened_at->format('Y-m-d');
        $dateToFormatted = $dateTo ?: $sheet->opened_at->format('Y-m-d');

        $pdf = Pdf::loadView('reports.collection-relationship-new-pdf', compact('sheet', 'payments', 'returns', 'config', 'user', 'date', 'totalsByCategory', 'totalsByCurrency', 'dateFrom', 'dateTo'));
        
        return $pdf->stream('Relacion_Cobros_' . $sheet->sheet_number . '.pdf');
    }
}
