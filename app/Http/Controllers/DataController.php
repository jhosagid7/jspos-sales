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
              ->orWhere('address', 'like', "%{$valueToSearch}%")
              ->orWhere('email', 'like', "%{$valueToSearch}%");
        });

        if (!auth()->user()->can('customers.view_all') && auth()->user()->can('customers.view_own')) {
            $query->where('seller_id', auth()->user()->id);
        }

        $clients = $query->get();

        return response()->json($clients);
    }

    public function autocomplete_suppliers(Request $request)
    {
        $valueToSearch = $request->get('q');

        $suppliers = Supplier::where('name', 'like', "%{$valueToSearch}%")
            ->orWhere('address', 'like', "%{$valueToSearch}%")
            ->orWhere('phone', 'like', "%{$valueToSearch}%")
            ->get();

        return response()->json($suppliers);
    }

    public function autocomplete_products(Request $request)
    {
        $valueToSearch = $request->get('q');

        $suppliers = Product::where('name', 'like', "%{$valueToSearch}%")
            ->orWhere('sku', 'like', "%{$valueToSearch}%")
            ->orderBy('name')
            ->get();

        return response()->json($suppliers);
    }

    public function customerDebtPdf($customerId)
    {
        $customer = Customer::with('seller')->findOrFail($customerId);
        
        // Load outstanding invoices
        $outstandingSales = \App\Models\Sale::where('customer_id', $customerId)
            ->where('credit_days', '>', 0)
            ->with(['payments' => function($q) {
                $q->where('status', 'approved');
            }])
            ->orderBy('created_at', 'desc')
            ->get();
        
        $invoices = [];
        $totalDebt = 0;
        
        foreach($outstandingSales as $sale) {
            $approvedPayments = $sale->payments->sum('amount');
            $pending = $sale->total - $approvedPayments;
            
            if($pending > 0.01) { // Has pending balance
                $dueDate = \Carbon\Carbon::parse($sale->created_at)->addDays($sale->credit_days);
                $invoices[] = [
                    'invoice_number' => $sale->invoice_number,
                    'created_at' => $sale->created_at->format('d/m/Y'),
                    'due_date' => $dueDate->format('d/m/Y'),
                    'total' => $sale->total,
                    'paid' => $approvedPayments,
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
