<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ZelleRecord;
use App\Models\BankRecord;
use PDF;

class PaymentConsultationController extends Controller
{
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
