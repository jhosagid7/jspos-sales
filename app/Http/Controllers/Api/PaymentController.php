<?php

namespace App\Http\Controllers\Api;

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
     * Get pending credit sales for a specific customer.
     */
    public function pendingSales(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id'
        ]);

        $customerId = $request->customer_id;
        $user = $request->user();

        $sales = Sale::where('customer_id', $customerId)
            ->where('type', 'credit')
            ->where('status', 'pending')
            ->with(['payments', 'returns'])
            ->orderBy('id', 'desc')
            ->get();

        $primaryCurrency = Currency::where('is_primary', true)->first();
        
        $formattedSales = $sales->map(function($sale) use ($primaryCurrency) {
            // Logic mirrored from PartialPayment.php
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

            $totalReturnsUSD = $sale->returns->where('refund_method', 'debt_reduction')->sum('total_returned') / ($sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1);
            
            $totalUSD = $sale->total_usd ?: ($sale->total / ($sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1));
            
            $debtUSD = max(0, $totalUSD - ($totalPaidUSD + $initialPaidUSD + $totalReturnsUSD));

            return [
                'id' => $sale->id,
                'invoice_number' => $sale->invoice_number ?? "F-" . str_pad($sale->id, 6, '0', STR_PAD_LEFT),
                'date' => $sale->created_at->format('Y-m-d'),
                'total_usd' => round($totalUSD, 2),
                'debt_usd' => round($debtUSD, 2),
                'total_display' => round($totalUSD * $primaryCurrency->exchange_rate, 2),
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
            'method' => 'required|in:cash,bank,zelle',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string',
            'bank_id' => 'required_if:method,bank|exists:banks,id',
            'reference' => 'required_unless:method,cash',
            'payment_date' => 'required|date',
            'image' => 'nullable|image|max:2048',
        ]);

        if (!$request->user()->can('payments.upload')) {
            return response()->json(['message' => 'No tiene permiso para subir pagos.'], 403);
        }

        DB::beginTransaction();
        try {
            $sale = Sale::findOrFail($request->sale_id);
            $primaryCurrency = Currency::where('is_primary', true)->first();
            $paymentCurrency = Currency::where('code', $request->currency)->first();
            
            $exchangeRate = $paymentCurrency ? $paymentCurrency->exchange_rate : 1;
            
            // Image handling (using same logic as PaymentComponent)
            $imagePath = null;
            if ($request->hasFile('image')) {
                $folder = ($request->method === 'zelle') ? 'zelle_receipts' : 'bank_receipts';
                $imagePath = $request->file('image')->store($folder, 'public');
            }

            // Standardize pay_way naming for DB
            $payWay = $request->method;
            if ($payWay === 'bank') $payWay = 'deposit';

            $payment = Payment::create([
                'user_id' => Auth::id(),
                'sale_id' => $sale->id,
                'amount' => $request->amount,
                'currency' => $request->currency,
                'exchange_rate' => $exchangeRate,
                'primary_exchange_rate' => $primaryCurrency->exchange_rate,
                'pay_way' => $payWay,
                'type' => 'pay',
                'status' => 'pending',
                'bank' => $request->bank_id ? Bank::find($request->bank_id)->name : null,
                'deposit_number' => $request->reference,
                'payment_date' => $request->payment_date,
                'zelle_image' => ($request->method === 'zelle') ? $imagePath : null,
                'bank_image' => ($request->method === 'bank') ? $imagePath : null,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Pago subido con éxito. Pendiente de aprobación.',
                'payment_id' => $payment->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("API Payment Upload Error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Error al subir el pago: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Get available banks and currencies for the payment form.
     */
    public function formData()
    {
        return response()->json([
            'banks' => Bank::orderBy('sort')->get(),
            'currencies' => Currency::all()
        ]);
    }
}
