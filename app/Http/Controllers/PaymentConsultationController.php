<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ZelleRecord;
use App\Models\BankRecord;
use PDF;

class PaymentConsultationController extends Controller
{
    public function generateFilteredZellePdf(Request $request)
    {
        if (!auth()->user()->can('zelle_print_pdf')) {
            abort(403, 'No tienes permiso para imprimir este reporte.');
        }

        $query = ZelleRecord::query()->with([
            'payments.user',
            'payments.sale.customer',
            'payments.sale.user',
            'salePaymentDetails.sale.customer'
        ]);

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('reference', 'like', '%' . $request->search . '%')
                  ->orWhere('sender_name', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->date_from) {
            $query->whereDate('zelle_date', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('zelle_date', '<=', $request->date_to);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $records = $query->orderBy('zelle_date', 'desc')->get();

        $pdf = PDF::loadView('reports.zelle-filtered-payments-pdf', compact('records'));
        return $pdf->stream('Reporte_Capturas_Zelle_Filtradas.pdf');
    }

    public function generateZellePdf($id)
    {
        if (!auth()->user()->can('zelle_print_pdf')) {
            abort(403, 'No tienes permiso para imprimir este reporte.');
        }

        $record = ZelleRecord::with(['payments.user', 'payments.sale.customer', 'payments.sale.user'])->findOrFail($id);

        $pdf = PDF::loadView('reports.zelle-payment-pdf', compact('record'));
        return $pdf->stream('Reporte_Zelle_' . $record->reference . '.pdf');
    }

    public function generateBankPdf($id)
    {
        if (!auth()->user()->can('bank_print_pdf')) {
            abort(403, 'No tienes permiso para imprimir este reporte.');
        }

        $record = BankRecord::with(['payments.user', 'payments.sale.customer', 'payments.sale.user'])->findOrFail($id);

        $pdf = PDF::loadView('reports.bank-payment-pdf', compact('record'));
        return $pdf->stream('Reporte_Banco_' . $record->reference . '.pdf');
    }
}

