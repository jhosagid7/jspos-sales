<?php

namespace App\Livewire\Reports;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\ExchangeRateHistory;
use App\Models\Configuration;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class ExchangeDiffReport extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // Filters
    public $dateFrom;
    public $dateTo;
    public $customer_id;
    public $seller_id;
    public $payment_agreement = 'ALL'; // ALL, BCV, USD
    public $pagination = 10;
    
    // Sort
    public $sortField = 'payment_date';
    public $sortDirection = 'desc';

    public $showReport = false;
    
    // Modal previsualizador PDF
    public $showPdfModal = false;
    public $pdfUrl = '';
    
    // Modal Interpretador IA
    public $showInterpretationModal = false;

    public function toggleInterpretationModal()
    {
        $this->showInterpretationModal = !$this->showInterpretationModal;
    }

    public function getInterpretation()
    {
        if (!$this->showReport) {
            return '';
        }

        $kpis = $this->calculateKPIs($this->getCombinedQuery()->get(), $this->getBinanceRatesMap($this->dateFrom, $this->dateTo));
        
        $invoiced = $kpis['totalInvoicedUSD'];
        $credited = $kpis['totalCreditedUSD'];
        $real = $kpis['totalRealUSD'];
        $diff = $kpis['netExchangeDifferenceUSD'];
        $surcharges = $kpis['totalSurchargesBilledUSD'];
        $netResult = $kpis['netCambiaryResultUSD'];

        $diffAbs = abs($diff);
        $netResultAbs = abs($netResult);

        $statusTitle = "Análisis Cambiario de Caja General";
        if ($this->customer_id) {
            $customer = \App\Models\Customer::find($this->customer_id);
            if ($customer) {
                $statusTitle = "Análisis Cambiario del Cliente: " . $customer->name;
            }
        }

        // Determine status and messages
        if ($diff < -0.01) {
            $leakageText = "Se ha detectado una <strong>fuga cambiaria de $" . number_format($diffAbs, 2) . " USD</strong> en el período seleccionado. Esto ocurre porque la tasa a la que se registraron los cobros en Bolívares es inferior a la tasa real de reposición del mercado (tasa Binance).";
            $leakageAlert = "danger";
        } else {
            $leakageText = "No se registran pérdidas por diferencial cambiario directo ($" . number_format($diff, 2) . " USD). Las tasas aplicadas en los cobros se mantuvieron alineadas o por encima del mercado de reposición.";
            $leakageAlert = "success";
        }

        if ($netResult < 0) {
            $surchargeEvaluation = "<strong>Insuficiencia de Cojín</strong>: El recargo por diferencial facturado ($" . number_format($surcharges, 2) . " USD) <strong>no logró cubrir</strong> la fuga cambiaria. La empresa presenta una pérdida neta real de <strong>$" . number_format($netResultAbs, 2) . " USD</strong>.";
            $surchargeAlert = "danger";
            $surchargeTip = "Se sugiere evaluar un incremento en el porcentaje de recargo por diferencial cambiario para los clientes con acuerdos BCV (actualmente cobrando menos del costo real de reposición), o incentivar el cobro directo en Dólares/Zelle.";
        } else {
            $surchargeEvaluation = "<strong>Cojín Eficiente</strong>: El recargo por diferencial facturado ($" . number_format($surcharges, 2) . " USD) <strong>logró cubrir exitosamente</strong> la fuga cambiaria, generando un beneficio neto de devaluación de <strong>$" . number_format($netResult, 2) . " USD</strong>.";
            $surchargeAlert = "success";
            $surchargeTip = "La estrategia actual de recargo y tasas de cobro está protegiendo adecuadamente el valor real del inventario frente a la devaluación.";
        }

        // Top 3 customer/invoice leaks
        $allPayments = $this->getCombinedQuery()->get();
        $ratesMap = $this->getBinanceRatesMap($this->dateFrom, $this->dateTo);
        $leaks = [];
        foreach ($allPayments as $p) {
            $paymentDateStr = Carbon::parse($p->payment_date)->toDateString();
            $binanceRate = $ratesMap[$paymentDateStr] ?? 1.0;
            $payRate = floatval($p->exchange_rate ?: 1.0);
            $amountOriginal = floatval($p->amount);

            $usdCredited = $amountOriginal / $payRate;
            $usdReal = $amountOriginal / $binanceRate;
            $pDiff = $usdReal - $usdCredited;
            if ($pDiff < -0.01) {
                $leaks[] = [
                    'invoice' => $p->invoice_number ? 'F-' . str_pad($p->invoice_number, 6, '0', STR_PAD_LEFT) : '#' . $p->sale_id,
                    'customer' => $p->customer_name,
                    'diff' => $pDiff,
                    'amount' => $amountOriginal,
                    'pay_rate' => $payRate,
                    'binance_rate' => $binanceRate
                ];
            }
        }

        usort($leaks, function($a, $b) {
            return $a['diff'] <=> $b['diff'];
        });

        $topLeaksHtml = '';
        if (count($leaks) > 0) {
            $topLeaksHtml .= '<div class="mt-4"><h6 class="font-weight-bold text-dark"><i class="fas fa-exclamation-circle text-danger mr-1"></i> Principales Fugas Cambiarias Detectadas:</h6><ul class="list-group list-group-flush">';
            foreach (array_slice($leaks, 0, 3) as $l) {
                $topLeaksHtml .= '<li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="font-weight-bold text-primary">' . $l['invoice'] . '</span> - ' . $l['customer'] . '<br>
                        <small class="text-muted">Cobro: ' . number_format($l['amount'], 2) . ' VED | Tasa Pago: ' . number_format($l['pay_rate'], 2) . ' vs. Binance: ' . number_format($l['binance_rate'], 2) . '</small>
                    </div>
                    <span class="text-danger font-weight-bold">-$' . number_format(abs($l['diff']), 2) . ' USD</span>
                </li>';
            }
            $topLeaksHtml .= '</ul></div>';
        } else {
            $topLeaksHtml .= '<div class="alert alert-success mt-3"><i class="fas fa-check-circle mr-1"></i> No se detectaron transacciones individuales con fugas significativas.</div>';
        }

        return '
        <div class="exchange-analysis">
            <h4 class="text-primary font-weight-bold mb-3"><i class="fas fa-balance-scale mr-2"></i> ' . $statusTitle . '</h4>
            <p class="text-muted">Este análisis diagnostica la eficiencia cambiaria de los cobros recibidos en Bolívares y el impacto de la devaluación sobre las cuentas por cobrar:</p>
            
            <div class="row mt-4">
                <div class="col-sm-12 col-md-6 mb-3">
                    <div class="card bg-light border-0 shadow-none p-3 h-100">
                        <h6 class="font-weight-bold text-dark"><i class="fas fa-chart-line text-info mr-1"></i> Flujo Cambiario General</h6>
                        <ul class="list-unstyled mt-2" style="line-height: 1.8;">
                            <li>• <strong>Monto Facturado (USD):</strong> $' . number_format($invoiced, 2) . '</li>
                            <li>• <strong>Abonos Descontados (USD):</strong> $' . number_format($credited, 2) . '</li>
                            <li>• <strong>Valor Real Ingresado (USD):</strong> $' . number_format($real, 2) . '</li>
                        </ul>
                    </div>
                </div>
                <div class="col-sm-12 col-md-6 mb-3">
                    <div class="card bg-light border-0 shadow-none p-3 h-100">
                        <h6 class="font-weight-bold text-dark"><i class="fas fa-shield-alt text-warning mr-1"></i> Cobertura y Recargos</h6>
                        <ul class="list-unstyled mt-2" style="line-height: 1.8;">
                            <li>• <strong>Diferencial Neto de Tasa:</strong> <span class="' . ($diff < 0 ? 'text-danger' : 'text-success') . ' font-weight-bold">$' . number_format($diff, 2) . ' USD</span></li>
                            <li>• <strong>Cojín de Recargo Facturado:</strong> <span class="text-warning font-weight-bold">+$' . number_format($surcharges, 2) . ' USD</span></li>
                            <li>• <strong>Resultado Cambiario Neto:</strong> <span class="' . ($netResult < 0 ? 'text-danger' : 'text-success') . ' font-weight-bold">$' . number_format($netResult, 2) . ' USD</span></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="alert alert-' . $leakageAlert . ' mt-3 p-3">
                <h6 class="alert-heading font-weight-bold mb-1"><i class="fas fa-compress-arrows-alt mr-1"></i> Impacto de Diferencial Cambiario</h6>
                <p class="mb-0 f-12">' . $leakageText . '</p>
            </div>

            <div class="alert alert-' . $surchargeAlert . ' mt-3 p-3">
                <h6 class="alert-heading font-weight-bold mb-1"><i class="fas fa-shield-alt mr-1"></i> Eficacia de la Cobertura (Cojín)</h6>
                <p class="mb-0 f-12">' . $surchargeEvaluation . '</p>
            </div>

            ' . $topLeaksHtml . '

            <div class="card border-warning mt-4 bg-warning-light" style="background-color: #fff9e6; border: 1px solid #ffeeba;">
                <div class="card-body p-3">
                    <h6 class="font-weight-bold text-warning mb-2" style="color: #856404 !important;"><i class="fas fa-lightbulb"></i> Acciones de Optimización Financiera</h6>
                    <p class="mb-0 f-12" style="color: #856404 !important;">' . $surchargeTip . '</p>
                </div>
            </div>
        </div>';
    }

    public function mount()
    {
        session(['map' => '', 'child' => '', 'rest' => '', 'pos' => 'Auditoría de Diferencial Cambiario']);
        
        // Default to current month
        $this->dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');
    }

    public function searchData()
    {
        $this->showReport = true;
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
            $this->sortDirection = 'desc';
        }
    }

    public function getCombinedQuery()
    {
        $subsequent = DB::table('payments')
            ->join('sales', 'payments.sale_id', '=', 'sales.id')
            ->join('customers', 'sales.customer_id', '=', 'customers.id')
            ->leftJoin('users as sellers', 'customers.seller_id', '=', 'sellers.id')
            ->leftJoin('users as operators', 'payments.user_id', '=', 'operators.id')
            ->whereIn('payments.currency', ['VED', 'VES'])
            ->where('payments.status', 'approved')
            ->select([
                'payments.id as payment_id',
                DB::raw("COALESCE(payments.payment_date, DATE(payments.created_at)) as payment_date"),
                'payments.created_at as created_at',
                'payments.amount as amount',
                'payments.currency as currency',
                'payments.exchange_rate as exchange_rate',
                'sales.invoice_number as invoice_number',
                'sales.id as sale_id',
                'sales.total_usd as sale_total_usd',
                'sales.payment_agreement as payment_agreement',
                'sales.exchange_diff_amount as sale_exchange_diff_amount',
                'customers.name as customer_name',
                'customers.id as customer_id',
                'sellers.name as seller_name',
                'sellers.id as seller_id',
                'operators.name as operator_name',
                DB::raw("'subsequent' as payment_type")
            ]);

        $initial = DB::table('sale_payment_details')
            ->join('sales', 'sale_payment_details.sale_id', '=', 'sales.id')
            ->join('customers', 'sales.customer_id', '=', 'customers.id')
            ->leftJoin('users as sellers', 'customers.seller_id', '=', 'sellers.id')
            ->leftJoin('users as operators', 'sales.user_id', '=', 'operators.id')
            ->whereIn('sale_payment_details.currency_code', ['VED', 'VES'])
            ->whereNotIn('sales.status', ['voided', 'cancelled', 'anulated'])
            ->select([
                'sale_payment_details.id as payment_id',
                DB::raw("DATE(sales.created_at) as payment_date"),
                'sales.created_at as created_at',
                'sale_payment_details.amount as amount',
                'sale_payment_details.currency_code as currency',
                'sale_payment_details.exchange_rate as exchange_rate',
                'sales.invoice_number as invoice_number',
                'sales.id as sale_id',
                'sales.total_usd as sale_total_usd',
                'sales.payment_agreement as payment_agreement',
                'sales.exchange_diff_amount as sale_exchange_diff_amount',
                'customers.name as customer_name',
                'customers.id as customer_id',
                'sellers.name as seller_name',
                'sellers.id as seller_id',
                'operators.name as operator_name',
                DB::raw("'initial' as payment_type")
            ]);

        if ($this->dateFrom) {
            $subsequent->whereDate('payments.payment_date', '>=', $this->dateFrom);
            $initial->whereDate('sales.created_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $subsequent->whereDate('payments.payment_date', '<=', $this->dateTo);
            $initial->whereDate('sales.created_at', '<=', $this->dateTo);
        }
        if ($this->customer_id) {
            $subsequent->where('sales.customer_id', $this->customer_id);
            $initial->where('sales.customer_id', $this->customer_id);
        }
        if ($this->seller_id) {
            $subsequent->where('customers.seller_id', $this->seller_id);
            $initial->where('customers.seller_id', $this->seller_id);
        }
        if ($this->payment_agreement && $this->payment_agreement !== 'ALL') {
            $subsequent->where('sales.payment_agreement', $this->payment_agreement);
            $initial->where('sales.payment_agreement', $this->payment_agreement);
        }

        $unionQuery = $subsequent->unionAll($initial);

        return DB::table(DB::raw("({$unionQuery->toSql()}) as combined_payments"))
            ->mergeBindings($unionQuery);
    }

    public function getBinanceRatesMap($dateFrom, $dateTo)
    {
        $start = Carbon::parse($dateFrom)->startOfDay();
        $end = Carbon::parse($dateTo)->endOfDay();
        
        $histories = ExchangeRateHistory::whereIn('rate_type', ['BinanceReal', 'Binance'])
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at', 'asc')
            ->get();
            
        $map = [];
        foreach ($histories as $history) {
            $dateStr = Carbon::parse($history->created_at)->toDateString();
            if ($history->rate_type === 'BinanceReal' || !isset($map[$dateStr])) {
                $map[$dateStr] = floatval($history->rate);
            }
        }
        
        $baseRateQuery = ExchangeRateHistory::where('rate_type', 'BinanceReal')
            ->where('created_at', '<', $start)
            ->orderBy('created_at', 'desc')
            ->first();
            
        if (!$baseRateQuery) {
            $baseRateQuery = ExchangeRateHistory::where('rate_type', 'Binance')
                ->where('created_at', '<', $start)
                ->orderBy('created_at', 'desc')
                ->first();
        }
        
        $baseRate = $baseRateQuery ? floatval($baseRateQuery->rate) : null;
        if (!$baseRate) {
            $config = Configuration::first();
            $baseRate = $config ? floatval($config->binance_rate) : 1.0;
        }
        
        $current = $start->copy();
        $lastRate = $baseRate;
        while ($current->lte($end)) {
            $dateStr = $current->toDateString();
            if (isset($map[$dateStr])) {
                $lastRate = $map[$dateStr];
            } else {
                $map[$dateStr] = $lastRate;
            }
            $current->addDay();
        }
        
        return $map;
    }

    public function calculateKPIs($payments, $ratesMap)
    {
        $totalInvoicedUSD = 0;
        $totalCreditedUSD = 0;
        $totalRealUSD = 0;
        $totalSurchargesBilledUSD = 0;
        
        // To avoid counting the same invoice multiple times for the surcharge and invoiced metrics,
        // we keep track of unique sale ids.
        $uniqueSaleIds = [];

        foreach ($payments as $payment) {
            $paymentDateStr = Carbon::parse($payment->payment_date)->toDateString();
            $binanceRate = $ratesMap[$paymentDateStr] ?? 1.0;
            
            $payRate = floatval($payment->exchange_rate ?: 1.0);
            $amountOriginal = floatval($payment->amount);
            
            $usdCredited = $amountOriginal / $payRate;
            $usdReal = $amountOriginal / $binanceRate;
            
            $totalCreditedUSD += $usdCredited;
            $totalRealUSD += $usdReal;

            $saleId = $payment->sale_id;
            if (!in_array($saleId, $uniqueSaleIds)) {
                $uniqueSaleIds[] = $saleId;
                $totalInvoicedUSD += floatval($payment->sale_total_usd);
                $totalSurchargesBilledUSD += floatval($payment->sale_exchange_diff_amount);
            }
        }

        $netExchangeDifferenceUSD = $totalRealUSD - $totalCreditedUSD;
        $netCambiaryResultUSD = $netExchangeDifferenceUSD + $totalSurchargesBilledUSD;

        return [
            'totalInvoicedUSD' => $totalInvoicedUSD,
            'totalCreditedUSD' => $totalCreditedUSD,
            'totalRealUSD' => $totalRealUSD,
            'netExchangeDifferenceUSD' => $netExchangeDifferenceUSD,
            'totalSurchargesBilledUSD' => $totalSurchargesBilledUSD,
            'netCambiaryResultUSD' => $netCambiaryResultUSD
        ];
    }

    public function getChartData()
    {
        if (!$this->showReport) {
            return ['labels' => [], 'datasets' => []];
        }

        $allPayments = $this->getCombinedQuery()->get();
        $ratesMap = $this->getBinanceRatesMap($this->dateFrom, $this->dateTo);

        // Group payments by date
        $grouped = [];
        $uniqueSalesByDate = []; // To compute daily billed surcharge

        foreach ($allPayments as $p) {
            $dateStr = Carbon::parse($p->payment_date)->toDateString();
            if (!isset($grouped[$dateStr])) {
                $grouped[$dateStr] = [
                    'credited' => 0,
                    'real' => 0,
                    'surcharge' => 0
                ];
                $uniqueSalesByDate[$dateStr] = [];
            }

            $binanceRate = $ratesMap[$dateStr] ?? 1.0;
            $payRate = floatval($p->exchange_rate ?: 1.0);
            $amountOriginal = floatval($p->amount);

            $usdCredited = $amountOriginal / $payRate;
            $usdReal = $amountOriginal / $binanceRate;

            $grouped[$dateStr]['credited'] += $usdCredited;
            $grouped[$dateStr]['real'] += $usdReal;

            $saleId = $p->sale_id;
            if (!in_array($saleId, $uniqueSalesByDate[$dateStr])) {
                $uniqueSalesByDate[$dateStr][] = $saleId;
                $grouped[$dateStr]['surcharge'] += floatval($p->sale_exchange_diff_amount);
            }
        }

        ksort($grouped);

        $labels = [];
        $diffDataset = [];
        $surchargeDataset = [];

        foreach ($grouped as $dateStr => $data) {
            $labels[] = Carbon::parse($dateStr)->format('d/m');
            $diffUSD = $data['real'] - $data['credited'];
            $diffDataset[] = round($diffUSD, 2);
            $surchargeDataset[] = round($data['surcharge'], 2);
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'name' => 'Diferencial Neto (Fuga)',
                    'data' => $diffDataset
                ],
                [
                    'name' => 'Cojín de Diferencial Facturado',
                    'data' => $surchargeDataset
                ]
            ]
        ];
    }

    public function updateChart()
    {
        $chartData = $this->getChartData();
        $this->dispatch('updateChart', labels: $chartData['labels'], datasets: $chartData['datasets']);
    }

    public function generatePdf()
    {
        $config = Configuration::first();
        $user = auth()->user();
        $date = Carbon::now()->format('d/m/Y H:i');

        $allPayments = $this->getCombinedQuery()
            ->orderBy($this->sortField, $this->sortDirection)
            ->get();
            
        $ratesMap = $this->getBinanceRatesMap($this->dateFrom, $this->dateTo);
        $kpis = $this->calculateKPIs($allPayments, $ratesMap);

        // Process payments array for the PDF view
        $processedPayments = [];
        foreach ($allPayments as $p) {
            $paymentDateStr = Carbon::parse($p->payment_date)->toDateString();
            $binanceRate = $ratesMap[$paymentDateStr] ?? 1.0;
            $payRate = floatval($p->exchange_rate ?: 1.0);
            $amountOriginal = floatval($p->amount);

            $usdCredited = $amountOriginal / $payRate;
            $usdReal = $amountOriginal / $binanceRate;
            $diff = $usdReal - $usdCredited;

            $saleTotalUSD = floatval($p->sale_total_usd ?: 0);
            $saleDiffAmount = floatval($p->sale_exchange_diff_amount ?: 0);
            $surchargePortion = $saleTotalUSD > 0 ? ($usdCredited * ($saleDiffAmount / $saleTotalUSD)) : 0;
            $netDiff = $diff + $surchargePortion;

            $status = 'green';
            $msg = 'Cumple Rentabilidad';
            if ($p->payment_agreement === 'BCV' || $surchargePortion > 0) {
                if ($netDiff < -0.01) {
                    $status = 'red';
                    $msg = 'Fuga Real (Cojín Insuficiente)';
                } else {
                    $status = 'green';
                    $msg = 'Cojín Eficiente';
                }
            } else {
                if ($diff < -0.01) {
                    $status = 'red';
                    $msg = 'Fuga de Capital';
                } elseif (abs($payRate - $binanceRate) > 0.01) {
                    $status = 'orange';
                    $msg = 'Desviación de Tasa';
                }
            }

            $processedPayments[] = [
                'payment_id' => $p->payment_id,
                'payment_date' => $p->payment_date,
                'invoice_number' => $p->invoice_number,
                'customer_name' => $p->customer_name,
                'seller_name' => $p->seller_name,
                'agreement' => $p->payment_agreement,
                'currency' => $p->currency,
                'amount' => $amountOriginal,
                'pay_rate' => $payRate,
                'binance_rate' => $binanceRate,
                'usd_credited' => $usdCredited,
                'usd_real' => $usdReal,
                'diff' => $diff,
                'surcharge_portion' => $surchargePortion,
                'net_diff' => $netDiff,
                'status' => $status,
                'msg' => $msg
            ];
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('livewire.reports.exchange-diff-report-pdf', [
            'payments' => $processedPayments,
            'kpis' => $kpis,
            'config' => $config,
            'user' => $user,
            'date' => $date,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo
        ]);
        
        $pdf->setPaper('a4', 'landscape');

        $fileName = 'Auditoria_Diferencial_Cambiario_' . Carbon::now()->format('YmdHis') . '.pdf';
        
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $fileName);
    }

    public function render()
    {
        $sellers = User::sellers()->orderBy('name')->get();
        $customers = Customer::orderBy('name')->get();

        $processedPayments = collect();
        $kpis = [
            'totalInvoicedUSD' => 0,
            'totalCreditedUSD' => 0,
            'totalRealUSD' => 0,
            'netExchangeDifferenceUSD' => 0,
            'totalSurchargesBilledUSD' => 0,
            'netCambiaryResultUSD' => 0
        ];

        if ($this->showReport) {
            $ratesMap = $this->getBinanceRatesMap($this->dateFrom, $this->dateTo);

            // For KPI calculations we need all matching payments
            $allPayments = $this->getCombinedQuery()->get();
            $kpis = $this->calculateKPIs($allPayments, $ratesMap);

            // Paginated payments for grid
            $paginatedRaw = $this->getCombinedQuery()
                ->orderBy($this->sortField, $this->sortDirection)
                ->paginate($this->pagination);

            // Process page items
            $processedItems = [];
            foreach ($paginatedRaw->items() as $p) {
                $paymentDateStr = Carbon::parse($p->payment_date)->toDateString();
                $binanceRate = $ratesMap[$paymentDateStr] ?? 1.0;
                $payRate = floatval($p->exchange_rate ?: 1.0);
                $amountOriginal = floatval($p->amount);

                $usdCredited = $amountOriginal / $payRate;
                $usdReal = $amountOriginal / $binanceRate;
                $diff = $usdReal - $usdCredited;

                $saleTotalUSD = floatval($p->sale_total_usd ?: 0);
                $saleDiffAmount = floatval($p->sale_exchange_diff_amount ?: 0);
                $surchargePortion = $saleTotalUSD > 0 ? ($usdCredited * ($saleDiffAmount / $saleTotalUSD)) : 0;
                $netDiff = $diff + $surchargePortion;

                $status = 'green';
                $msg = 'Cumple Rentabilidad';
                if ($p->payment_agreement === 'BCV' || $surchargePortion > 0) {
                    if ($netDiff < -0.01) {
                        $status = 'red';
                        $msg = 'Fuga Real (Cojín Insuficiente)';
                    } else {
                        $status = 'green';
                        $msg = 'Cojín Eficiente';
                    }
                } else {
                    if ($diff < -0.01) {
                        $status = 'red';
                        $msg = 'Fuga de Capital';
                    } elseif (abs($payRate - $binanceRate) > 0.01) {
                        $status = 'orange';
                        $msg = 'Desviación de Tasa';
                    }
                }

                $processedItems[] = [
                    'payment_id' => $p->payment_id,
                    'payment_date' => $p->payment_date,
                    'invoice_number' => $p->invoice_number,
                    'customer_name' => $p->customer_name,
                    'seller_name' => $p->seller_name,
                    'agreement' => $p->payment_agreement,
                    'currency' => $p->currency,
                    'amount' => $amountOriginal,
                    'pay_rate' => $payRate,
                    'binance_rate' => $binanceRate,
                    'usd_credited' => $usdCredited,
                    'usd_real' => $usdReal,
                    'diff' => $diff,
                    'surcharge_portion' => $surchargePortion,
                    'net_diff' => $netDiff,
                    'status' => $status,
                    'msg' => $msg
                ];
            }

            $processedPayments = new \Illuminate\Pagination\LengthAwarePaginator(
                $processedItems,
                $paginatedRaw->total(),
                $paginatedRaw->perPage(),
                $paginatedRaw->currentPage(),
                ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
            );
        }

        return view('livewire.reports.exchange-diff-report', [
            'payments' => $processedPayments,
            'kpis' => $kpis,
            'sellers' => $sellers,
            'customers' => $customers
        ])->layout('layouts.theme.app');
    }
}

