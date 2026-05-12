<?php

namespace App\Http\Controllers;

use App\Models\Transfer;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class TransferController extends Controller
{
    public function pdf($id)
    {
        $transfer = Transfer::with(['details.product', 'fromWarehouse', 'toWarehouse', 'user'])->findOrFail($id);
        
        $pdf = Pdf::loadView('pdf.transfer', compact('transfer'));
        
        return $pdf->stream('traspaso_' . $transfer->id . '.pdf');
    }
}
