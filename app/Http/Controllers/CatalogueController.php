<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Configuration;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class CatalogueController extends Controller
{
    /**
     * Generate the professional product catalog PDF.
     * Accessible by admins and supervisors.
     */
    public function generate(Request $request)
    {
        // Increase memory and execution time for large catalogs
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 300);

        // Fetch categories with active products and their images
        $categories = Category::with(['products' => function($query) {
            $query->orderBy('name');
        }, 'products.images'])->get();

        $config = Configuration::first();

        $data = [
            'categories' => $categories,
            'config' => $config,
            'title' => 'Catálogo de Productos',
            'date' => date('d/m/Y')
        ];

        // Load the view and set options for DomPDF
        $pdf = Pdf::loadView('catalogue.pdf', $data);
        
        $pdf->setPaper('a4', 'portrait');
        
        // Ensure images from storage are allowed
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled'      => true,
            'defaultFont'          => 'sans-serif'
        ]);

        // Return the PDF stream (browser preview)
        return $pdf->stream('catalogo-de-productos-' . date('Y-m-d') . '.pdf');
    }
}
