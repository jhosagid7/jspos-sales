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
    public $showPdfModal = false;
    public $pdfUrl = '';

    public function searchData()
    {
        $this->showReport = true;
        $this->render();
    }

    function mount()
    {
        $this->operators = \App\Models\User::orderBy('name')->get();
        $this->sellers = \App\Models\User::role(['Vendedor', 'Vendedor foraneo'])->orderBy('name')->get();
        
        // Default to today
        $this->dateFrom = Carbon::now()->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');
        
        session(['map' => "TOTAL RECAUDADO $0.00", 'child' => 'PLANILLAS: 0', 'rest' => '', 'pos' => 'Relación de Cobro General']);
    }

    public function render()
    {
        if ($this->selectedSheet) {
            $payments = $this->getSheetDetails($this->selectedSheet);
            $banks = \App\Models\Bank::orderBy('sort')->get();
            $knownBanks = $banks->pluck('name')->toArray();

            return view('livewire.reports.payment-relationship-detail', [
                'sheet' => $this->selectedSheet,
                'payments' => $payments,
                'currencies' => \App\Models\Currency::all(),
                'banks' => $banks,
                'knownBanks' => $knownBanks,
                'summary' => $this->calculateSummary($payments),
                'commissions' => $this->calculateCommissions($payments)
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
            $query->where(function($sheetQuery) {
                $sheetQuery->whereHas('payments', function($q) {
                    $this->applyPaymentFilters($q);
                })->orWhereHas('returns', function($q) {
                    $this->applyReturnFilters($q);
                });
            });
        }

            $sheets = $query->with(['payments' => function($q) {
                $this->applyPaymentFilters($q);
            }])->orderBy('opened_at', 'desc')->paginate($this->pagination);

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
                // Sale filters applied below via applyPaymentFilters
            })
            ->whereIn('collection_sheet_id', $allSheetsQuery->pluck('id'))
            ->where(function($q) {
                $this->applyPaymentFilters($q);
            })
            ->sum(DB::raw('amount / exchange_rate')); // Approximation of USD total

        $map = "TOTAL RECAUDADO $" . number_format($totalRecaudado, 2);
        $child = "PLANILLAS: " . $sheets->total();
        
        $this->dispatch('update-header', map: $map, child: $child, rest: '');

        // Calculate Summary for General View
        $summaryPayments = \App\Models\Payment::whereHas('sale', function($q) {
                // Sale filters applied below via applyPaymentFilters
            })
            ->whereIn('collection_sheet_id', $allSheetsQuery->pluck('id'))
            ->where(function($q) {
                $this->applyPaymentFilters($q);
            })->get();
            
        $this->summaryData = $this->calculateSummary($summaryPayments);

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
                if ($this->invoice_from || $this->invoice_to) {
                    $invFrom = 0;
                    if ($this->invoice_from) {
                        $valFrom = trim($this->invoice_from);
                        if (is_numeric($valFrom)) {
                            $invFrom = (int)$valFrom;
                        } elseif (preg_match('/^[Ff]0*([1-9][0-9]*)$/', $valFrom, $matches)) {
                            $invFrom = (int)$matches[1];
                        }
                    }

                    $invTo = 0;
                    if ($this->invoice_to) {
                        $valTo = trim($this->invoice_to);
                        if (is_numeric($valTo)) {
                            $invTo = (int)$valTo;
                        } elseif (preg_match('/^[Ff]0*([1-9][0-9]*)$/', $valTo, $matches)) {
                            $invTo = (int)$matches[1];
                        }
                    }

                    if ($invFrom > 0 && $invTo > 0) {
                        // Range Search
                        $q->whereBetween('id', [$invFrom, $invTo]);
                    } elseif ($invFrom > 0) {
                        // Exact match for "From"
                        $q->where('id', $invFrom);
                    } elseif ($invTo > 0) {
                        // Exact match for "To" 
                        $q->where('id', $invTo);
                    }
                }
            });
        }
        
        // Configured Rule: Only show APPROVED payments in this report
        $query->where('status', 'approved');
    }

    function applyReturnFilters($query)
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
                if ($this->invoice_from || $this->invoice_to) {
                    // Logic mirroring invoice id search in payments
                    if ($this->invoice_from) $q->where('id', '>=', (int)preg_replace('/[^0-9]/', '', $this->invoice_from));
                    if ($this->invoice_to) $q->where('id', '<=', (int)preg_replace('/[^0-9]/', '', $this->invoice_to));
                }
            });
        }
        
        $query->where('status', 'approved');
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
        $query = $sheet->payments()->with(['sale.customer', 'user', 'zelleRecord']);
        $this->applyPaymentFilters($query);
        return $query->get();
    }
    
    function getSheetReturns($sheet)
    {
        // 1. Returns explicitly assigned to this sheet
        $returnsInSheet = \App\Models\SaleReturn::where('collection_sheet_id', $sheet->id)
            ->with(['sale.customer', 'user'])
            ->get();

        // 2. Returns associated with sales that have payments in this sheet
        // but might not have a collection_sheet_id (e.g. old data or created via other modules)
        $saleIdsInPayments = $sheet->payments()->pluck('sale_id')->unique();
        
        $associatedReturns = \App\Models\SaleReturn::whereIn('sale_id', $saleIdsInPayments)
            ->where(function($q) use ($sheet) {
                $q->whereNull('collection_sheet_id')
                  ->orWhere('collection_sheet_id', 0)
                  // Also include if created on the same day as the sheet opened, even if sheet_id is null
                  ->orWhereDate('created_at', \Carbon\Carbon::parse($sheet->opened_at)->toDateString());
            })
            ->with(['sale.customer', 'user'])
            ->get();

        return $returnsInSheet->merge($associatedReturns)->unique('id');
    }

    function generateRelacionCobroPdf()
    {
        if (!$this->selectedSheet) return;

        $sheet = $this->selectedSheet;
        $payments = $this->getSheetDetails($sheet);
        $returns = $this->getSheetReturns($sheet);
        
        $config = \App\Models\Configuration::first();
        $user = Auth()->user();
        $date = \Carbon\Carbon::now()->format('d/m/Y H:i');

        // Calculate Totals by Category requested by user (Match header image)
        $currencies = \App\Models\Currency::all();
        $banks = \App\Models\Bank::all();
        
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
                if (isset($totalsByCategory[$key])) {
                    $totalsByCategory[$key] += $amtUSD;
                } else {
                    $totalsByCategory[$key] = $amtUSD;
                }
            } else {
                $bankName = $p->bank ? strtoupper($p->bank) : ($p->pay_way == 'zelle' ? 'ZELLE' : null);
                if ($bankName && isset($totalsByCategory[$bankName])) {
                    $totalsByCategory[$bankName] += $amtUSD;
                } elseif ($bankName) {
                    $totalsByCategory[$bankName] = $amtUSD;
                } else {
                    $othersKey = 'OTROS (BANCOS/MEDIOS)';
                    $totalsByCategory[$othersKey] = ($totalsByCategory[$othersKey] ?? 0) + $amtUSD;
                }
            }
        }

        // Totals per Currency (Original Amounts)
        $totalsByCurrency = [];
        $uniqueCurrencies = $payments->pluck('currency')->unique();
        foreach($uniqueCurrencies as $currencyCode) {
            $totalsByCurrency[$currencyCode] = $payments->where('currency', $currencyCode)->sum('amount');
        }

        // Use component filters if present, otherwise fallback to the sheet's opening date
        $dateFrom = $this->dateFrom ?: $sheet->opened_at;
        $dateTo = $this->dateTo ?: $sheet->opened_at;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.collection-relationship-new-pdf', compact('sheet', 'payments', 'returns', 'config', 'user', 'date', 'totalsByCategory', 'totalsByCurrency', 'dateFrom', 'dateTo'));
        
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'Relacion_Cobros_Nuevo_' . $sheet->sheet_number . '_' . \Carbon\Carbon::now()->format('YmdHis') . '.pdf');
    }

    public function openPdfPreview()
    {
        if (!$this->selectedSheet) return;
        
        $params = [
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'operator_id' => $this->operator_id,
            'seller_id' => $this->seller_id,
            'batch_name' => $this->batch_name,
            'zone' => $this->zone,
            'invoice_from' => $this->invoice_from,
            'invoice_to' => $this->invoice_to,
        ];

        $this->pdfUrl = route('reports.collection.relationship.pdf', array_merge(['sheet' => $this->selectedSheet->id], $params));
        $this->showPdfModal = true;
    }

    public function closePdfPreview()
    {
        $this->showPdfModal = false;
        $this->pdfUrl = '';
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
            $summary = $this->calculateSummary($payments);

            // Calculate Commissions for Paid Invoices
            $commissions = $this->calculateCommissions($payments);

            // Fetch Dynamic Headers for PDF

            // Fetch Dynamic Headers for PDF
            $currencies = \App\Models\Currency::all();
            $banks = \App\Models\Bank::orderBy('sort')->get();

            $view = $type == 'detailed' ? 'reports.collection-sheet-detail-full-pdf' : 'reports.collection-sheet-detail-pdf';
            
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($view, compact('sheet', 'payments', 'config', 'user', 'date', 'summary', 'filters', 'currencies', 'banks', 'commissions'));
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

            $sheets = $query->with(['payments' => function($q) {
                $this->applyPaymentFilters($q);
            }])->orderBy('opened_at', 'desc')->get();
            
            // Calculate Global Summary
            $allPayments = \App\Models\Payment::whereHas('sale', function($q) {
                // Sale filters applied below via applyPaymentFilters
            })
            ->whereIn('collection_sheet_id', $sheets->pluck('id'))
            ->where(function($q) {
                $this->applyPaymentFilters($q);
            })->get();

            $summary = $this->calculateSummary($allPayments);

            // Fetch Dynamic Headers for PDF
            $currencies = \App\Models\Currency::all();
            $banks = \App\Models\Bank::orderBy('sort')->get();

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.collection-sheets-list-pdf', compact('sheets', 'config', 'user', 'date', 'filters', 'currencies', 'banks', 'summary'));
            $pdf->setPaper('a4', 'landscape'); // Set Landscape
            $fileName = 'Relacion_Cobro_General_' . \Carbon\Carbon::now()->format('YmdHis') . '.pdf';
        }

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $fileName);
    }
    public $summaryData = [];

    function calculateSummary($payments)
    {
        $summary = [];
        $currencies = \App\Models\Currency::all();
        $banks = \App\Models\Bank::orderBy('sort')->get();
        $knownBanks = $banks->pluck('name')->toArray();

        // 1. Cash Payments by Currency
        foreach ($currencies as $currency) {
            $cashPayments = $payments->where('pay_way', 'cash')->where('currency', $currency->code);
            $amount = $cashPayments->sum('amount');
            
            if ($amount > 0) {
                $equivalent = $cashPayments->sum(function($p) {
                    return $p->amount / ($p->exchange_rate > 0 ? $p->exchange_rate : 1);
                });

                $summary[] = [
                    'name' => "Efectivo {$currency->code}",
                    'original' => $amount,
                    'currency' => $currency->code,
                    'equivalent' => $equivalent
                ];
            }
        }

        // 2. Bank Payments by Bank
        foreach ($banks as $bank) {
            $bankPayments = $payments->filter(function($p) use ($bank) {
                $match = ($p->pay_way == 'bank' || $p->pay_way == 'deposit') && $p->bank == $bank->name;
                if (stripos($bank->name, 'zelle') !== false && $p->pay_way == 'zelle') $match = true;
                return $match;
            });

            $amount = $bankPayments->sum('amount');

            if ($amount > 0) {
                $equivalent = $bankPayments->sum(function($p) {
                    return $p->amount / ($p->exchange_rate > 0 ? $p->exchange_rate : 1);
                });
                
                $summary[] = [
                    'name' => $bank->name,
                    'original' => $amount,
                    'currency' => $bank->currency_code ?? 'USD', 
                    'equivalent' => $equivalent
                ];
            }
        }

        // 3. Other Banks (Grouped by Currency)
        $otherPayments = $payments->filter(function($p) use ($knownBanks) {
             return ($p->pay_way == 'bank' || $p->pay_way == 'deposit') && !in_array($p->bank, $knownBanks);
        });

        $otherByCurrency = $otherPayments->groupBy('currency');

        foreach ($otherByCurrency as $currencyCode => $groupedPayments) {
            $amount = $groupedPayments->sum('amount');
            if ($amount > 0) {
                $equivalent = $groupedPayments->sum(function($p) {
                    return $p->amount / ($p->exchange_rate > 0 ? $p->exchange_rate : 1);
                });

                $summary[] = [
                    'name' => "Otros Bancos ($currencyCode)",
                    'original' => $amount,
                    'currency' => $currencyCode,
                    'equivalent' => $equivalent
                ];
            }
        }

        return $summary;
    }

    function calculateCommissions($payments)
    {
        $commissions = [];
        $groupedPayments = $payments->groupBy('sale_id');
        
        foreach ($groupedPayments as $saleId => $salePayments) {
            $sale = $salePayments->first()->sale;
            if (!$sale || $sale->status != 'paid') continue;

            $lastPayment = $sale->payments->sortByDesc('created_at')->first();
            $paymentDate = $lastPayment ? $lastPayment->created_at : now();

            $percentage = \App\Services\CommissionService::calculateCommission($sale, $paymentDate);
            
            if ($percentage > 0) {
                // Determine Payment Currency (Majority Rule)
                $usdTotal = $sale->payments->where('currency', 'USD')->sum('amount');
                $copTotal = $sale->payments->where('currency', 'COP')->sum('amount');
                $vesTotal = $sale->payments->where('currency', 'VES')->sum('amount');
                
                // Convert all to USD to compare magnitude
                $copInUsd = $sale->payments->where('currency', 'COP')->sum(function($p) { return $p->amount / ($p->exchange_rate > 0 ? $p->exchange_rate : 1); });
                $vesInUsd = $sale->payments->where('currency', 'VES')->sum(function($p) { return $p->amount / ($p->exchange_rate > 0 ? $p->exchange_rate : 1); });

                $paymentCurrency = 'USD';
                $maxVal = $usdTotal;

                if ($copInUsd > $maxVal) {
                    $maxVal = $copInUsd;
                    $paymentCurrency = 'COP';
                }
                if ($vesInUsd > $maxVal) {
                    $paymentCurrency = 'VES';
                }

                // Calculate Base Amount for Display
                $totalSurchargePercent = ($sale->applied_commission_percent ?? 0) + 
                                         ($sale->applied_freight_percent ?? 0) + 
                                         ($sale->applied_exchange_diff_percent ?? 0);
                
                $baseAmount = $sale->total;
                if ($totalSurchargePercent > 0) {
                    $baseAmount = $sale->total / (1 + ($totalSurchargePercent / 100));
                }

                $commissions[] = [
                    'invoice' => $sale->invoice_number ?? $sale->id,
                    'client' => $sale->customer->name,
                    'base' => $baseAmount,
                    'total_with_surcharges' => $sale->total,
                    'percentage' => $percentage,
                    'commission_usd' => $sale->final_commission_amount,
                    'payment_currency' => $paymentCurrency
                ];
            }
        }
        return $commissions;
    }
}
