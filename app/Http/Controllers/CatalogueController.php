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
     * Generate the professional product catalog PDF with Base64 images and performance caching.
     */
    public function generate(Request $request)
    {
        // Increase memory and execution time for large catalogs
        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', 600);

        // Fetch only categories that actually have available products
        $categories = Category::with(['products' => function($query) {
            $query->where('status', 'available')->orderBy('name');
        }, 'products.images'])
        ->get()
        ->filter(function($category) {
            return $category->products->count() > 0;
        });

        $config = Configuration::first();

        // Convert Logo to Base64 (Cache for 1 day)
        $logoBase64 = null;
        if ($config && $config->logo) {
            $logoPath = storage_path('app/public/' . $config->logo);
            if (file_exists($logoPath)) {
                $cacheKey = 'base64_logo_' . md5($config->logo . filemtime($logoPath));
                $logoBase64 = cache()->remember($cacheKey, now()->addDay(), function() use ($logoPath) {
                    return $this->imageToBase64($logoPath);
                });
            }
        }
        
        // Fallback for logo
        if (!$logoBase64) {
            $fallbackLogo = public_path('assets/images/logo/logo-icon.png');
            if (file_exists($fallbackLogo)) {
                $logoBase64 = $this->imageToBase64($fallbackLogo);
            }
        }

        // Prepare products with cached Base64 images
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

        // Load the view and set options for DomPDF speed
        $pdf = Pdf::loadView('catalogue.pdf', $data);
        
        $pdf->setPaper('a4', 'portrait');
        
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled'      => true,
            'defaultFont'          => 'sans-serif',
            'isFontSubsettingEnabled' => true, // Faster font processing
            'isPhpEnabled' => false
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
     * Get the best available image for a product in Base64 format (with caching).
     */
    private function getProductImageBase64($product)
    {
        // Try to get the latest product image
        if ($product->images->count() > 0) {
            $imageModel = $product->images->last();
            $fileName = $imageModel->file;
            $path = storage_path('app/public/products/' . $fileName);
            
            if (file_exists($path)) {
                // Cache key based on product ID and file modification time to ensure freshness
                $cacheKey = 'base64_img_prod_' . $product->id . '_' . md5($fileName . filemtime($path));
                
                return cache()->remember($cacheKey, now()->addDays(7), function() use ($path) {
                    return $this->imageToBase64($path);
                });
            }
        }

        // Fallback to noimage.jpg (cached)
        $noImagePath = public_path('noimage.jpg');
        if (file_exists($noImagePath)) {
            return cache()->remember('base64_noimage_fallback', now()->addMonth(), function() use ($noImagePath) {
                return $this->imageToBase64($noImagePath);
            });
        }

        return null;
    }
}
