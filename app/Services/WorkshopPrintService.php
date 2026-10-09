<?php

namespace App\Services;

use App\Models\Configuration;
use App\Models\WorkOrder;
use Barryvdh\DomPDF\Facade\Pdf;

class WorkshopPrintService
{
    // ESC/POS Command Byte Sequences
    public const ESC = "\x1b";
    public const GS  = "\x1d";

    public const INIT_PRINTER     = "\x1b\x40";
    public const ALIGN_LEFT       = "\x1b\x61\x00";
    public const ALIGN_CENTER     = "\x1b\x61\x01";
    public const ALIGN_RIGHT      = "\x1b\x61\x02";
    public const BOLD_ON          = "\x1b\x45\x01";
    public const BOLD_OFF         = "\x1b\x45\x00";
    public const FEED_AND_CUT     = "\x1d\x56\x41\x03";

    /**
     * Build ESC/POS formatted thermal ticket for Reception or Delivery.
     */
    public function buildThermalTicket(WorkOrder $order, string $type = 'reception', int $widthMm = 80): string
    {
        $maxWidth = ($widthMm === 58) ? 32 : 48;
        $config = Configuration::first();
        $businessName = $config?->business_name ?? 'TALLER AUTOMOTRIZ';
        $taxpayerId = $config?->taxpayer_id ?? '';
        $phone = $config?->phone ?? '';

        $out = '';
        $out .= self::INIT_PRINTER;

        // Header
        $out .= self::ALIGN_CENTER;
        $out .= self::BOLD_ON . $this->center($businessName, $maxWidth) . "\n" . self::BOLD_OFF;
        if ($taxpayerId) {
            $out .= $this->center("RIF: {$taxpayerId}", $maxWidth) . "\n";
        }
        if ($phone) {
            $out .= $this->center("Telf: {$phone}", $maxWidth) . "\n";
        }

        $out .= str_repeat('-', $maxWidth) . "\n";

        // Title
        $title = ($type === 'reception') ? 'FICHA DE RECEPCION' : 'COMPROBANTE DE ENTREGA';
        $out .= self::BOLD_ON . $this->center($title, $maxWidth) . "\n" . self::BOLD_OFF;
        $out .= $this->center("ORDEN: {$order->order_number}", $maxWidth) . "\n";
        $out .= str_repeat('-', $maxWidth) . "\n";

        // Customer & Vehicle Details
        $out .= self::ALIGN_LEFT;
        $out .= "Fecha: " . $order->created_at->format('d/m/Y H:i') . "\n";
        $out .= "Cliente: " . ($order->customer?->name ?? 'Cliente General') . "\n";
        if ($order->customer?->taxpayer_id) {
            $out .= "CI/RIF: " . $order->customer->taxpayer_id . "\n";
        }

        $out .= str_repeat('.', $maxWidth) . "\n";
        $out .= self::BOLD_ON . "DATOS DEL VEHICULO:" . self::BOLD_OFF . "\n";
        $out .= "Placa: " . self::BOLD_ON . ($order->vehicle?->plate ?? 'S/P') . self::BOLD_OFF . "\n";
        $out .= "Vehiculo: " . ($order->vehicle?->brand ?? '') . " " . ($order->vehicle?->model ?? '') . "\n";
        $out .= "Km Entrada: " . number_format($order->entry_mileage) . " Km\n";
        if ($type === 'delivery' && $order->exit_mileage) {
            $out .= "Km Salida:  " . number_format($order->exit_mileage) . " Km\n";
        }
        $out .= "Gasolina: " . ($order->fuel_level ?? 'No especificado') . "\n";

        if ($type === 'reception') {
            $out .= str_repeat('-', $maxWidth) . "\n";
            $out .= self::BOLD_ON . "MOTIVO / DIAGNOSTICO:" . self::BOLD_OFF . "\n";
            $out .= wordwrap($order->initial_diagnosis, $maxWidth, "\n") . "\n\n";

            $out .= self::ALIGN_CENTER;
            $out .= "\n\n" . str_repeat('_', min(28, $maxWidth - 4)) . "\n";
            $out .= "Firma Conforme Recepcion\n";
            $out .= "Estimado cliente, conserve este ticket\n";
        } else {
            // Delivery items breakdown
            $out .= str_repeat('-', $maxWidth) . "\n";
            $out .= self::BOLD_ON . $this->formatTwoColumns("DESCRIPCION", "TOTAL", $maxWidth) . self::BOLD_OFF . "\n";
            $out .= str_repeat('-', $maxWidth) . "\n";

            foreach ($order->items as $item) {
                $desc = ($item->type === 'part' ? '[R] ' : '[MO] ') . $item->description;
                $price = number_format($item->subtotal, 2);
                $out .= $this->formatTwoColumns($desc, $price, $maxWidth) . "\n";
            }

            $out .= str_repeat('=', $maxWidth) . "\n";
            $out .= $this->formatTwoColumns("Subtotal Repuestos:", "$" . number_format($order->subtotal_parts, 2), $maxWidth) . "\n";
            $out .= $this->formatTwoColumns("Subtotal Mano Obra:", "$" . number_format($order->subtotal_labor, 2), $maxWidth) . "\n";
            $out .= self::BOLD_ON . $this->formatTwoColumns("TOTAL GENERAL:", "$" . number_format($order->total, 2), $maxWidth) . self::BOLD_OFF . "\n";

            $out .= self::ALIGN_CENTER;
            $out .= "\n\n" . str_repeat('_', min(28, $maxWidth - 4)) . "\n";
            $out .= "Firma Cliente / Vehiculo Retirado\n";
            $out .= "Gracias por preferir nuestros servicios!\n";
        }

        $out .= "\n\n" . self::FEED_AND_CUT;

        return $out;
    }

    /**
     * Generate PDF document for workshop work order.
     */
    public function generatePdf(WorkOrder $order, string $type = 'reception')
    {
        $config = Configuration::first();
        return Pdf::loadView('pdf.workshop-work-order', compact('order', 'config', 'type'));
    }

    protected function formatTwoColumns(string $left, string $right, int $maxWidth = 48): string
    {
        $rightLen = strlen($right);
        $maxLeftLen = $maxWidth - $rightLen - 1;
        $left = mb_substr($left, 0, max(1, $maxLeftLen));
        $spaces = $maxWidth - strlen($left) - $rightLen;
        return $left . str_repeat(' ', max(1, $spaces)) . $right;
    }

    protected function center(string $text, int $width = 48): string
    {
        $len = strlen($text);
        if ($len >= $width) {
            return $text;
        }
        $pad = (int)floor(($width - $len) / 2);
        return str_repeat(' ', $pad) . $text;
    }
}
