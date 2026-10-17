<?php

namespace App\Livewire\Reports;

use Livewire\Component;
use App\Models\Sale;
use App\Models\Payment;
use App\Models\SalePaymentDetail;
use App\Models\CollectionSheet;
use Carbon\Carbon;

class MonthlyIncomeReport extends Component
{
    public $selectedMonth;
    public $monthLabel;

    public function mount()
    {
        $this->selectedMonth = Carbon::today()->format('Y-m');
        $this->calculateMonthLabel();
        session(['map' => 'REPORTE MENSUAL DE INGRESOS', 'child' => 'Ventas y Cobros', 'rest' => '', 'pos' => 'Reportes']);
    }

    public function updatedSelectedMonth()
    {
        $this->calculateMonthLabel();
    }

    public function previousMonth()
    {
        $this->selectedMonth = Carbon::parse($this->selectedMonth . '-01')->subMonth()->format('Y-m');
        $this->calculateMonthLabel();
    }

    public function nextMonth()
    {
        $this->selectedMonth = Carbon::parse($this->selectedMonth . '-01')->addMonth()->format('Y-m');
        $this->calculateMonthLabel();
    }

    private function calculateMonthLabel()
    {
        $dt = Carbon::parse($this->selectedMonth . '-01');
        // Get month name in Spanish
        $monthName = strtoupper($dt->locale('es')->monthName);
        $year = $dt->year;
        $this->monthLabel = "Mes de {$monthName} {$year}";
    }

    public function render()
    {
        $data = $this->getReportData();
        return view('livewire.reports.monthly-income-report', $data)
            ->layout('layouts.theme.app');
    }

    public function getReportData()
    {
        $dt = Carbon::parse($this->selectedMonth . '-01');
        $startOfMonth = $dt->copy()->startOfMonth();
        $endOfMonth = $dt->copy()->endOfMonth();

        // 1. Divide month into weeks (Monday to Saturday, excluding Sunday)
        $daysByWeek = [];
        $currentDate = $startOfMonth->copy();
        while ($currentDate <= $endOfMonth) {
            if ($currentDate->dayOfWeek !== Carbon::SUNDAY) {
                $weekKey = $currentDate->format('o-W');
                if (!isset($daysByWeek[$weekKey])) {
                    $daysByWeek[$weekKey] = [];
                }
                $daysByWeek[$weekKey][] = $currentDate->copy();
            }
            $currentDate->addDay();
        }

        $weeks = [];
        $weekIndex = 1;
        foreach ($daysByWeek as $weekKey => $days) {
            $minDate = collect($days)->min()->toDateString();
            $maxDate = collect($days)->max()->toDateString();
            
            $minFormatted = Carbon::parse($minDate)->format('d/m');
            $maxFormatted = Carbon::parse($maxDate)->format('d/m');
            
            $weeks[$weekKey] = [
                'index' => $weekIndex,
                'label' => "Semana {$weekIndex} ({$minFormatted} - {$maxFormatted})",
                'start' => $minDate,
                'end' => $maxDate
            ];
            $weekIndex++;
        }

        // Scan transactions for any bank names used in this month
        $saleBanks = SalePaymentDetail::whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->whereNotNull('bank_name')
            ->where('bank_name', '!=', '')
            ->pluck('bank_name')
            ->toArray();

        $paymentBanks = Payment::whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->whereNotNull('bank')
            ->where('bank', '!=', '')
            ->pluck('bank')
            ->toArray();

        $extraBanks = array_unique(array_merge($saleBanks, $paymentBanks));
        $categories = \App\Services\IncomeReportService::getCategories($extraBanks);

        // Structure to hold values per week and category
        $report = [];
        foreach ($categories as $cat) {
            $report[$cat] = [];
            foreach ($weeks as $wKey => $wVal) {
                $report[$cat][$wKey] = [
                    'contado' => 0.0,
                    'cobranza' => 0.0
                ];
            }
        }

        $weeklyMetrics = [];
        foreach ($weeks as $wKey => $wVal) {
            $weeklyMetrics[$wKey] = [
                'subtotal_contado' => 0.0,
                'subtotal_cobranza' => 0.0,
                'ventas_credito' => 0.0,
                'ventas_mas_credito' => 0.0,
                'total_general' => 0.0,
                'total_recibido' => 0.0
            ];
        }

        $allSheetsClosedAndAudited = true;
        $hasSheets = false;

        // Populate weekly data
        foreach ($weeks as $wKey => $wVal) {
            $start = Carbon::parse($wVal['start'])->startOfDay();
            $end = Carbon::parse($wVal['end'])->endOfDay();

            // A. CONTADO
            $sales = Sale::whereBetween('created_at', [$start, $end])
                ->whereNotIn('status', ['voided', 'cancelled', 'anulated', 'returned'])
                ->get();

            foreach ($sales as $sale) {
                $details = $sale->paymentDetails;
                if ($details->count() > 0) {
                    foreach ($details as $d) {
                        $rate = $d->exchange_rate > 0 ? $d->exchange_rate : 1;
                        $amtUSD = $d->amount / $rate;
                        $method = strtolower($d->payment_method);
                        $curr = strtoupper($d->currency_code);
                        $bank = strtoupper($d->bank_name ?? '');

                        $targetCat = \App\Services\IncomeReportService::resolveCategory($method, $curr, $bank, $categories);
                        if (!isset($report[$targetCat])) {
                            $report[$targetCat] = [];
                            foreach ($weeks as $wk => $wv) {
                                $report[$targetCat][$wk] = ['contado' => 0.0, 'cobranza' => 0.0];
                            }
                        }
                        $report[$targetCat][$wKey]['contado'] += $amtUSD;
                    }

                    // Subtract change
                    $changes = $sale->changeDetails;
                    foreach ($changes as $c) {
                        $rate = $c->exchange_rate > 0 ? $c->exchange_rate : 1;
                        $amtUSD = $c->amount / $rate;
                        $curr = strtoupper($c->currency_code);
                        if ($curr === 'USD') {
                            $report['DOLARES'][$wKey]['contado'] -= $amtUSD;
                        } elseif ($curr === 'COP') {
                            $report['PESOS'][$wKey]['contado'] -= $amtUSD;
                        } elseif ($curr === 'VES' || $curr === 'VED') {
                            $report['EFECTIVO BS'][$wKey]['contado'] -= $amtUSD;
                        }
                    }
                } else {
                    if ($sale->type !== 'credit') {
                        $rate = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;
                        $netAmt = ($sale->cash - $sale->change) / $rate;
                        $curr = strtoupper($sale->primary_currency_code ?? 'USD');

                        $targetCat = \App\Services\IncomeReportService::resolveCategory($sale->type, $curr, $sale->bank_name ?? '', $categories);
                        if (!isset($report[$targetCat])) {
                            $report[$targetCat] = [];
                            foreach ($weeks as $wk => $wv) {
                                $report[$targetCat][$wk] = ['contado' => 0.0, 'cobranza' => 0.0];
                            }
                        }
                        $report[$targetCat][$wKey]['contado'] += $netAmt;
                    }
                }
            }

            // B. COBRANZA
            // Find collection sheets opened in this week
            $sheets = CollectionSheet::whereBetween('opened_at', [$start, $end])->get();
            foreach ($sheets as $sheet) {
                $hasSheets = true;
                if ($sheet->status !== 'closed') {
                    $allSheetsClosedAndAudited = false;
                }

                $payments = Payment::where('collection_sheet_id', $sheet->id)
                    ->where('status', 'approved')
                    ->get();

                foreach ($payments as $p) {
                    $rate = $p->exchange_rate > 0 ? $p->exchange_rate : 1;
                    $amtUSD = $p->amount / $rate;
                    $payWay = strtolower($p->pay_way);
                    $curr = strtoupper($p->currency);
                    $bank = strtoupper($p->bank ?? '');

                    $targetCat = \App\Services\IncomeReportService::resolveCategory($payWay, $curr, $bank, $categories);
                    if (!isset($report[$targetCat])) {
                        $report[$targetCat] = [];
                        foreach ($weeks as $wk => $wv) {
                            $report[$targetCat][$wk] = ['contado' => 0.0, 'cobranza' => 0.0];
                        }
                    }
                    $report[$targetCat][$wKey]['cobranza'] += $amtUSD;
                }
            }

            // C. VENTAS A CREDITO
            $creditSales = Sale::whereBetween('created_at', [$start, $end])
                ->where('type', 'credit')
                ->whereNotIn('status', ['voided', 'cancelled', 'anulated', 'returned'])
                ->get();

            $weekCreditTotal = 0.0;
            foreach ($creditSales as $sale) {
                // Subtract approved returns
                $returnsForSale = \App\Models\SaleReturn::where('sale_id', $sale->id)
                    ->where('status', 'approved')
                    ->get();
                $retAmtUSD = 0.0;
                foreach ($returnsForSale as $ret) {
                    $rt_rate = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;
                    $retAmtUSD += ($ret->total_returned / $rt_rate);
                }
                $netSaleUSD = $sale->total_usd - $retAmtUSD;

                $pdSum = $sale->paymentDetails->sum(function($d) {
                    $rate = $d->exchange_rate > 0 ? $d->exchange_rate : 1;
                    return $d->amount / $rate;
                });
                $weekCreditTotal += max(0.0, $netSaleUSD - $pdSum);
            }

            // Compute week metrics
            $subtotalContado = 0.0;
            $subtotalCobranza = 0.0;
            foreach ($categories as $cat) {
                $subtotalContado += $report[$cat][$wKey]['contado'] ?? 0.0;
                $subtotalCobranza += $report[$cat][$wKey]['cobranza'] ?? 0.0;
            }

            $weeklyMetrics[$wKey]['subtotal_contado'] = $subtotalContado;
            $weeklyMetrics[$wKey]['subtotal_cobranza'] = $subtotalCobranza;
            $weeklyMetrics[$wKey]['ventas_credito'] = $weekCreditTotal;
            $weeklyMetrics[$wKey]['ventas_mas_credito'] = $subtotalContado + $weekCreditTotal;
            $weeklyMetrics[$wKey]['total_general'] = $subtotalContado + $weekCreditTotal + $subtotalCobranza;
            $weeklyMetrics[$wKey]['total_recibido'] = $subtotalContado + $subtotalCobranza;
        }

        // D. MONTHLY TOTALS (Accumulation)
        $monthlyTotals = [];
        $monthlySubtotalContado = 0.0;
        $monthlySubtotalCobranza = 0.0;
        $monthlyCreditTotal = 0.0;

        foreach ($categories as $cat) {
            $monthlyTotals[$cat] = [
                'contado' => 0.0,
                'cobranza' => 0.0
            ];
            foreach ($weeks as $wKey => $wVal) {
                $monthlyTotals[$cat]['contado'] += $report[$cat][$wKey]['contado'] ?? 0.0;
                $monthlyTotals[$cat]['cobranza'] += $report[$cat][$wKey]['cobranza'] ?? 0.0;
            }
            $monthlySubtotalContado += $monthlyTotals[$cat]['contado'];
            $monthlySubtotalCobranza += $monthlyTotals[$cat]['cobranza'];
        }

        foreach ($weeklyMetrics as $wKey => $metrics) {
            $monthlyCreditTotal += $metrics['ventas_credito'];
        }

        $monthlyVentasMasCredito = $monthlySubtotalContado + $monthlyCreditTotal;
        $monthlyTotalGeneral = $monthlyVentasMasCredito + $monthlySubtotalCobranza;
        $monthlyTotalRecibido = $monthlySubtotalContado + $monthlySubtotalCobranza;

        // Audit status determination
        $currentMonthString = Carbon::today()->format('Y-m');
        $isCurrentOrFutureMonth = Carbon::parse($this->selectedMonth . '-01')->startOfMonth() <= Carbon::today()->startOfMonth();
        
        $isPreliminar = !$hasSheets || !$allSheetsClosedAndAudited || $isCurrentOrFutureMonth;
        $statusText = $isPreliminar ? 'PRELIMINAR / EN CURSO' : 'CONSOLIDADO / AUDITADO';

        return [
            'weeks' => $weeks,
            'categories' => $categories,
            'report' => $report,
            'weeklyMetrics' => $weeklyMetrics,
            'monthlyTotals' => $monthlyTotals,
            'monthlySubtotalContado' => $monthlySubtotalContado,
            'monthlySubtotalCobranza' => $monthlySubtotalCobranza,
            'monthlyCreditTotal' => $monthlyCreditTotal,
            'monthlyVentasMasCredito' => $monthlyVentasMasCredito,
            'monthlyTotalGeneral' => $monthlyTotalGeneral,
            'monthlyTotalRecibido' => $monthlyTotalRecibido,
            'isPreliminar' => $isPreliminar,
            'statusText' => $statusText,
        ];
    }
}
