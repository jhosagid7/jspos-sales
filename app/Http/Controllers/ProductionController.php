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

        $isBags = $production->details->contains(function ($detail) {
            $product = $detail->product;
            if (!$product) return false;
            
            $hasTag = $product->tags()->where('name', 'M&F')->exists();
            $hasSupplier = $product->supplier && str_contains($product->supplier->name, 'M&F Steel');
            
            return $hasTag || $hasSupplier;
        });

        $view = $isBags ? 'pdf.bags_production' : 'pdf.production';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($view, compact('production'));
        $pdf->setPaper('letter', 'portrait');
        
        return $pdf->stream('produccion_' . $production->id . '.pdf');
    }
}
