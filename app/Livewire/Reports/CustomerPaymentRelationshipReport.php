<?php

namespace App\Livewire\Reports;

use Carbon\Carbon;
use App\Models\Sale;
use App\Models\Payment;
use App\Models\Customer;
use App\Models\User;
use App\Models\Currency;
use App\Models\Bank;
use App\Models\Configuration;
use App\Models\SaleReturn;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class CustomerPaymentRelationshipReport extends Component
{
    use WithPagination;

    public $pagination = 10, $showReport = false;
    
    // Filters
    public $dateFrom, $dateTo;
    public $customer_id;
    public $invoice_from, $invoice_to;
    public $seller_id, $operator_id, $currency;
    
    // Lists for filters
    public $sellers = [], $operators = [], $currencies = [];
    
    // PDF Modal
    public $showPdfModal = false;
    public $pdfUrl = '';

    public function selectCustomer($id)
    {
        $this->customer_id = $id;
    }

    public function clearCustomers()
    {
        $this->customer_id = null;
        $this->dispatch('clear-customer-select');
    }

    public function closePdfPreview()
    {
        $this->showPdfModal = false;
        $this->pdfUrl = '';
    }

    public function mount()
    {
        if (!auth()->user()->can('reports.customer_payment_relationship')) {
            abort(403);
        }

        $this->sellers = User::role(['Vendedor', 'Vendedor foraneo'])->orderBy('name')->get();
        $this->operators = User::orderBy('name')->get();
        $this->currencies = Currency::all();
        
        $this->dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');
        
        // Header info
        session(['pos' => 'Relación de Cobros por Cliente']);
    }

    public function searchData()
    {
        $this->showReport = true;
        $this->resetPage();
    }

    public function render()
    {
        $reportData = $this->getReportData();

        return view('livewire.reports.customer-payment-relationship-report', [
            'groupedData' => $reportData['grouped'],
            'totalMonto' => $reportData['totalMonto'],
            'totalIngreso' => $reportData['totalIngreso'],
            'summary' => $reportData['summary'],
            'totalsByCurrency' => $reportData['totalsByCurrency']
        ])->layout('layouts.theme.app');
    }

    public function getReportData()
    {
        if (!$this->showReport) {
            return [
                'grouped' => collect(),
                'totalMonto' => 0,
                'totalIngreso' => 0,
                'summary' => [],
                'totalsByCurrency' => []
            ];
        }

        $query = Payment::query()
            ->with(['sale.customer', 'user', 'zelleRecord', 'bankRecord'])
            ->where('status', 'approved');

        // Apply Date Filter
        if ($this->dateFrom && $this->dateTo) {
            $query->whereBetween('payment_date', [
                Carbon::parse($this->dateFrom)->startOfDay(),
                Carbon::parse($this->dateTo)->endOfDay()
            ]);
        }

        // Apply Operator Filter
        if ($this->operator_id) {
            $query->where('user_id', $this->operator_id);
        }

        // Apply Currency Filter
        if ($this->currency) {
            $query->where('currency', $this->currency);
        }

        // Apply Sale related filters
        $query->whereHas('sale', function($q) {
            // Customer Filter
            if ($this->customer_id) $q->where('customer_id', $this->customer_id);

            // Invoice Range
            if ($this->invoice_from) {
                $valFrom = (int)preg_replace('/[^0-9]/', '', $this->invoice_from);
                if ($valFrom > 0) $q->where('id', '>=', $valFrom);
            }
            if ($this->invoice_to) {
                $valTo = (int)preg_replace('/[^0-9]/', '', $this->invoice_to);
                if ($valTo > 0) $q->where('id', '<=', $valTo);
            }

            // Seller Filter
            if ($this->seller_id) {
                $q->whereHas('customer', function($c) {
                    $c->where('seller_id', $this->seller_id);
                });
            }
        });

        $payments = $query->get();
        
        // Also fetch returns in the same period for these customers
        $customerIds = $payments->pluck('sale.customer_id')->unique();
        $returns = collect();
        if ($customerIds->isNotEmpty()) {
            $returns = SaleReturn::whereIn('customer_id', $customerIds)
                ->where('status', 'approved')
                ->whereBetween('created_at', [
                    Carbon::parse($this->dateFrom)->startOfDay(),
                    Carbon::parse($this->dateTo)->endOfDay()
                ])
                ->with(['sale.customer'])
                ->get();
        }

        $activity = collect();

        // Group payments by sale to merge cash if necessary (matching general report logic)
        $saleGroups = $payments->groupBy('sale_id');
        
        foreach($saleGroups as $saleId => $salePayments) {
            $cashPayments = $salePayments->where('pay_way', 'cash');
            $otherPayments = $salePayments->where('pay_way', '!=', 'cash');

            // 1. Process Merged Cash
                if ($cashPayments->count() > 0) {
                    $p = $cashPayments->first();
                    $totalUsd = 0;
                    $descriptions = [];
                    foreach($cashPayments as $cp) {
                        $rate = $cp->exchange_rate > 0 ? $cp->exchange_rate : 1;
                        $usdEquivalent = $cp->amount / $rate;
                        $totalUsd += $usdEquivalent;
                        
                        $descriptions[] = "[(Tasa: " . number_format($rate, 4) . " | (" . number_format($cp->amount, 4) . " " . $cp->currency . ") = $" . number_format($usdEquivalent, 4) . "]";
                    }

                    $activity->push([
                        'type' => 'Pago',
                        'sale_id' => $p->sale_id,
                        'customer_id' => $p->sale->customer_id,
                        'customer_name' => $p->sale->customer->name,
                        'customer_doc' => $p->sale->customer->taxpayer_id,
                        'date_pay' => Carbon::parse($p->payment_date),
                        'date_emit' => Carbon::parse($p->sale->created_at),
                        'days' => $this->calculateDays($p->sale, $p->payment_date),
                        'doc_number' => $p->sale->invoice_number ?? $p->sale->id,
                        'description' => "CASH " . implode("; ", $descriptions),
                        'monto' => $totalUsd,
                        'ingreso' => $totalUsd,
                        'is_voided' => false,
                        'is_merged' => true
                    ]);
                }

            // 2. Process Others
            foreach($otherPayments as $p) {
                $methodStr = strtoupper($p->pay_way);
                if ($p->pay_way == 'zelle' && $p->zelleRecord) {
                    $methodStr .= " (Ref: {$p->zelleRecord->reference})";
                } elseif (($p->pay_way == 'bank' || $p->pay_way == 'deposit') && $p->bank) {
                    $methodStr .= ": " . ($p->deposit_number ? "{$p->bank}: {$p->deposit_number}" : "{$p->bank}");
                }

                $rate = $p->exchange_rate > 0 ? $p->exchange_rate : 1;
                $usdEquivalent = $p->amount / $rate;

                $description = "{$methodStr} [(Tasa: " . number_format($rate, 4) . ") | (" . number_format($p->amount, 4) . " " . $p->currency . ") = $" . number_format($usdEquivalent, 4) . "]";

                if ($p->discount_applied > 0) {
                    $description .= " [Desc: $" . number_format($p->discount_applied, 2) . "]";
                }

                $activity->push([
                    'type' => 'Pago',
                    'sale_id' => $p->sale_id,
                    'customer_id' => $p->sale->customer_id,
                    'customer_name' => $p->sale->customer->name,
                    'customer_doc' => $p->sale->customer->taxpayer_id,
                    'date_pay' => Carbon::parse($p->payment_date),
                    'date_emit' => Carbon::parse($p->sale->created_at),
                    'days' => $this->calculateDays($p->sale, $p->payment_date),
                    'doc_number' => $p->sale->invoice_number ?? $p->sale->id,
                    'description' => $description,
                    'monto' => $usdEquivalent,
                    'ingreso' => ($p->pay_way == 'advance' || $p->pay_way == 'adelanto') ? 0 : $usdEquivalent,
                    'is_voided' => false,
                    'is_merged' => false
                ]);
            }
        }

        // 3. Process Returns
        foreach($returns as $r) {
            $amtUsd = $r->total_returned / ($r->sale->primary_exchange_rate > 0 ? $r->sale->primary_exchange_rate : 1);
            $activity->push([
                'type' => 'N/C',
                'sale_id' => $r->sale_id,
                'customer_id' => $r->customer_id,
                'customer_name' => $r->customer->name,
                'customer_doc' => $r->customer->taxpayer_id,
                'date_pay' => Carbon::parse($r->created_at),
                'date_emit' => Carbon::parse($r->sale->created_at),
                'days' => 0,
                'doc_number' => $r->sale->invoice_number ?? $r->sale->id,
                'description' => "N/C #{$r->id}: " . ($r->reason ?? 'Devolución'),
                'monto' => $amtUsd,
                'ingreso' => 0,
                'is_voided' => false
            ]);
        }

        // Sorting by customer name, then by invoice date (date_emit), then by sale_id, then by payment date
        $grouped = $activity->sortBy([
            ['customer_name', 'asc'],
            ['date_emit', 'asc'],
            ['sale_id', 'asc'],
            ['date_pay', 'asc']
        ])->groupBy(['customer_id', 'sale_id']);
        
        // Summaries
        $summary = $this->calculateSummary($payments);
        $totalsByCurrency = [];
        foreach ($payments->groupBy('currency') as $curr => $group) {
            $totalsByCurrency[$curr] = $group->sum('amount');
        }

        return [
            'grouped' => $grouped,
            'totalMonto' => $activity->sum('monto'),
            'totalIngreso' => $activity->sum('ingreso'),
            'summary' => $summary,
            'totalsByCurrency' => $totalsByCurrency
        ];
    }

    private function calculateDays($sale, $paymentDate)
    {
        $dateEmit = Carbon::parse($sale->created_at);
        $datePay = Carbon::parse($paymentDate);
        $creditDays = $sale->credit_days ?? 0;
        $dueDate = $dateEmit->copy()->addDays($creditDays);
        return $dueDate->diffInDays($datePay, false);
    }

    private function calculateSummary($payments)
    {
        $summary = [];
        $currencies = Currency::all();
        $banks = Bank::orderBy('sort')->get();
        $knownBanks = $banks->pluck('name')->toArray();

        // Cash
        foreach ($currencies as $currency) {
            $cashPays = $payments->where('pay_way', 'cash')->where('currency', $currency->code);
            $amt = $cashPays->sum('amount');
            if ($amt > 0) {
                $equiv = $cashPays->sum(function($p) { return $p->amount / ($p->exchange_rate > 0 ? $p->exchange_rate : 1); });
                $summary[] = ['name' => "Efectivo {$currency->code}", 'amount' => $amt, 'equiv' => $equiv];
            }
        }

        // Known Banks
        foreach ($banks as $bank) {
            $bankPays = $payments->filter(function($p) use ($bank) {
                return ($p->pay_way == 'bank' || $p->pay_way == 'deposit') && $p->bank == $bank->name;
            });
            $amt = $bankPays->sum('amount');
            if ($amt > 0) {
                $equiv = $bankPays->sum(function($p) { return $p->amount / ($p->exchange_rate > 0 ? $p->exchange_rate : 1); });
                $summary[] = ['name' => $bank->name, 'amount' => $amt, 'equiv' => $equiv];
            }
        }
        
        // Zelle
        $zellePays = $payments->where('pay_way', 'zelle');
        if ($zellePays->sum('amount') > 0) {
            $equiv = $zellePays->sum(function($p) { return $p->amount / ($p->exchange_rate > 0 ? $p->exchange_rate : 1); });
            $summary[] = ['name' => 'Zelle', 'amount' => $zellePays->sum('amount'), 'equiv' => $equiv];
        }

        return $summary;
    }

    public function openPdfPreview()
    {
        $this->pdfUrl = route('reports.customer.payment.relationship.pdf', $this->getParams());
        $this->showPdfModal = true;
    }

    public function generatePdf()
    {
        return redirect()->to(route('reports.customer.payment.relationship.pdf', array_merge($this->getParams(), ['download' => 1])));
    }

    private function getParams()
    {
        return [
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'customer_id' => $this->customer_id,
            'invoice_from' => $this->invoice_from,
            'invoice_to' => $this->invoice_to,
            'seller_id' => $this->seller_id,
            'operator_id' => $this->operator_id,
            'currency' => $this->currency,
        ];
    }
}
