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

        $config = \App\Models\Configuration::first();
        $logoBase64 = null;
        if ($config && $config->logo) {
            $path = public_path('storage/' . $config->logo);
            if (!file_exists($path)) {
                $path = storage_path('app/public/' . $config->logo);
            }
            if (file_exists($path)) {
                $ext = pathinfo($path, PATHINFO_EXTENSION);
                $logoBase64 = 'data:image/' . $ext . ';base64,' . base64_encode(file_get_contents($path));
            }
        }
        if (!$logoBase64) {
            $defaultLogo = public_path('logo/logo.jpg');
            if (file_exists($defaultLogo)) {
                $logoBase64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($defaultLogo));
            }
        }

        $template = session('label_template', 'standard');
        $view = $template === 'large_qr' ? 'pdf.labels_qr' : 'pdf.labels';

        $pdf = Pdf::loadView($view, compact('products', 'logoBase64'));
        $pdf->setPaper('letter', 'portrait');
        
        return $pdf->stream('etiquetas.pdf');
    }
}
