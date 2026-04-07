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
     * Generate the professional product catalog PDF with Base64 images for maximum stability.
     */
    public function generate(Request $request)
    {
        // Increase memory and execution time for large catalogs
        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', 600);

        // Fetch categories with active products and their images
        $categories = Category::with(['products' => function($query) {
            $query->orderBy('name');
        }, 'products.images'])->get();

        $config = Configuration::first();

        // Convert Logo to Base64
        $logoBase64 = null;
        if ($config && $config->logo) {
            $logoPath = storage_path('app/public/' . $config->logo);
            if (file_exists($logoPath)) {
                $logoBase64 = $this->imageToBase64($logoPath);
            }
        }
        
        // Fallback for logo if not found in storage
        if (!$logoBase64) {
            $fallbackLogo = public_path('assets/images/logo/logo-icon.png');
            if (file_exists($fallbackLogo)) {
                $logoBase64 = $this->imageToBase64($fallbackLogo);
            }
        }

        // Prepare products with Base64 images to avoid path issues in DomPDF
        foreach ($categories as $category) {
            foreach ($category->products as $product) {
                $product->image_base64 = $this->getProductImageBase64($product);
            }
        }

        $data = [
            'categories' => $categories,
            'config' => $config,
            'logo' => $logoBase64,
            'title' => 'Catálogo de Productos',
            'date' => date('d/m/Y')
        ];

        // Load the view and set options for DomPDF
        $pdf = Pdf::loadView('catalogue.pdf', $data);
        
        $pdf->setPaper('a4', 'portrait');
        
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled'      => true,
            'defaultFont'          => 'sans-serif'
        ]);

        return $pdf->stream('catalogo-de-productos-' . date('Y-m-d') . '.pdf');
    }

    /**
     * Helper to convert an image path to Base64.
     */
    private function imageToBase64($path)
    {
        try {
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $data = file_get_contents($path);
            return 'data:image/' . $type . ';base64,' . base64_encode($data);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get the best available image for a product in Base64 format.
     */
    private function getProductImageBase64($product)
    {
        // Try to get the latest product image from storage
        if ($product->images->count() > 0) {
            $fileName = $product->images->last()->file;
            $path = storage_path('app/public/products/' . $fileName);
            if (file_exists($path)) {
                return $this->imageToBase64($path);
            }
        }

        // Fallback to noimage.jpg
        $noImagePath = public_path('noimage.jpg');
        if (file_exists($noImagePath)) {
            return $this->imageToBase64($noImagePath);
        }

        return null;
    }
}
