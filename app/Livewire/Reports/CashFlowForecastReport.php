<?php

namespace App\Livewire\Reports;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Sale;
use App\Models\Payment;
use App\Models\Customer;
use App\Models\SalePaymentDetail;
use App\Models\Configuration;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class CashFlowForecastReport extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // Filters
    public $dateFrom;
    public $dateTo;
    public $customer_id;
    public $seller_id;
    public $pagination = 10;

    // Sort
    public $sortField = 'due_date';
    public $sortDirection = 'asc';

    public $showReport = false;
    public $showInterpretationModal = false;
    public $showPdfModal = false;
    public $pdfUrl = '';
    public $selectedBucket = 'all';

    public function toggleInterpretationModal()
    {
        $this->showInterpretationModal = !$this->showInterpretationModal;
    }

    public function selectBucket($bucket)
    {
        if ($this->selectedBucket === $bucket) {
            $this->selectedBucket = 'all';
        } else {
            $this->selectedBucket = $bucket;
        }
        $this->resetPage();
    }

    public function openPdfPreview()
    {
        $params = [
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'customer_id' => $this->customer_id,
            'seller_id' => $this->seller_id,
        ];

        $this->pdfUrl = route('reports.cash.flow.forecast.pdf', $params);
        $this->showPdfModal = true;
    }

    public function closePdfPreview()
    {
        $this->showPdfModal = false;
        $this->pdfUrl = '';
    }

    public function mount()
    {
        session(['pos' => 'Proyección de Flujo y Cobranza']);

        // Default to current month
        $this->dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = Carbon::now()->endOfMonth()->format('Y-m-d');
    }

    public function searchData()
    {
        $this->showReport = true;
        $this->selectedBucket = 'all';
        $this->resetPage();
        $this->updateChart();
    }

    public function updated($propertyName)
    {
        if ($this->showReport) {
            $this->updateChart();
        }
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    /**
     * Calculate outstanding debt in USD for a single sale.
     */
    public static function calculateSaleDebtUsd($sale)
    {
        if ($sale->status === 'paid' || $sale->status === 'returned' || $sale->status === 'voided' || $sale->status === 'cancelled' || $sale->status === 'anulated' || $sale->deletion_approved_at !== null) {
            return 0.0;
        }

        // 1. Initial payments at checkout
        $initialPaidUSD = 0;
        if ($sale->paymentDetails) {
            foreach ($sale->paymentDetails as $detail) {
                $rate = $detail->exchange_rate > 0 ? $detail->exchange_rate : 1;
                $initialPaidUSD += ($detail->amount / $rate);
            }
        }

        // 2. Subsequent payments
        $subsequentPaidUSD = 0;
        if ($sale->payments) {
            foreach ($sale->payments->where('status', 'approved') as $p) {
                $rate = $p->exchange_rate > 0 ? $p->exchange_rate : 1;
                $amountUSD = $p->amount / $rate;
                $adjustmentUSD = $p->discount_applied ?? 0;

                if ($p->rule_type === 'overdue') {
                    $subsequentPaidUSD += ($amountUSD - $adjustmentUSD);
                } else {
                    $subsequentPaidUSD += ($amountUSD + $adjustmentUSD);
                }
            }
        }

        // 3. Returns applied to debt
        $returnsUSD = 0;
        if ($sale->returns) {
            $totalReturnsOrig = $sale->returns->where('refund_method', 'debt_reduction')->where('status', 'approved')->sum('total_returned');
            $exchangeRateReturns = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;
            $returnsUSD = $totalReturnsOrig / $exchangeRateReturns;
        }

        // 4. Debit notes
        $debitNotesUSD = 0;
        if ($sale->debitNotes) {
            $debitNotesUSD = $sale->debitNotes->where('status', '<>', 'voided')->sum(function($dn) {
                $rate = $dn->exchange_rate > 0 ? $dn->exchange_rate : 1;
                return $dn->amount / $rate;
            });
        }

        $debt = ($sale->total_usd + $debitNotesUSD) - ($initialPaidUSD + $subsequentPaidUSD + $returnsUSD);
        return $debt > 0 ? (float)round($debt, 2) : 0.0;
    }

    /**
     * Get all active credit sales with outstanding debt based on filters.
     */
    public function getActiveCreditSalesQuery()
    {
        return Sale::with(['paymentDetails', 'payments', 'returns', 'debitNotes', 'customer', 'customer.seller'])
            ->where('sales.type', 'credit')
            ->where('sales.status', '<>', 'returned')
            ->whereNull('sales.deletion_approved_at')
            ->when($this->customer_id, fn($q) => $q->where('sales.customer_id', $this->customer_id))
            ->when($this->seller_id, function($q) {
                $oficinaUser = User::where('name', 'OFICINA')->first();
                $oficinaId = $oficinaUser ? $oficinaUser->id : null;

                $q->whereHas('customer', function($c) use ($oficinaId) {
                    if ($this->seller_id == $oficinaId) {
                        $c->whereNull('seller_id')->orWhere('seller_id', $this->seller_id);
                    } else {
                        $c->where('seller_id', $this->seller_id);
                    }
                });
            });
    }

    /**
     * Process sales to calculate debt, due date, and assign ageing buckets.
     */
    public function getProcessedSales()
    {
        $sales = $this->getActiveCreditSalesQuery()->get();
        $processed = [];

        foreach ($sales as $sale) {
            $debt = self::calculateSaleDebtUsd($sale);
            if ($debt <= 0) {
                continue;
            }

            $creditDays = intval($sale->credit_days ?? 0);
            $startDate = $sale->delivered_at ? Carbon::parse($sale->delivered_at) : Carbon::parse($sale->created_at);
            $dueDate = $startDate->copy()->addDays($creditDays);
            
            // Difference in days from today (positive = overdue, negative = current/future)
            $daysDiff = $dueDate->diffInDays(Carbon::now(), false);

            if ($daysDiff > 0) {
                $status = 'vencido';
                if ($daysDiff <= 7) {
                    $bucket = 'vencido_1_7';
                    $statusText = 'Vencido 1-7 días';
                } elseif ($daysDiff <= 15) {
                    $bucket = 'vencido_8_15';
                    $statusText = 'Vencido 8-15 días';
                } else {
                    $bucket = 'vencido_critico';
                    $statusText = 'Vencido >15 días';
                }
            } else {
                $status = 'corriente';
                $daysRemaining = abs($daysDiff);
                if ($daysRemaining <= 7) {
                    $bucket = 'corriente_1_7';
                    $statusText = 'Por Vencer 1-7 días';
                } elseif ($daysRemaining <= 14) {
                    $bucket = 'corriente_8_14';
                    $statusText = 'Por Vencer 8-14 días';
                } else {
                    $bucket = 'corriente_largo';
                    $statusText = 'Por Vencer >14 días';
                }
            }

            $processed[] = [
                'sale_id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'customer_name' => $sale->customer->name,
                'seller_name' => $sale->customer->seller ? $sale->customer->seller->name : 'OFICINA',
                'created_at' => $sale->created_at->format('Y-m-d'),
                'delivered_at' => $sale->delivered_at ? Carbon::parse($sale->delivered_at)->format('Y-m-d') : null,
                'credit_days' => $creditDays,
                'due_date' => $dueDate->format('Y-m-d'),
                'total_usd' => (float)$sale->total_usd,
                'debt_usd' => $debt,
                'days_diff' => $daysDiff,
                'status' => $status,
                'bucket' => $bucket,
                'status_text' => $statusText
            ];
        }

        return collect($processed);
    }

    /**
     * Calculate and return KPIs and Ageing Buckets.
     */
    public function getCalculatedMetrics($processedSales)
    {
        $totalDebt = $processedSales->sum('debt_usd');
        $currentDebt = $processedSales->where('status', 'corriente')->sum('debt_usd');
        $overdueDebt = $processedSales->where('status', 'vencido')->sum('debt_usd');

        // Buckets
        $buckets = [
            'vencido_critico' => $processedSales->where('bucket', 'vencido_critico')->sum('debt_usd'),
            'vencido_8_15' => $processedSales->where('bucket', 'vencido_8_15')->sum('debt_usd'),
            'vencido_1_7' => $processedSales->where('bucket', 'vencido_1_7')->sum('debt_usd'),
            'corriente_1_7' => $processedSales->where('bucket', 'corriente_1_7')->sum('debt_usd'),
            'corriente_8_14' => $processedSales->where('bucket', 'corriente_8_14')->sum('debt_usd'),
            'corriente_largo' => $processedSales->where('bucket', 'corriente_largo')->sum('debt_usd'),
        ];

        // Cobros realizados en el rango de fecha
        $dateFromStart = Carbon::parse($this->dateFrom)->startOfDay();
        $dateToValue = Carbon::parse($this->dateTo)->endOfDay();

        // 1. Initial Payments
        $initialPaymentsQuery = SalePaymentDetail::whereHas('sale', function($q) use ($dateFromStart, $dateToValue) {
            $q->whereBetween('created_at', [$dateFromStart, $dateToValue])
              ->whereNull('deletion_approved_at')
              ->where('status', '<>', 'returned');
            if ($this->customer_id) {
                $q->where('customer_id', $this->customer_id);
            }
            if ($this->seller_id) {
                $q->whereHas('customer', function($c) {
                    $oficinaUser = User::where('name', 'OFICINA')->first();
                    $oficinaId = $oficinaUser ? $oficinaUser->id : null;
                    if ($this->seller_id == $oficinaId) {
                        $c->whereNull('seller_id')->orWhere('seller_id', $this->seller_id);
                    } else {
                        $c->where('seller_id', $this->seller_id);
                    }
                });
            }
        });
        $totalInitialPaid = $initialPaymentsQuery->get()->sum(function($p) {
            $rate = $p->exchange_rate > 0 ? $p->exchange_rate : 1;
            return $p->amount / $rate;
        });

        // 2. Subsequent Payments
        $subsequentPaymentsQuery = Payment::where('status', 'approved')
            ->where(function($q) use ($dateFromStart, $dateToValue) {
                $q->whereBetween('payment_date', [$dateFromStart, $dateToValue])
                  ->orWhere(function($sq) use ($dateFromStart, $dateToValue) {
                      $sq->whereNull('payment_date')->whereBetween('created_at', [$dateFromStart, $dateToValue]);
                  });
            })
            ->whereHas('sale', function($q) {
                $q->whereNull('deletion_approved_at');
                if ($this->customer_id) {
                    $q->where('customer_id', $this->customer_id);
                }
                if ($this->seller_id) {
                    $q->whereHas('customer', function($c) {
                        $oficinaUser = User::where('name', 'OFICINA')->first();
                        $oficinaId = $oficinaUser ? $oficinaUser->id : null;
                        if ($this->seller_id == $oficinaId) {
                            $c->whereNull('seller_id')->orWhere('seller_id', $this->seller_id);
                        } else {
                            $c->where('seller_id', $this->seller_id);
                        }
                    });
                }
            });
        $totalSubsequentPaid = $subsequentPaymentsQuery->get()->sum(function($p) {
            $rate = $p->exchange_rate > 0 ? $p->exchange_rate : 1;
            $amountUSD = $p->amount / $rate;
            $adjustmentUSD = $p->discount_applied ?? 0;
            return $p->rule_type === 'overdue' ? ($amountUSD - $adjustmentUSD) : ($amountUSD + $adjustmentUSD);
        });

        $totalCollected = $totalInitialPaid + $totalSubsequentPaid;

        // CEI % (Collection Efficiency Index)
        $ceiNumerator = $totalCollected;
        $ceiDenominator = $totalCollected + $overdueDebt;
        $cei = $ceiDenominator > 0 ? ($ceiNumerator / $ceiDenominator) * 100 : 100.0;

        // DSO Ponderado (Days Sales Outstanding)
        $weightedDaysSum = 0;
        $overdueCount = 0;
        foreach ($processedSales->where('status', 'vencido') as $sale) {
            $weightedDaysSum += ($sale['debt_usd'] * $sale['days_diff']);
            $overdueCount++;
        }
        $dso = $overdueDebt > 0 ? $weightedDaysSum / $overdueDebt : 0.0;

        return [
            'totalDebt' => $totalDebt,
            'currentDebt' => $currentDebt,
            'overdueDebt' => $overdueDebt,
            'totalCollected' => $totalCollected,
            'cei' => $cei,
            'dso' => $dso,
            'buckets' => $buckets
        ];
    }

    public function getChartData()
    {
        if (!$this->showReport) {
            return ['labels' => [], 'datasets' => []];
        }

        $dateFromStart = Carbon::parse($this->dateFrom);
        $dateToValue = Carbon::parse($this->dateTo);

        // Generate daily labels
        $labels = [];
        $realIncome = [];
        $projectedIncome = [];
        
        $current = $dateFromStart->copy();
        $dateKeys = [];
        while ($current->lte($dateToValue)) {
            $dateStr = $current->toDateString();
            $labels[] = $current->format('d/m');
            $dateKeys[$dateStr] = [
                'real' => 0.0,
                'projected' => 0.0
            ];
            $current->addDay();
        }

        // 1. Gather Real Payments in this range
        // Subsequent
        $subPayments = Payment::where('status', 'approved')
            ->where(function($q) use ($dateFromStart, $dateToValue) {
                $q->whereBetween('payment_date', [$dateFromStart->startOfDay(), $dateToValue->endOfDay()])
                  ->orWhere(function($sq) use ($dateFromStart, $dateToValue) {
                      $sq->whereNull('payment_date')->whereBetween('created_at', [$dateFromStart->startOfDay(), $dateToValue->endOfDay()]);
                  });
            })
            ->whereHas('sale', function($q) {
                $q->whereNull('deletion_approved_at');
                if ($this->customer_id) {
                    $q->where('customer_id', $this->customer_id);
                }
                if ($this->seller_id) {
                    $q->whereHas('customer', function($c) {
                        $oficinaUser = User::where('name', 'OFICINA')->first();
                        $oficinaId = $oficinaUser ? $oficinaUser->id : null;
                        if ($this->seller_id == $oficinaId) {
                            $c->whereNull('seller_id')->orWhere('seller_id', $this->seller_id);
                        } else {
                            $c->where('seller_id', $this->seller_id);
                        }
                    });
                }
            })->get();

        foreach ($subPayments as $p) {
            $pDate = $p->payment_date ? Carbon::parse($p->payment_date)->toDateString() : Carbon::parse($p->created_at)->toDateString();
            if (isset($dateKeys[$pDate])) {
                $rate = $p->exchange_rate > 0 ? $p->exchange_rate : 1;
                $amountUSD = $p->amount / $rate;
                $adjustmentUSD = $p->discount_applied ?? 0;
                $usdValue = $p->rule_type === 'overdue' ? ($amountUSD - $adjustmentUSD) : ($amountUSD + $adjustmentUSD);
                $dateKeys[$pDate]['real'] += $usdValue;
            }
        }

        // Initial Payments
        $initPayments = SalePaymentDetail::whereHas('sale', function($q) use ($dateFromStart, $dateToValue) {
            $q->whereBetween('created_at', [$dateFromStart->startOfDay(), $dateToValue->endOfDay()])
              ->whereNull('deletion_approved_at')
              ->where('status', '<>', 'returned');
            if ($this->customer_id) {
                $q->where('customer_id', $this->customer_id);
            }
            if ($this->seller_id) {
                $q->whereHas('customer', function($c) {
                    $oficinaUser = User::where('name', 'OFICINA')->first();
                    $oficinaId = $oficinaUser ? $oficinaUser->id : null;
                    if ($this->seller_id == $oficinaId) {
                        $c->whereNull('seller_id')->orWhere('seller_id', $this->seller_id);
                    } else {
                        $c->where('seller_id', $this->seller_id);
                    }
                });
            }
        })->get();

        foreach ($initPayments as $p) {
            $pDate = Carbon::parse($p->sale->created_at)->toDateString();
            if (isset($dateKeys[$pDate])) {
                $rate = $p->exchange_rate > 0 ? $p->exchange_rate : 1;
                $dateKeys[$pDate]['real'] += ($p->amount / $rate);
            }
        }

        // 2. Gather Projected Due Dates of outstanding debts that expire in this range
        $processedSales = $this->getProcessedSales();
        foreach ($processedSales as $ps) {
            $dDate = $ps['due_date'];
            if (isset($dateKeys[$dDate])) {
                $dateKeys[$dDate]['projected'] += $ps['debt_usd'];
            }
        }

        // Populate datasets
        foreach ($dateKeys as $dateStr => $vals) {
            $realIncome[] = round($vals['real'], 2);
            $projectedIncome[] = round($vals['projected'], 2);
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'name' => 'Cobrado Real',
                    'data' => $realIncome
                ],
                [
                    'name' => 'Vencimiento Proyectado',
                    'data' => $projectedIncome
                ]
            ]
        ];
    }

    public function updateChart()
    {
        $chartData = $this->getChartData();
        $this->dispatch('updateChart', labels: $chartData['labels'], datasets: $chartData['datasets']);
    }

    public function getInterpretation()
    {
        if (!$this->showReport) {
            return '';
        }

        $sales = $this->getProcessedSales();
        $metrics = $this->getCalculatedMetrics($sales);

        $cei = $metrics['cei'];
        $dso = $metrics['dso'];
        $totalCollected = $metrics['totalCollected'];
        $overdueDebt = $metrics['overdueDebt'];
        $currentDebt = $metrics['currentDebt'];

        $ceiClass = 'success';
        $ceiStatus = 'Excelente';
        $ceiTip = 'La cobranza está al día y el capital fluye adecuadamente hacia la caja.';
        if ($cei < 70) {
            $ceiClass = 'danger';
            $ceiStatus = 'Crítico';
            $ceiTip = 'Riesgo alto de iliquidez. Gran parte del capital a crédito está estancado en cartera vencida. Se sugiere pausar nuevos créditos o despachos.';
        } elseif ($cei < 85) {
            $ceiClass = 'warning';
            $ceiStatus = 'Aceptable con Desviación';
            $ceiTip = 'La recuperación es regular. Hay facturas vencidas acumulándose que requieren seguimiento inmediato.';
        }

        // Recommendations based on DSO and Buckets
        $criticalOverdue = $metrics['buckets']['vencido_critico'];
        $shortTermFlow = $metrics['buckets']['corriente_1_7'];

        return '
        <div class="cash-flow-analysis">
            <h4 class="text-primary font-weight-bold mb-3"><i class="fas fa-chart-line mr-2"></i> Diagnóstico de Flujo de Caja y Cobranza</h4>
            <p class="text-muted">Análisis detallado de la recuperación de deudas y la proyección de ingresos por ventas a crédito para la toma de decisiones:</p>
            
            <div class="row mt-4">
                <div class="col-sm-12 col-md-6 mb-3">
                    <div class="card bg-light border-0 shadow-none p-3 h-100">
                        <h6 class="font-weight-bold text-dark"><i class="fas fa-wallet text-info mr-1"></i> Cartera de Crédito Actual</h6>
                        <ul class="list-unstyled mt-2" style="line-height: 1.8;">
                            <li>• <strong>Deuda Total en Calle:</strong> $' . number_format($metrics['totalDebt'], 2) . ' USD</li>
                            <li>• <strong>Deuda Corriente (Al día):</strong> $' . number_format($currentDebt, 2) . ' USD</li>
                            <li>• <strong>Deuda Vencida (Atraso):</strong> <span class="text-danger font-weight-bold">$' . number_format($overdueDebt, 2) . ' USD</span></li>
                        </ul>
                    </div>
                </div>
                <div class="col-sm-12 col-md-6 mb-3">
                    <div class="card bg-light border-0 shadow-none p-3 h-100">
                        <h6 class="font-weight-bold text-dark"><i class="fas fa-hand-holding-usd text-success mr-1"></i> Desempeño del Rango</h6>
                        <ul class="list-unstyled mt-2" style="line-height: 1.8;">
                            <li>• <strong>Cobrado Realizado:</strong> $' . number_format($totalCollected, 2) . ' USD</li>
                            <li>• <strong>Eficiencia de Cobranza (CEI):</strong> <span class="badge badge-' . $ceiClass . '">' . number_format($cei, 2) . '% (' . $ceiStatus . ')</span></li>
                            <li>• <strong>Atraso Promedio (DSO):</strong> <span class="text-danger font-weight-bold">' . number_format($dso, 1) . ' días</span></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="alert alert-' . $ceiClass . ' mt-3 p-3">
                <h6 class="alert-heading font-weight-bold mb-1"><i class="fas fa-info-circle mr-1"></i> Evaluación del Índice CEI</h6>
                <p class="mb-0 f-12">' . $ceiTip . '</p>
            </div>

            <div class="mt-4">
                <h6 class="font-weight-bold text-dark"><i class="fas fa-clock mr-1"></i> Proyección de Flujo a Corto Plazo (0-7 días):</h6>
                <p class="text-muted f-12 mb-2">Monto estimado a ingresar esta semana por vencimientos corrientes: <strong>$' . number_format($shortTermFlow, 2) . ' USD</strong>.</p>
                
                <h6 class="font-weight-bold text-dark mt-3"><i class="fas fa-exclamation-triangle text-danger mr-1"></i> Concentración de Cartera Crítica (>15 días vencida):</h6>
                <p class="text-muted f-12 mb-0">Deuda vencida de cobro difícil que supera los 15 días de atraso: <strong class="text-danger">$' . number_format($criticalOverdue, 2) . ' USD</strong>.</p>
            </div>

            <div class="card border-warning mt-4 bg-warning-light" style="background-color: #fff9e6; border: 1px solid #ffeeba;">
                <div class="card-body p-3">
                    <h6 class="font-weight-bold text-warning mb-2" style="color: #856404 !important;"><i class="fas fa-lightbulb"></i> Acciones de Cobranza Recomendadas</h6>
                    <ul class="mb-0 f-12 text-warning-dark" style="color: #856404 !important; padding-left: 20px;">
                        ' . ($cei < 85 ? '<li>Activar gestiones de cobro inmediatas sobre clientes con deudas mayores a 7 días.</li>' : '') . '
                        ' . ($criticalOverdue > 0 ? '<li>Pausar despachos o suspender línea de crédito a los clientes que figuran en el bucket de "Vencido Crítico".</li>' : '') . '
                        <li>Incentivar descuentos por pronto pago para acelerar la velocidad del efectivo.</li>
                    </ul>
                </div>
            </div>
        </div>';
    }

    public function generatePdf()
    {
        $config = Configuration::first();
        $user = auth()->user();
        $date = Carbon::now()->format('d/m/Y H:i');

        $sales = $this->getProcessedSales();
        $metrics = $this->getCalculatedMetrics($sales);

        // Sorting processed sales
        $field = $this->sortField;
        $direction = $this->sortDirection;
        $sortedSales = $sales->sortBy(function($item) use ($field) {
            return $item[$field] ?? '';
        }, SORT_REGULAR, $direction === 'desc');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('livewire.reports.cash-flow-forecast-report-pdf', [
            'sales' => $sortedSales,
            'metrics' => $metrics,
            'config' => $config,
            'user' => $user,
            'date' => $date,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo
        ]);

        $pdf->setPaper('a4', 'landscape');

        $fileName = 'Proyeccion_Flujo_Cobranza_' . Carbon::now()->format('YmdHis') . '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $fileName);
    }

    public function render()
    {
        $sellers = User::sellers()->orderBy('name')->get();
        $customers = Customer::orderBy('name')->get();

        $processedSalesPaginated = collect();
        $metrics = [
            'totalDebt' => 0.0,
            'currentDebt' => 0.0,
            'overdueDebt' => 0.0,
            'totalCollected' => 0.0,
            'cei' => 100.0,
            'dso' => 0.0,
            'buckets' => [
                'vencido_critico' => 0.0,
                'vencido_8_15' => 0.0,
                'vencido_1_7' => 0.0,
                'corriente_1_7' => 0.0,
                'corriente_8_14' => 0.0,
                'corriente_largo' => 0.0,
            ]
        ];

        if ($this->showReport) {
            $processedSales = $this->getProcessedSales();
            $metrics = $this->getCalculatedMetrics($processedSales);

            if ($this->selectedBucket !== 'all') {
                $processedSales = $processedSales->where('bucket', $this->selectedBucket);
            }

            // Sorting
            $field = $this->sortField;
            $direction = $this->sortDirection;
            $sortedSales = $processedSales->sortBy(function($item) use ($field) {
                return $item[$field] ?? '';
            }, SORT_REGULAR, $direction === 'desc');

            // Paginated items
            $currentPage = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
            $pagedData = $sortedSales->slice(($currentPage - 1) * $this->pagination, $this->pagination)->values();
            
            $processedSalesPaginated = new \Illuminate\Pagination\LengthAwarePaginator(
                $pagedData,
                $sortedSales->count(),
                $this->pagination,
                $currentPage,
                ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
            );
        }

        return view('livewire.reports.cash-flow-forecast-report', [
            'sales' => $processedSalesPaginated,
            'metrics' => $metrics,
            'sellers' => $sellers,
            'customers' => $customers
        ])->layout('layouts.theme.app');
    }
}
