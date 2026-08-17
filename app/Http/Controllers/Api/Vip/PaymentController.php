<?php

namespace App\Http\Controllers\Api\Vip;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Payment;
use App\Models\Currency;
use App\Models\Bank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    /**
     * Get pending credit sales for a specific VIP customer.
     */
    public function pendingSales(Request $request)
    {
        $customer = $request->user();

        // VIP only sees their own sales
        $sales = Sale::where('customer_id', $customer->id)
            ->where('type', 'credit')
            ->where('status', 'pending')
            ->with(['payments', 'returns', 'customer'])
            ->orderBy('id', 'desc')
            ->get();

        $primaryCurrency = Currency::where('is_primary', true)->first();
        
        $formattedSales = $sales->map(function($sale) use ($primaryCurrency) {
            $totalPaidUSD = $sale->payments->whereNotIn('status', ['pending', 'rejected'])->sum(function($p) {
                $rate = $p->exchange_rate > 0 ? $p->exchange_rate : 1;
                $amountUSD = $p->amount / $rate; 
                $discountVal = $p->discount_applied ?? 0;
                return ($p->rule_type === 'overdue') ? ($amountUSD - $discountVal) : ($amountUSD + $discountVal);
            });
            
            $initialPaidUSD = $sale->paymentDetails->sum(function($detail) {
                $rate = $detail->exchange_rate > 0 ? $detail->exchange_rate : 1;
                return $detail->amount / $rate;
            });

            $pendingPaidUSD = $sale->payments->where('status', 'pending')->sum(function($p) {
                $rate = $p->exchange_rate > 0 ? $p->exchange_rate : 1;
                return $p->amount / $rate;
            });

            $totalReturnsUSD = $sale->returns->where('refund_method', 'debt_reduction')->sum('total_returned') / ($sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1);
            
            $totalUSD = $sale->total_usd ?: ($sale->total / ($sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1));
            
            $debtUSD = max(0, $totalUSD - ($totalPaidUSD + $initialPaidUSD + $totalReturnsUSD));

            $startDate = $sale->delivered_at ? \Carbon\Carbon::parse($sale->delivered_at) : \Carbon\Carbon::parse($sale->created_at);
            $creditDays = $sale->credit_days ?? ($sale->customer->credit_days ?? 0);
            $dueDate = $startDate->copy()->addDays($creditDays);
            
            $now = \Carbon\Carbon::now()->startOfDay();
            $due = $dueDate->copy()->startOfDay();
            $daysOverdue = (int) $due->diffInDays($now, false);

            return [
                'id' => $sale->id,
                'invoice_number' => $sale->invoice_number ?? "F-" . str_pad($sale->id, 6, '0', STR_PAD_LEFT),
                'date' => $sale->created_at->format('Y-m-d'),
                'due_date' => $dueDate->format('Y-m-d'),
                'days_overdue' => $daysOverdue,
                'total_usd' => round($totalUSD, 2),
                'paid_usd' => round($totalPaidUSD + $initialPaidUSD, 2),
                'pending_usd' => round($pendingPaidUSD, 2),
                'debt_usd' => round($debtUSD, 2),
                'total_display' => round($totalUSD * $primaryCurrency->exchange_rate, 2),
                'paid_display' => round(($totalPaidUSD + $initialPaidUSD) * $primaryCurrency->exchange_rate, 2),
                'pending_display' => round($pendingPaidUSD * $primaryCurrency->exchange_rate, 2),
                'debt_display' => round($debtUSD * $primaryCurrency->exchange_rate, 2),
                'currency_symbol' => $primaryCurrency->symbol
            ];
        });

        return response()->json($formattedSales);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'method' => 'required|in:cash,bank,zelle,usdt',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string',
            'bank_id' => 'required_if:method,bank|required_if:method,usdt|nullable|exists:banks,id',
            'reference' => 'required_unless:method,cash',
            'payment_date' => 'required|date',
            'image' => 'nullable|image|max:2048',
        ]);

        DB::beginTransaction();
        try {
            $customer = $request->user();
            $sale = Sale::where('id', $request->sale_id)->where('customer_id', $customer->id)->firstOrFail();
            $primaryCurrency = Currency::where('is_primary', true)->first();
            $paymentCurrency = Currency::where('code', $request->currency)->first();
            
            if ($request->reference) {
                // Ensure the exact same reference logic as Vendor upload
                $exists = Payment::where('deposit_number', $request->reference)
                    ->where('status', '!=', 'rejected')
                    ->exists();
                if ($exists) {
                    return response()->json(['message' => 'El número de referencia ya ha sido utilizado.'], 422);
                }
            }

            $exchangeRate = $paymentCurrency ? $paymentCurrency->exchange_rate : 1;
            
            $imagePath = null;
            if ($request->hasFile('image')) {
                $folder = ($request->method === 'usdt') ? 'usdt_receipts' : (($request->method === 'zelle') ? 'zelle_receipts' : 'bank_receipts');
                $imagePath = $request->file('image')->store($folder, 'public');
            }

            $payWay = $request->method;
            if ($payWay === 'bank') $payWay = 'deposit';

            $usdtRecordId = null;
            if ($request->method === 'usdt') {
                $usdtRecord = \App\Models\UsdtRecord::create([
                    'sender_name' => $request->issuer_name ?? $customer->name,
                    'usdt_date' => $request->payment_date ?? date('Y-m-d'),
                    'amount' => $request->amount,
                    'reference' => $request->reference,
                    'image_path' => $imagePath,
                    'status' => 'used',
                    'remaining_balance' => 0,
                    'customer_id' => $customer->id,
                    'sale_id' => $sale->id,
                    'invoice_total' => $sale->total
                ]);
                $usdtRecordId = $usdtRecord->id;
            }

            $payment = Payment::create([
                'user_id' => $sale->user_id ?? 1, // Assign to the seller who made the sale
                'sale_id' => $sale->id,
                'amount' => $request->amount,
                'currency' => $request->currency,
                'exchange_rate' => $exchangeRate,
                'primary_exchange_rate' => $primaryCurrency->exchange_rate,
                'pay_way' => $payWay,
                'type' => 'pay',
                'status' => 'pending',
                'bank' => $request->bank_id ? Bank::find($request->bank_id)->name : 'USDT BINANCE',
                'deposit_number' => $request->reference,
                'payment_date' => $request->payment_date ?? now(),
                'issuer_name' => $request->issuer_name ?? $customer->name,
                'zelle_image' => ($request->method === 'zelle' || $request->method === 'usdt') ? $imagePath : null,
                'bank_image' => ($request->method === 'bank') ? $imagePath : null,
                'usdt_record_id' => $usdtRecordId,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Pago subido con éxito. Pendiente de aprobación.',
                'payment_id' => $payment->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("VIP API Payment Upload Error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Error al subir el pago: ' . $e->getMessage()], 500);
        }
    }
    
    public function formData(Request $request)
    {
        $saleId = $request->query('sale_id');
        $dateStr = $request->query('date', date('Y-m-d'));

        $rateOptions = [];
        $calculatedRate = null;
        $rateType = null;
        $isBcv = false;

        if ($saleId) {
            $sale = Sale::find($saleId);
            if ($sale) {
                $isBcv = ($sale->payment_agreement === 'BCV');
                $dayStart = \Carbon\Carbon::parse($dateStr)->startOfDay();
                $dayEnd = \Carbon\Carbon::parse($dateStr)->endOfDay();

                if ($isBcv) {
                    $rateType = 'BCV';
                    $recordsBCV = \App\Models\ExchangeRateHistory::where('rate_type', 'BCV')
                        ->whereBetween('created_at', [$dayStart, $dayEnd])
                        ->orderBy('created_at', 'desc')
                        ->get();

                    if ($recordsBCV->isEmpty()) {
                        $latestBCV = \App\Models\ExchangeRateHistory::where('rate_type', 'BCV')
                            ->where('created_at', '<=', $dayEnd)
                            ->orderBy('created_at', 'desc')
                            ->first();
                        if ($latestBCV) {
                            $recordsBCV = collect([$latestBCV]);
                        }
                    }

                    foreach ($recordsBCV as $r) {
                        $exists = collect($rateOptions)->contains('rate', floatval($r->rate));
                        if (!$exists) {
                            $periodLabel = $r->period ? " - {$r->period}" : "";
                            $rateOptions[] = [
                                'rate' => floatval($r->rate),
                                'label' => number_format($r->rate, 2) . " Bs. (BCV{$periodLabel})",
                                'rate_type' => 'BCV'
                            ];
                        }
                    }

                    if (empty($rateOptions)) {
                        $config = \App\Models\Configuration::first();
                        $bcvVal = $config ? floatval($config->bcv_rate) : null;
                        if ($bcvVal) {
                            $rateOptions[] = [
                                'rate' => $bcvVal,
                                'label' => number_format($bcvVal, 2) . " Bs. (Tasa Oficial BCV)",
                                'rate_type' => 'BCV'
                            ];
                        }
                    }
                } else {
                    $rateType = 'Binance';
                    $recordsBinance = \App\Models\ExchangeRateHistory::where('rate_type', 'BinanceReal')
                        ->whereBetween('created_at', [$dayStart, $dayEnd])
                        ->orderBy('created_at', 'desc')
                        ->get();

                    if ($recordsBinance->isEmpty()) {
                        $latestReal = \App\Models\ExchangeRateHistory::where('rate_type', 'BinanceReal')
                            ->where('created_at', '<=', $dayEnd)
                            ->orderBy('created_at', 'desc')
                            ->first();

                        if ($latestReal) {
                            $periodLabel = $latestReal->period ? " - {$latestReal->period}" : "";
                            $rateOptions[] = [
                                'rate' => floatval($latestReal->rate),
                                'label' => number_format($latestReal->rate, 2) . " Bs. (Binance{$periodLabel})",
                                'rate_type' => 'Binance'
                            ];
                        }
                    } else {
                        foreach ($recordsBinance as $r) {
                            $exists = collect($rateOptions)->contains('rate', floatval($r->rate));
                            if (!$exists) {
                                $periodLabel = $r->period ? " - {$r->period}" : "";
                                $rateOptions[] = [
                                    'rate' => floatval($r->rate),
                                    'label' => number_format($r->rate, 2) . " Bs. (Binance{$periodLabel})",
                                    'rate_type' => 'Binance'
                                ];
                            }
                        }
                    }

                    if (empty($rateOptions)) {
                        $config = \App\Models\Configuration::first();
                        $binanceVal = $config ? floatval($config->binance_rate) : null;
                        if ($binanceVal) {
                            $rateOptions[] = [
                                'rate' => $binanceVal,
                                'label' => number_format($binanceVal, 2) . " Bs. (Binance)",
                                'rate_type' => 'Binance'
                            ];
                        }
                    }
                }

                if (!empty($rateOptions)) {
                    $calculatedRate = $rateOptions[0]['rate'];
                }
            }
        }

        return response()->json([
            'banks' => Bank::orderBy('sort')->get(),
            'currencies' => Currency::all(),
            'calculated_rate' => $calculatedRate,
            'rate_type' => $rateType,
            'is_bcv' => $isBcv,
            'rate_options' => $rateOptions,
        ]);
    }

    public function history(Request $request)
    {
        $saleId = $request->input('sale_id');
        if (!$saleId) return response()->json(['message' => 'ID de venta requerido'], 422);

        $customer = $request->user();
        $sale = Sale::where('id', $saleId)->where('customer_id', $customer->id)->first();
        
        if (!$sale) return response()->json(['message' => 'Venta no encontrada'], 404);

        $payments = Payment::where('sale_id', $saleId)
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'type' => 'payment',
                    'method' => $p->pay_way,
                    'amount' => $p->amount,
                    'currency' => $p->currency,
                    'reference' => $p->deposit_number,
                    'status' => $p->status,
                    'rejection_reason' => $p->rejection_reason,
                    'date' => $p->payment_date,
                    'exchange_rate' => $p->exchange_rate,
                    'issuer_name' => $p->issuer_name,
                    'bank' => $p->bank,
                    'discount_applied' => $p->discount_applied,
                    'image' => $p->zelle_image ?? $p->bank_image,
                    'created_at' => $p->created_at
                ];
            });

        $returns = $sale->returns->map(function ($r) {
            return [
                'id' => $r->id,
                'type' => 'return',
                'method' => 'Nota de Crédito',
                'amount' => $r->total_returned,
                'currency' => 'USD', 
                'reference' => 'N/C-' . ($r->return_number ?? $r->id),
                'status' => $r->status,
                'date' => $r->created_at->format('Y-m-d'),
                'reason' => $r->reason,
                'created_at' => $r->created_at
            ];
        });

        $combined = $payments->concat($returns)->sortByDesc('created_at')->values();

        return response()->json($combined);
    }

    public function globalHistory(Request $request)
    {
        $customer = $request->user();

        // Obtener historial global de todos los pagos DE ESE CLIENTE
        $salesIds = Sale::where('customer_id', $customer->id)->pluck('id');

        $payments = Payment::whereIn('sale_id', $salesIds)
            ->with(['sale.customer'])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'sale_id' => $p->sale_id,
                    'invoice_number' => $p->sale->invoice_number ?? "F-{$p->sale_id}",
                    'customer_name' => $p->sale->customer->name ?? 'N/A',
                    'method' => $p->pay_way,
                    'amount' => $p->amount,
                    'currency' => $p->currency,
                    'reference' => $p->deposit_number,
                    'status' => $p->status,
                    'rejection_reason' => $p->rejection_reason,
                    'date' => $p->payment_date,
                    'image' => $p->zelle_image ?? $p->bank_image,
                    'created_at' => $p->created_at->format('Y-m-d H:i')
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $payments
        ]);
    }
}
