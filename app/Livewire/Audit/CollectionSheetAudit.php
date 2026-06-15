<?php

namespace App\Livewire\Audit;

use App\Models\CollectionSheet;
use App\Models\Payment;
use App\Models\ExchangeRateHistory;
use App\Models\Configuration;
use Livewire\Component;
use Carbon\Carbon;

class CollectionSheetAudit extends Component
{
    public $sheet;
    public $searchQuery = '';
    public $scannerInput = '';
    public $isCameraActive = false;
    public $selectedPaymentDetails = null;

    protected $queryString = ['searchQuery' => ['except' => '']];

    public function mount($sheet = null)
    {
        if ($sheet) {
            if (is_numeric($sheet)) {
                $this->sheet = CollectionSheet::find($sheet);
            } else {
                $this->sheet = CollectionSheet::where('sheet_number', $sheet)->first();
            }

            if (!$this->sheet) {
                session()->flash('error', 'Planilla de cobranza no encontrada.');
                return redirect()->route('audit.sheet');
            }
        }
        
        session(['map' => 'AUDITORÍA DE COBRANZA', 'child' => $this->sheet ? 'Planilla: ' . $this->sheet->sheet_number : 'Portal', 'rest' => '', 'pos' => 'Auditoría']);
    }

    public function search()
    {
        $query = trim($this->searchQuery);
        if (empty($query)) {
            return;
        }

        $resolvedSheet = CollectionSheet::where('sheet_number', $query)
            ->orWhere('id', $query)
            ->first();

        if ($resolvedSheet) {
            return redirect()->route('audit.sheet.detail', ['sheet' => $resolvedSheet->sheet_number]);
        }

        session()->flash('error', "No se encontró ninguna planilla con el número o ID: {$query}");
    }

    public function handleScanner()
    {
        $query = trim($this->scannerInput);
        $this->scannerInput = ''; // Clear input

        if (empty($query)) {
            return;
        }

        // Check if the input is a full URL (from QR code)
        if (filter_var($query, FILTER_VALIDATE_URL)) {
            $path = parse_url($query, PHP_URL_PATH);
            $segments = explode('/', trim($path, '/'));
            // E.g., /audit/sheet/20260609-001 -> last segment is sheet number
            $query = end($segments);
        }

        $resolvedSheet = CollectionSheet::where('sheet_number', $query)
            ->orWhere('id', $query)
            ->first();

        if ($resolvedSheet) {
            return redirect()->route('audit.sheet.detail', ['sheet' => $resolvedSheet->sheet_number]);
        }

        session()->flash('error', "No se encontró planilla escaneada: {$query}");
    }

    public function showAuditDetails($paymentId)
    {
        $payment = Payment::with(['sale.customer'])->findOrFail($paymentId);
        $val = $this->getPaymentValidation($payment);
        
        $sale = $payment->sale;
        $commissionPercent = $sale ? floatval($sale->resolved_commission_percent) : 0;
        $freightPercent = $sale ? floatval($sale->resolved_freight_percent) : 0;
        $diffPercent = $sale ? floatval($sale->resolved_exchange_diff_percent) : 0;
        $markupPercent = $sale ? floatval($sale->resolved_base_markup_percent) : 0;
        
        $base = $sale ? floatval($sale->base_amount) : 0;
        $total = $sale ? floatval($sale->total_usd) : 0;
        $ratio = $total > 0 ? (($payment->amount / ($payment->exchange_rate ?: 1)) / $total) : 0;
        
        $paymentBase = $base * $ratio;
        $paymentFreight = ($sale ? floatval($sale->freight_amount) : 0) * $ratio;
        $paymentCommission = ($sale ? floatval($sale->commission_amount) : 0) * $ratio;
        $paymentMarkup = ($sale ? floatval($sale->base_markup_amount) : 0) * $ratio;
        $paymentDiff = ($sale ? floatval($sale->exchange_diff_amount) : 0) * $ratio;
        
        $this->selectedPaymentDetails = [
            'payment_id' => $payment->id,
            'invoice_number' => $sale ? ($sale->invoice_number ?: ('#' . $sale->id)) : 'N/A',
            'client_name' => $sale && $sale->customer ? $sale->customer->name : 'N/A',
            'invoice_total' => $total,
            'agreement' => $val['agreement'],
            
            // Surcharges configuration
            'commission_percent' => $commissionPercent,
            'freight_percent' => $freightPercent,
            'diff_percent' => $diffPercent,
            'markup_percent' => $markupPercent,
            'base_amount' => $base,
            'commission_amount' => $sale ? floatval($sale->commission_amount) : 0,
            'freight_amount' => $sale ? floatval($sale->freight_amount) : 0,
            'diff_amount' => $sale ? floatval($sale->exchange_diff_amount) : 0,
            'markup_amount' => $sale ? floatval($sale->base_markup_amount) : 0,
            
            // Payment details
            'payment_amount' => floatval($payment->amount),
            'payment_currency' => $payment->currency,
            'payment_rate' => floatval($payment->exchange_rate),
            'payment_usd' => $payment->amount / ($payment->exchange_rate ?: 1),
            'pay_way' => $payment->pay_way,
            'bank_name' => $payment->bank,
            'reference' => $payment->deposit_number ?: ($payment->zelleRecord ? $payment->zelleRecord->reference : 'N/A'),
            
            // Validation details
            'bcv_rate' => $val['bcv_rate'],
            'binance_rate' => $val['binance_rate'],
            'binance_rates' => $val['binance_rates'],
            'payment_base_prop' => $paymentBase,
            'payment_freight_prop' => $paymentFreight,
            'payment_commission_prop' => $paymentCommission,
            'payment_markup_prop' => $paymentMarkup,
            'payment_diff_prop' => $paymentDiff,
            'net_real_usd' => $val['net_usd'],
            'color' => $val['color'],
            'message' => $val['message']
        ];
        
        $this->dispatch('show-audit-modal');
    }

    public function closeAuditDetails()
    {
        $this->selectedPaymentDetails = null;
        $this->dispatch('close-audit-modal');
    }

    public function toggleReconciliation($paymentId)
    {
        $payment = Payment::findOrFail($paymentId);
        $payment->is_bank_reconciled = !$payment->is_bank_reconciled;
        $payment->reconciled_at = $payment->is_bank_reconciled ? now() : null;
        $payment->save();

        session()->flash('success', 'Conciliación bancaria actualizada.');
    }

    public function getPaymentValidation($payment)
    {
        $sale = $payment->sale;
        if (!$sale) {
            return [
                'color' => 'green',
                'message' => 'Sin factura asociada',
                'net_usd' => 0,
                'base_amount' => 0,
                'agreement' => 'N/A'
            ];
        }

        $paymentDate = $payment->payment_date ? Carbon::parse($payment->payment_date) : $payment->created_at;

        // Retrieve historical rates
        $historyBCV = ExchangeRateHistory::where('rate_type', 'BCV')
            ->where('created_at', '<=', $paymentDate->copy()->endOfDay())
            ->orderBy('created_at', 'desc')
            ->first();
        $bcvRate = $historyBCV ? floatval($historyBCV->rate) : null;
        if (!$bcvRate) {
            $config = Configuration::first();
            $bcvRate = $config ? floatval($config->bcv_rate) : 0;
        }

        $recordsBinance = ExchangeRateHistory::whereIn('rate_type', ['BinanceReal', 'Binance'])
            ->whereBetween('created_at', [$paymentDate->copy()->startOfDay(), $paymentDate->copy()->endOfDay()])
            ->orderBy('created_at', 'asc')
            ->get();
        
        if ($recordsBinance->isEmpty()) {
            $latestReal = ExchangeRateHistory::where('rate_type', 'BinanceReal')
                ->where('created_at', '<=', $paymentDate->copy()->endOfDay())
                ->orderBy('created_at', 'desc')
                ->first();
            $latestInflated = ExchangeRateHistory::where('rate_type', 'Binance')
                ->where('created_at', '<=', $paymentDate->copy()->endOfDay())
                ->orderBy('created_at', 'desc')
                ->first();
            $binanceRates = [];
            if ($latestReal) $binanceRates[] = floatval($latestReal->rate);
            if ($latestInflated) $binanceRates[] = floatval($latestInflated->rate);
        } else {
            $binanceRates = $recordsBinance->pluck('rate')->map(fn($r) => floatval($r))->toArray();
        }

        if (empty($binanceRates)) {
            $config = Configuration::first();
            $binanceReal = $config ? floatval($config->binance_rate) : 0;
            $binanceInflated = $binanceReal + ($config ? floatval($config->binance_markup_points) : 0);
            $binanceRates = [$binanceReal, $binanceInflated];
        }

        $binanceRate = !empty($binanceRates) ? $binanceRates[0] : 1;

        $currency = strtoupper($payment->currency);
        $payRate = floatval($payment->exchange_rate ?: 1);

        $paymentUsd = $payment->amount / ($payRate ?: 1);
        $ratio = $sale->total > 0 ? ($paymentUsd / $sale->total) : 0;

        $paymentBase = floatval($sale->base_amount) * $ratio;
        $paymentFreight = floatval($sale->freight_amount) * $ratio;
        $paymentCommission = floatval($sale->commission_amount) * $ratio;
        $paymentMarkup = floatval($sale->base_markup_amount) * $ratio;

        $agreement = $sale->payment_agreement ?: 'USD';

        $isWarning = false;
        $isLoss = false;
        $message = '';
        $computedNetUSD = 0;

        if ($currency === 'USD') {
            $computedNetUSD = $paymentUsd - $paymentFreight - $paymentCommission - $paymentMarkup;
            if ($computedNetUSD < $paymentBase - 0.0099) {
                $isLoss = true;
                $message = 'Monto neto menor al costo base.';
            }
        } elseif ($currency === 'VES' || $currency === 'VED') {
            if ($agreement === 'USD') {
                $isBcvRateUsed = false;
                if ($bcvRate > 0 && abs($payRate - $bcvRate) < 0.01) {
                    $isBcvRateUsed = true;
                } elseif ($binanceRate > 0 && $payRate <= $bcvRate + 0.05) {
                    $isBcvRateUsed = true;
                }

                if ($isBcvRateUsed) {
                    $isLoss = true;
                    $message = 'Tasa BCV en acuerdo USD.';
                } else {
                    $matchesBinance = false;
                    $matchedBinanceRate = null;
                    foreach ($binanceRates as $br) {
                        if (abs($payRate - $br) < 0.01) {
                            $matchesBinance = true;
                            $matchedBinanceRate = $br;
                            break;
                        }
                    }
                    if (!$matchesBinance) {
                        $isWarning = true;
                        $message = 'Tasa de pago no coincide con Binance oficial.';
                    } else {
                        $binanceRate = $matchedBinanceRate;
                    }
                }

                $realUsdValue = $payment->amount / ($binanceRate ?: 1);
                $computedNetUSD = $realUsdValue - $paymentFreight - $paymentCommission - $paymentMarkup;

                if ($computedNetUSD < $paymentBase - 0.0099) {
                    $isLoss = true;
                    if ($message === '') {
                        $message = 'Contravalor neto Binance menor al costo base.';
                    }
                }
            } else {
                if ($bcvRate > 0 && abs($payRate - $bcvRate) > 0.01) {
                    $isWarning = true;
                    $message = 'Tasa de pago no coincide con BCV oficial.';
                }

                $invoiceUsdCovered = $payment->amount / ($payRate ?: 1);
                $realBinanceUsd = ($invoiceUsdCovered * ($bcvRate ?: 1)) / ($binanceRate ?: 1);

                $computedNetUSD = $realBinanceUsd - $paymentFreight - $paymentCommission - $paymentMarkup;

                if ($computedNetUSD < $paymentBase - 0.0099) {
                    $isLoss = true;
                    $message = 'Contravalor real Binance menor al costo base.';
                }
            }
        } else {
            $computedNetUSD = $paymentUsd - $paymentFreight - $paymentCommission - $paymentMarkup;
            if ($computedNetUSD < $paymentBase - 0.0099) {
                $isLoss = true;
                $message = 'Monto neto menor al costo base.';
            }
        }

        $color = 'green';
        if ($isLoss) {
            $color = 'red';
        } elseif ($isWarning) {
            $color = 'orange';
        }

        return [
            'color' => $color,
            'message' => $message ?: 'Cumple rentabilidad',
            'net_usd' => $computedNetUSD,
            'base_amount' => $paymentBase,
            'agreement' => $agreement,
            'bcv_rate' => $bcvRate,
            'binance_rate' => $binanceRate,
            'binance_rates' => $binanceRates,
        ];
    }

    public function render()
    {
        $payments = collect();
        $returns = collect();

        if ($this->sheet) {
            $payments = Payment::where('collection_sheet_id', $this->sheet->id)
                ->with(['sale.customer', 'user', 'zelleRecord'])
                ->whereIn('status', ['approved', 'voided'])
                ->get();

            $returnsInSheet = \App\Models\SaleReturn::where('collection_sheet_id', $this->sheet->id)
                ->with(['sale.customer', 'user'])
                ->get();

            $saleIdsInPayments = $payments->pluck('sale_id')->unique();
            $associatedReturns = \App\Models\SaleReturn::where(function($q) use ($saleIdsInPayments) {
                    $q->whereIn('sale_id', $saleIdsInPayments)
                      ->where(function($iq) {
                          $iq->whereNull('collection_sheet_id')->orWhere('collection_sheet_id', 0);
                      });
                })
                ->orWhere(function($q) {
                    if ($this->sheet) {
                        $q->whereDate('created_at', Carbon::parse($this->sheet->opened_at)->toDateString())
                          ->where(function($iq) {
                              $iq->whereNull('collection_sheet_id')->orWhere('collection_sheet_id', 0);
                          });
                    }
                })
                ->with(['sale.customer', 'user'])
                ->get();

            $returns = $returnsInSheet->merge($associatedReturns)->unique('id');
        }

        return view('livewire.audit.collection-sheet-audit', [
            'payments' => $payments,
            'returns' => $returns,
            'config' => Configuration::first(),
        ])->layout('layouts.theme.app');
    }

    public function finalizeAudit()
    {
        if (!$this->sheet) {
            return;
        }

        // Validate USD sales with BCV payments
        $payments = Payment::where('collection_sheet_id', $this->sheet->id)
            ->whereNotNull('sale_id')
            ->where('status', 'approved')
            ->get();

        $blockedSaleNumbers = [];
        foreach ($payments as $payment) {
            $val = $this->getPaymentValidation($payment);
            if ($val['color'] === 'red' && $val['message'] === 'Tasa BCV en acuerdo USD.') {
                $blockedSaleNumbers[] = $payment->sale->invoice_number ?: ('#' . $payment->sale_id);
            }
        }

        if (!empty($blockedSaleNumbers)) {
            session()->flash('error', 'No se puede finalizar la auditoría de la planilla: contiene pagos de facturas con acuerdo USD pagadas a tasa BCV (' . implode(', ', $blockedSaleNumbers) . ').');
            return;
        }

        // Check if there are pending bank reconciled payments
        $hasPending = Payment::where('collection_sheet_id', $this->sheet->id)
            ->where('status', 'approved')
            ->where('is_bank_reconciled', false)
            ->exists();

        if ($hasPending) {
            $this->dispatch('show-finalize-warning-modal');
        } else {
            $this->confirmFinalizeAudit(false);
        }
    }

    public function confirmFinalizeAudit($forceReconcile = false)
    {
        if (!$this->sheet) {
            return;
        }

        // Validate USD sales with BCV payments
        $payments = Payment::where('collection_sheet_id', $this->sheet->id)
            ->whereNotNull('sale_id')
            ->where('status', 'approved')
            ->get();

        $blockedSaleNumbers = [];
        foreach ($payments as $payment) {
            $val = $this->getPaymentValidation($payment);
            if ($val['color'] === 'red' && $val['message'] === 'Tasa BCV en acuerdo USD.') {
                $blockedSaleNumbers[] = $payment->sale->invoice_number ?: ('#' . $payment->sale_id);
            }
        }

        if (!empty($blockedSaleNumbers)) {
            session()->flash('error', 'No se puede finalizar la auditoría de la planilla: contiene pagos de facturas con acuerdo USD pagadas a tasa BCV (' . implode(', ', $blockedSaleNumbers) . ').');
            return;
        }

        if ($forceReconcile) {
            // Reconcile all approved, non-reconciled payments in this sheet
            Payment::where('collection_sheet_id', $this->sheet->id)
                ->where('status', 'approved')
                ->where('is_bank_reconciled', false)
                ->update([
                    'is_bank_reconciled' => true,
                    'reconciled_at' => now()
                ]);
        }

        // Close the collection sheet
        $this->sheet->update([
            'status' => 'closed',
            'closed_at' => now()
        ]);

        // Propagate audited status to all associated sales
        $saleIds = Payment::where('collection_sheet_id', $this->sheet->id)
            ->whereNotNull('sale_id')
            ->pluck('sale_id')
            ->unique();

        if ($saleIds->isNotEmpty()) {
            \App\Models\Sale::whereIn('id', $saleIds)->update([
                'is_audited' => true,
                'audited_at' => now()
            ]);
        }

        session()->flash('success', 'Auditoría finalizada con éxito. Planilla cerrada y facturas marcadas como auditadas.');
        return redirect()->route('audit.sheet');
    }
}
