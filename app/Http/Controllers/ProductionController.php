<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProductionController extends Controller
{
    public function pdf($id)
    {
        $production = \App\Models\Production::with(['details.product', 'user'])->find($id);
        
        if (!$production) {
            return redirect()->back();
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.production', compact('production'));
        $pdf->setPaper('letter', 'portrait');
        
        return $pdf->stream('produccion_' . $production->id . '.pdf');
    }
}
