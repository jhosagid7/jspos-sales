<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;

class DataController extends Controller
{


    public function autocomplete_customers(Request $request)
    {
        $valueToSearch = $request->get('q');

        $query = Customer::where(function($q) use ($valueToSearch) {
            $q->where('name', 'like', "%{$valueToSearch}%")
              ->orWhere('taxpayer_id', 'like', "%{$valueToSearch}%")
              ->orWhere('address', 'like', "%{$valueToSearch}%")
              ->orWhere('email', 'like', "%{$valueToSearch}%");
        });

        if (!auth()->user()->can('customers.view_all') && auth()->user()->can('customers.view_own')) {
            $sharedIds = auth()->user()->getSharedSellerIds();
            if ($request->has('seller_id') && $request->seller_id > 0) {
                $requestedSellerId = (int)$request->seller_id;
                if (in_array($requestedSellerId, $sharedIds)) {
                    $query->where('seller_id', $requestedSellerId);
                } else {
                    $query->whereRaw('1 = 0');
                }
            } else {
                $query->whereIn('seller_id', $sharedIds);
            }
        } elseif ($request->has('seller_id') && $request->seller_id > 0) {
            $query->where('seller_id', $request->seller_id);
        }

        $clients = $query->take(30)->get();

        return response()->json($clients);
    }

    public function autocomplete_suppliers(Request $request)
    {
        $valueToSearch = $request->get('q');

        $suppliers = Supplier::where('name', 'like', "%{$valueToSearch}%")
            ->orWhere('address', 'like', "%{$valueToSearch}%")
            ->orWhere('phone', 'like', "%{$valueToSearch}%")
            ->take(30)
            ->get();

        return response()->json($suppliers);
    }

    public function autocomplete_products(Request $request)
    {
        $valueToSearch = $request->get('q');

        $suppliers = Product::where('name', 'like', "%{$valueToSearch}%")
            ->orWhere('sku', 'like', "%{$valueToSearch}%")
            ->orderBy('name')
            ->take(30)
            ->get();

        return response()->json($suppliers);
    }

    public function customerDebtPdf($customerId)
    {
        $customer = Customer::with('seller')->findOrFail($customerId);
        
        // Load outstanding invoices
        $outstandingSales = \App\Models\Sale::where('customer_id', $customerId)
            ->where('credit_days', '>', 0)
            ->whereNotIn('status', ['returned', 'voided', 'paid'])
            ->with([
                'payments' => function($q) {
                    $q->where('status', 'approved');
                },
                'returns',
                'paymentDetails'
            ])
            ->orderBy('created_at', 'desc')
            ->get();
        
        $invoices = [];
        $totalDebt = 0;
        
        foreach($outstandingSales as $sale) {
            $approvedPaymentsUSD = $sale->payments->sum(function($payment) {
                $rate = $payment->exchange_rate > 0 ? $payment->exchange_rate : 1;
                return $payment->amount / $rate;
            });
            
            $totalReturnsUSD = $sale->returns->where('refund_method', 'debt_reduction')->where('status', 'approved')->sum(function($ret) use ($sale) {
                $rate = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;
                return $ret->total_returned / $rate;
            });
            
            $initialPaidUSD = $sale->paymentDetails->sum(function($detail) {
                $rate = $detail->exchange_rate > 0 ? $detail->exchange_rate : 1;
                return $detail->amount / $rate;
            });

            // Convert USD values to sale's primary currency
            $saleRate = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;
            $approvedPayments = $approvedPaymentsUSD * $saleRate;
            $initialPaid = $initialPaidUSD * $saleRate;
            $totalReturns = $totalReturnsUSD * $saleRate;
            
            $totalCredited = $approvedPayments + $initialPaid + $totalReturns;
            $pending = max(0, $sale->total - $totalCredited);
            
            if($pending > 0.01) { // Has pending balance
                $dueDate = \Carbon\Carbon::parse($sale->created_at)->addDays($sale->credit_days);
                $invoices[] = [
                    'invoice_number' => $sale->invoice_number,
                    'created_at' => $sale->created_at->format('d/m/Y'),
                    'due_date' => $dueDate->format('d/m/Y'),
                    'total' => $sale->total,
                    'paid' => $totalCredited,
                    'pending' => $pending,
                    'is_overdue' => now()->gt($dueDate),
                ];
                $totalDebt += $pending;
            }
        }
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.customer-debt', [
            'customer' => $customer,
            'invoices' => $invoices,
            'totalDebt' => $totalDebt,
            'generatedAt' => now()->format('d/m/Y H:i:s'),
        ]);
        
        return $pdf->stream('estado-cuenta-' . $customer->name . '.pdf');
    }
}
