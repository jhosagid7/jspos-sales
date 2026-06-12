<?php

namespace App\Livewire\Reports;

use Livewire\Component;
use App\Models\Sale;
use App\Models\Payment;
use App\Models\SalePaymentDetail;
use App\Models\CollectionSheet;
use Carbon\Carbon;

class WeeklyIncomeReport extends Component
{
    public $selectedDate;
    public $mondayDate;
    public $saturdayDate;
    public $weekLabel;

    public function mount()
    {
        $this->selectedDate = Carbon::today()->toDateString();
        $this->calculateWeekRange();
        session(['map' => 'REPORTE SEMANAL DE INGRESOS', 'child' => 'Ventas y Cobros', 'rest' => '', 'pos' => 'Reportes']);
    }

    public function updatedSelectedDate()
    {
        $this->calculateWeekRange();
    }

    public function previousWeek()
    {
        $this->selectedDate = Carbon::parse($this->selectedDate)->subWeek()->toDateString();
        $this->calculateWeekRange();
    }

    public function nextWeek()
    {
        $this->selectedDate = Carbon::parse($this->selectedDate)->addWeek()->toDateString();
        $this->calculateWeekRange();
    }

    private function calculateWeekRange()
    {
        $dt = Carbon::parse($this->selectedDate);
        // startOfWeek yields Monday
        $mon = $dt->startOfWeek(Carbon::MONDAY);
        $this->mondayDate = $mon->toDateString();
        $this->saturdayDate = $mon->copy()->addDays(5)->toDateString();
        
        $monFormatted = $mon->format('d/m/Y');
        $satFormatted = $mon->copy()->addDays(5)->format('d/m/Y');
        $this->weekLabel = "Semana del {$monFormatted} al {$satFormatted}";
    }

    public function render()
    {
        $data = $this->getReportData();
        return view('livewire.reports.weekly-income-report', $data)
            ->layout('layouts.theme.app');
    }

    public function getReportData()
    {
        $mon = Carbon::parse($this->mondayDate);
        
        $days = [
            1 => ['name' => 'LUNES', 'date' => $mon->toDateString()],
            2 => ['name' => 'MARTES', 'date' => $mon->copy()->addDays(1)->toDateString()],
            3 => ['name' => 'MIERCOLES', 'date' => $mon->copy()->addDays(2)->toDateString()],
            4 => ['name' => 'JUEVES', 'date' => $mon->copy()->addDays(3)->toDateString()],
            5 => ['name' => 'VIERNES', 'date' => $mon->copy()->addDays(4)->toDateString()],
            6 => ['name' => 'SABADO', 'date' => $mon->copy()->addDays(5)->toDateString()],
        ];

        $categories = [
            'DOLARES' => 'DOLARES',
            'PESOS' => 'PESOS',
            'EFECTIVO BS' => 'EFECTIVO BS',
            'BANCO DE VENEZUELA' => 'BANCO DE VENEZUELA',
            'BANCO PROVINCIAL' => 'BANCO PROVINCIAL',
            'BANCO MERCANTIL' => 'BANCO MERCANTIL',
            'ZELLE' => 'ZELLE'
        ];

        $report = [];
        $weeklyTotals = [];
        foreach ($categories as $cat) {
            $weeklyTotals[$cat] = [
                'contado' => 0.0,
                'cobranza' => 0.0
            ];
        }

        $allSheetsClosedAndAudited = true;
        $hasSheets = false;

        foreach ($days as $num => $dayInfo) {
            $date = $dayInfo['date'];
            $dayName = $dayInfo['name'];

            // Initialize day data
            $dayData = [];
            foreach ($categories as $cat) {
                $dayData[$cat] = [
                    'contado' => 0.0,
                    'cobranza' => 0.0
                ];
            }

            // 1. CONTADO
            $sales = Sale::whereBetween('created_at', [
                    Carbon::parse($date)->startOfDay(), 
                    Carbon::parse($date)->endOfDay()
                ])
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

                        if ($method === 'cash') {
                            if ($curr === 'USD') {
                                $dayData['DOLARES']['contado'] += $amtUSD;
                            } elseif ($curr === 'COP') {
                                $dayData['PESOS']['contado'] += $amtUSD;
                            } elseif ($curr === 'VES' || $curr === 'VED') {
                                $dayData['EFECTIVO BS']['contado'] += $amtUSD;
                            }
                        } elseif ($method === 'zelle' || str_contains($bank, 'ZELLE')) {
                            $dayData['ZELLE']['contado'] += $amtUSD;
                        } elseif ($method === 'bank' || $method === 'deposit') {
                            if (str_contains($bank, 'PROVINCIAL')) {
                                $dayData['BANCO PROVINCIAL']['contado'] += $amtUSD;
                            } elseif (str_contains($bank, 'MERCANTIL')) {
                                $dayData['BANCO MERCANTIL']['contado'] += $amtUSD;
                            } elseif (str_contains($bank, 'VENEZUELA') || str_contains($bank, 'BDV')) {
                                $dayData['BANCO DE VENEZUELA']['contado'] += $amtUSD;
                            }
                        }
                    }

                    // Subtract change
                    $changes = $sale->changeDetails;
                    foreach ($changes as $c) {
                        $rate = $c->exchange_rate > 0 ? $c->exchange_rate : 1;
                        $amtUSD = $c->amount / $rate;
                        $curr = strtoupper($c->currency_code);
                        if ($curr === 'USD') {
                            $dayData['DOLARES']['contado'] -= $amtUSD;
                        } elseif ($curr === 'COP') {
                            $dayData['PESOS']['contado'] -= $amtUSD;
                        } elseif ($curr === 'VES' || $curr === 'VED') {
                            $dayData['EFECTIVO BS']['contado'] -= $amtUSD;
                        }
                    }
                } else {
                    if ($sale->type !== 'credit') {
                        $rate = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;
                        $netAmt = ($sale->cash - $sale->change) / $rate;
                        $curr = strtoupper($sale->primary_currency_code ?? 'USD');

                        if ($sale->type === 'cash') {
                            if ($curr === 'USD') {
                                $dayData['DOLARES']['contado'] += $netAmt;
                            } elseif ($curr === 'COP') {
                                $dayData['PESOS']['contado'] += $netAmt;
                            } elseif ($curr === 'VES' || $curr === 'VED') {
                                $dayData['EFECTIVO BS']['contado'] += $netAmt;
                            }
                        } elseif ($sale->type === 'zelle') {
                            $dayData['ZELLE']['contado'] += $netAmt;
                        } elseif ($sale->type === 'bank') {
                            $bank = strtoupper($sale->bank_name ?? '');
                            if (str_contains($bank, 'PROVINCIAL')) {
                                $dayData['BANCO PROVINCIAL']['contado'] += $netAmt;
                            } elseif (str_contains($bank, 'MERCANTIL')) {
                                $dayData['BANCO MERCANTIL']['contado'] += $netAmt;
                            } elseif (str_contains($bank, 'VENEZUELA') || str_contains($bank, 'BDV')) {
                                $dayData['BANCO DE VENEZUELA']['contado'] += $netAmt;
                            }
                        }
                    }
                }
            }

            // 2. COBRANZA
            // Find collection sheet opened on this day
            $sheet = CollectionSheet::whereDate('opened_at', $date)->first();
            if ($sheet) {
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

                    if ($payWay === 'cash') {
                        if ($curr === 'USD') {
                            $dayData['DOLARES']['cobranza'] += $amtUSD;
                        } elseif ($curr === 'COP') {
                            $dayData['PESOS']['cobranza'] += $amtUSD;
                        } elseif ($curr === 'VES' || $curr === 'VED') {
                            $dayData['EFECTIVO BS']['cobranza'] += $amtUSD;
                        }
                    } elseif ($payWay === 'zelle' || str_contains($bank, 'ZELLE')) {
                        $dayData['ZELLE']['cobranza'] += $amtUSD;
                    } elseif ($payWay === 'bank' || $payWay === 'deposit') {
                        if (str_contains($bank, 'PROVINCIAL')) {
                            $dayData['BANCO PROVINCIAL']['cobranza'] += $amtUSD;
                        } elseif (str_contains($bank, 'MERCANTIL')) {
                            $dayData['BANCO MERCANTIL']['cobranza'] += $amtUSD;
                        } elseif (str_contains($bank, 'VENEZUELA') || str_contains($bank, 'BDV')) {
                            $dayData['BANCO DE VENEZUELA']['cobranza'] += $amtUSD;
                        }
                    }
                }
            }

            // 3. VENTAS A CREDITO
            $creditSales = Sale::whereBetween('created_at', [
                    Carbon::parse($date)->startOfDay(), 
                    Carbon::parse($date)->endOfDay()
                ])
                ->where('type', 'credit')
                ->whereNotIn('status', ['voided', 'cancelled', 'anulated', 'returned'])
                ->get();

            $dayCreditTotal = 0.0;
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
                $dayCreditTotal += max(0.0, $netSaleUSD - $pdSum);
            }

            // Calculate Subtotals for the day
            $subtotalContado = 0.0;
            $subtotalCobranza = 0.0;
            foreach ($categories as $cat) {
                $subtotalContado += $dayData[$cat]['contado'];
                $subtotalCobranza += $dayData[$cat]['cobranza'];

                // Accumulate to weekly totals
                $weeklyTotals[$cat]['contado'] += $dayData[$cat]['contado'];
                $weeklyTotals[$cat]['cobranza'] += $dayData[$cat]['cobranza'];
            }

            $ventasMasCreditoContado = $subtotalContado + $dayCreditTotal;
            $totalGeneral = $ventasMasCreditoContado + $subtotalCobranza;
            $totalRecibido = $subtotalContado + $subtotalCobranza;

            $report[$dayName] = [
                'date' => $date,
                'data' => $dayData,
                'subtotal_contado' => $subtotalContado,
                'subtotal_cobranza' => $subtotalCobranza,
                'ventas_credito' => $dayCreditTotal,
                'ventas_mas_credito' => $ventasMasCreditoContado,
                'total_general' => $totalGeneral,
                'total_recibido' => $totalRecibido
            ];
        }

        // Calculate Weekly Subtotals
        $weeklySubtotalContado = 0.0;
        $weeklySubtotalCobranza = 0.0;
        $weeklyCreditTotal = 0.0;
        foreach ($report as $dName => $dVal) {
            $weeklyCreditTotal += $dVal['ventas_credito'];
        }

        foreach ($categories as $cat) {
            $weeklySubtotalContado += $weeklyTotals[$cat]['contado'];
            $weeklySubtotalCobranza += $weeklyTotals[$cat]['cobranza'];
        }

        $weeklyVentasMasCredito = $weeklySubtotalContado + $weeklyCreditTotal;
        $weeklyTotalGeneral = $weeklyVentasMasCredito + $weeklySubtotalCobranza;
        $weeklyTotalRecibido = $weeklySubtotalContado + $weeklySubtotalCobranza;

        // Audit status determination
        $currentDateString = Carbon::today()->toDateString();
        $isCurrentOrFutureWeek = Carbon::parse($this->mondayDate)->startOfDay() <= Carbon::today();
        
        $isPreliminar = !$hasSheets || !$allSheetsClosedAndAudited || $isCurrentOrFutureWeek;
        $statusText = $isPreliminar ? 'PRELIMINAR / EN CURSO' : 'CONSOLIDADO / AUDITADO';

        return [
            'report' => $report,
            'weeklyTotals' => $weeklyTotals,
            'weeklySubtotalContado' => $weeklySubtotalContado,
            'weeklySubtotalCobranza' => $weeklySubtotalCobranza,
            'weeklyCreditTotal' => $weeklyCreditTotal,
            'weeklyVentasMasCredito' => $weeklyVentasMasCredito,
            'weeklyTotalGeneral' => $weeklyTotalGeneral,
            'weeklyTotalRecibido' => $weeklyTotalRecibido,
            'isPreliminar' => $isPreliminar,
            'statusText' => $statusText,
        ];
    }
}
