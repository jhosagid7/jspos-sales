<?php

namespace App\Livewire\Reports;

use Carbon\Carbon;
use App\Models\Sale;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class PaymentRelationshipReport extends Component
{
    use WithPagination;

    public $pagination = 10, $showReport = false;
    public $dateFrom, $dateTo;
    public $operator_id, $seller_id;
    public $batch_name, $zone;
    public $invoice_from, $invoice_to;
    
    public $operators = [], $sellers = [];
    public $selectedSheet = null; // For detail view

    function mount()
    {
        $this->operators = \App\Models\User::orderBy('name')->get();
        $this->sellers = \App\Models\User::role('Vendedor')->orderBy('name')->get();
        
        // Default to today
        $this->dateFrom = Carbon::now()->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');
        
        session(['map' => "TOTAL RECAUDADO $0.00", 'child' => 'PLANILLAS: 0', 'rest' => '', 'pos' => 'Relación de Cobro General']);
    }

    public function render()
    {
        if ($this->selectedSheet) {
            return view('livewire.reports.payment-relationship-detail', [
                'sheet' => $this->selectedSheet,
                'payments' => $this->getSheetDetails($this->selectedSheet),
                'currencies' => \App\Models\Currency::all(),
                'banks' => \App\Models\Bank::orderBy('sort')->get()
            ]);
        }

        return view('livewire.reports.payment-relationship-report', [
            'sheets' => $this->getReport(),
            'currencies' => \App\Models\Currency::all(),
            'banks' => \App\Models\Bank::orderBy('sort')->get()
        ]);
    }

    function getReport()
    {
        if (!$this->showReport) return [];

        $query = \App\Models\CollectionSheet::query();

        if ($this->dateFrom && $this->dateTo) {
            $dFrom = Carbon::parse($this->dateFrom)->startOfDay();
            $dTo = Carbon::parse($this->dateTo)->endOfDay();
            $query->whereBetween('opened_at', [$dFrom, $dTo]);
        }

        // Note: Filters like Operator, Seller, Batch, Zone, Invoice Range apply to the PAYMENTS inside the sheet,
        // but the main view shows SHEETS. 
        // If we filter by Operator, should we show only sheets that contain payments from that operator?
        // Yes, let's filter sheets that have at least one matching payment.

        if ($this->operator_id || $this->seller_id || $this->batch_name || $this->zone || ($this->invoice_from && $this->invoice_to)) {
            $query->whereHas('payments', function($q) {
                $this->applyPaymentFilters($q);
            });
        }

            $sheets = $query->with('payments')->orderBy('opened_at', 'desc')->paginate($this->pagination);

        // Calculate Totals
        $totalAmount = 0;
        // We need to calculate total based on filtered payments, not just sheet total
        // But for the main list, showing the sheet's total is standard. 
        // Let's calculate the SUM of the filtered sheets' total_amount for now.
        // Ideally, if I filter by "Operator Juan", I might want to see only Juan's total in that sheet.
        // For now, let's keep it simple: List matching sheets.
        
        // Recalculate totals for header
        $allSheetsQuery = clone $query;
        // To get accurate "Total Recaudado" based on filters, we should sum the payments matching filters
        $totalRecaudado = \App\Models\Payment::whereHas('sale', function($q) {
                // Sale filters
            })
            ->whereIn('collection_sheet_id', $allSheetsQuery->pluck('id'))
            ->where(function($q) {
                $this->applyPaymentFilters($q);
            })
            ->sum(DB::raw('amount / exchange_rate')); // Approximation of USD total

        $map = "TOTAL RECAUDADO $" . number_format($totalRecaudado, 2);
        $child = "PLANILLAS: " . $sheets->total();
        
        $this->dispatch('update-header', map: $map, child: $child, rest: '');

        return $sheets;
    }

    function applyPaymentFilters($query)
    {
        if ($this->operator_id) {
            $query->where('user_id', $this->operator_id);
        }

        if ($this->seller_id || $this->batch_name || $this->zone || ($this->invoice_from && $this->invoice_to)) {
            $query->whereHas('sale', function($q) {
                if ($this->seller_id) {
                    $q->whereHas('customer', function($c) {
                        $c->where('seller_id', $this->seller_id);
                    });
                }
                if ($this->batch_name) {
                    $q->where('batch_name', 'like', "%{$this->batch_name}%");
                }
                if ($this->zone) {
                    $q->whereHas('customer', function($c) {
                        $c->where('zone', 'like', "%{$this->zone}%");
                    });
                }
                if ($this->invoice_from && $this->invoice_to) {
                    // Assuming invoice_number is numeric or we cast it? 
                    // Or just ID range? User said "Invoice Range". 
                    // Let's assume ID for robustness or invoice_number if numeric.
                    // Let's use ID for now as it's safer.
                    $q->whereBetween('id', [$this->invoice_from, $this->invoice_to]);
                }
            });
        }
    }

    function viewDetails($sheetId)
    {
        $this->selectedSheet = \App\Models\CollectionSheet::find($sheetId);
    }

    function closeDetails()
    {
        $this->selectedSheet = null;
    }

    function getSheetDetails($sheet)
    {
        $query = $sheet->payments()->with(['sale.customer', 'user']);
        $this->applyPaymentFilters($query);
        return $query->get();
    }
    
    // PDF Generation would need to be updated similarly
    function generatePdf($type = 'basic') {
        $config = \App\Models\Configuration::first();
        $user = Auth()->user();
        $date = \Carbon\Carbon::now()->format('d/m/Y H:i');

        // Prepare Filters Text
        $filters = [];
        if ($this->dateFrom && $this->dateTo) {
            $filters['Fecha'] = \Carbon\Carbon::parse($this->dateFrom)->format('d/m/Y') . ' - ' . \Carbon\Carbon::parse($this->dateTo)->format('d/m/Y');
        }
        if ($this->operator_id) {
            $op = \App\Models\User::find($this->operator_id);
            if ($op) $filters['Operador'] = $op->name;
        }
        if ($this->seller_id) {
            $sel = \App\Models\User::find($this->seller_id);
            if ($sel) $filters['Vendedor'] = $sel->name;
        }
        if ($this->batch_name) $filters['Lote'] = $this->batch_name;
        if ($this->zone) $filters['Zona'] = $this->zone;
        if ($this->invoice_from && $this->invoice_to) $filters['Facturas'] = "#$this->invoice_from - #$this->invoice_to";


        if ($this->selectedSheet) {
            // Generate Detail PDF (Basic or Detailed)
            $sheet = $this->selectedSheet;
            $payments = $this->getSheetDetails($sheet);
            
            // Calculate Payment Method Summary
            $methods = $payments->groupBy('pay_way')->map(function($group) {
                return $group->sum(function($p) {
                    return $p->amount / ($p->exchange_rate > 0 ? $p->exchange_rate : 1);
                });
            });

            // Fetch Dynamic Headers for PDF
            $currencies = \App\Models\Currency::all();
            $banks = \App\Models\Bank::orderBy('sort')->get();

            $view = $type == 'detailed' ? 'reports.collection-sheet-detail-full-pdf' : 'reports.collection-sheet-detail-pdf';
            
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($view, compact('sheet', 'payments', 'config', 'user', 'date', 'methods', 'filters', 'currencies', 'banks'));
            $pdf->setPaper('a4', 'landscape'); // Set Landscape
            $suffix = $type == 'detailed' ? '_Detallado_' : '_Basico_';
            // Clean filename to avoid encoding issues
            $fileName = 'Relacion_Cobro' . $suffix . $sheet->sheet_number . '_' . \Carbon\Carbon::now()->format('YmdHis') . '.pdf';
        } else {
            // Generate Summary List PDF
            $query = \App\Models\CollectionSheet::query();

            if ($this->dateFrom && $this->dateTo) {
                $dFrom = \Carbon\Carbon::parse($this->dateFrom)->startOfDay();
                $dTo = \Carbon\Carbon::parse($this->dateTo)->endOfDay();
                $query->whereBetween('opened_at', [$dFrom, $dTo]);
            }

            if ($this->operator_id || $this->seller_id || $this->batch_name || $this->zone || ($this->invoice_from && $this->invoice_to)) {
                $query->whereHas('payments', function($q) {
                    $this->applyPaymentFilters($q);
                });
            }

            $sheets = $query->with('payments')->orderBy('opened_at', 'desc')->get();
            
            // Fetch Dynamic Headers for PDF
            $currencies = \App\Models\Currency::all();
            $banks = \App\Models\Bank::orderBy('sort')->get();

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.collection-sheets-list-pdf', compact('sheets', 'config', 'user', 'date', 'filters', 'currencies', 'banks'));
            $pdf->setPaper('a4', 'landscape'); // Set Landscape
            $fileName = 'Relacion_Cobro_General_' . \Carbon\Carbon::now()->format('YmdHis') . '.pdf';
        }

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $fileName);
    }
}
