<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class LabelController extends Controller
{
    public function generate()
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(300);

        $products = session('label_products', []);
        
        if (empty($products)) {
            return redirect()->route('labels.index');
        }

        $config = \App\Models\Configuration::first();
        $logoPath = null;
        $logoBase64 = null;

        if ($config && $config->logo) {
            $path = public_path('storage/' . $config->logo);
            if (!file_exists($path)) {
                $path = storage_path('app/public/' . $config->logo);
            }
            if (file_exists($path)) {
                $logoPath = $path;
                $ext = pathinfo($path, PATHINFO_EXTENSION);
                $logoBase64 = 'data:image/' . $ext . ';base64,' . base64_encode(file_get_contents($path));
            }
        }

        if (!$logoPath) {
            $defaultLogo = public_path('logo/logo.jpg');
            if (file_exists($defaultLogo)) {
                $logoPath = $defaultLogo;
                $logoBase64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($defaultLogo));
            }
        }

        $template = session('label_template', 'standard');
        $view = $template === 'large_qr' ? 'pdf.labels_qr' : 'pdf.labels';

        // Pre-compute barcode images once per unique barcode
        $barcodes = [];
        foreach ($products as $product) {
            $code = $product['barcode'] ?? $product['sku'] ?? '';
            if ($code !== '' && !isset($barcodes[$code])) {
                try {
                    if ($template === 'large_qr') {
                        $barcodes[$code] = \DNS2D::getBarcodePNG($code, 'QRCODE');
                    } else {
                        $barcodes[$code] = \DNS1D::getBarcodePNG($code, 'C128');
                    }
                } catch (\Throwable $e) {
                    $barcodes[$code] = null;
                }
            }
        }

        $pdf = Pdf::loadView($view, compact('products', 'logoPath', 'logoBase64', 'barcodes'));
        $pdf->setPaper('letter', 'portrait');
        
        return $pdf->stream('etiquetas.pdf');
    }
}
