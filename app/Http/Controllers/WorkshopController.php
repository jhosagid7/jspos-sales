<?php

namespace App\Http\Controllers;

use App\Models\WorkOrder;
use App\Services\WorkshopPrintService;
use Illuminate\Http\Request;

class WorkshopController extends Controller
{
    public function pdf(WorkOrder $order, Request $request)
    {
        $type = $request->query('type', 'reception');
        $service = app(WorkshopPrintService::class);
        $pdf = $service->generatePdf($order, $type);

        $filename = "Orden_Trabajo_{$order->order_number}.pdf";
        return $pdf->stream($filename);
    }
}
