<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class LabelController extends Controller
{
    public function generate()
    {
        $products = session('label_products', []);
        
        if (empty($products)) {
            return redirect()->route('labels.index');
        }

        $pdf = Pdf::loadView('pdf.labels', compact('products'));
        $pdf->setPaper('letter', 'portrait');
        
        return $pdf->stream('etiquetas.pdf');
    }
}
