<?php

namespace App\Traits;

use App\Models\DebitNote;
use App\Models\Configuration;
use App\Services\ConfigurationService;
use Illuminate\Support\Facades\Log;
use Jhosagid\Invoices\Invoice;
use Jhosagid\Invoices\Classes\Party;
use Jhosagid\Invoices\Classes\InvoiceItem;
use App\Helpers\CurrencyHelper;

trait PdfDebitNoteTrait
{
    public function generatePdfDebitNote(DebitNote $note)
    {
        try {
            $note->load(['customer.seller', 'user', 'sale']);
            $config = Configuration::first();

            if (!$config) {
                return response()->json(['error' => 'No hay configuración del sistema.'], 500);
            }

            $seller = new Party([
                'name'          => $config->business_name,
                'CC/NIT'        => $config->taxpayer_id,
                'address'       => $config->address,
                'city'          => $config->city,
                'phone'         => $config->phone,
                'custom_fields' => [
                    'email'         => $config->email,
                    'operador'      => $note->user->name,
                    'cloning_qr'    => \DNS2D::getBarcodeHTML("DEBIT_NOTE:{$note->id}", "QRCODE", 2, 2)
                ],
            ]);

            $customer = new Party([
                'name'          => $note->customer->name,
                'custom_fields' => [
                    'CC/NIT'        => $note->customer->taxpayer_id,
                    'address'       => $note->customer->address,
                    'city'          => $note->customer->city,
                    'phone'         => $note->customer->phone,
                    'email'         => $note->customer->email,
                ],
            ]);

            // For Debit Notes, the "item" is the concept of the adjustment
            $items = [
                InvoiceItem::make($note->concept)
                    ->pricePerUnit($note->amount)
                    ->quantity(1)
            ];

            $currencyCode = $note->currency ?? 'USD';
            $currencySymbol = CurrencyHelper::getSymbol($currencyCode);

            $logoPath = $config->logo ? public_path('storage/' . $config->logo) : public_path('logo/logo.jpg');
            if (!file_exists($logoPath)) $logoPath = null;

            $invoice = Invoice::make('NOTA DE DEBITO')
                ->template('invoice-debit-note')
                ->status('PENDIENTE')
                ->series('ND')
                ->sequence($note->id)
                ->serialNumberFormat('ND-{SEQUENCE}')
                ->seller($seller)
                ->buyer($customer)
                ->date($note->created_at)
                ->dateFormat('d-M-Y')
                ->currencySymbol($currencySymbol)
                ->currencyCode($currencyCode)
                ->currencyDecimals(ConfigurationService::getDecimalPlaces())
                ->addItems($items)
                ->notes($note->sale ? "Vinculada a Factura: " . ($note->sale->invoice_number ?? $note->sale->id) : '')
                ->logo($logoPath ?? '');

            return $invoice->stream();

        } catch (\Exception $th) {
            Log::error("Error generating PDF for Debit Note ID: {$note->id}: " . $th->getMessage());
            return response()->json(['error' => 'Error al generar PDF: ' . $th->getMessage()], 500);
        }
    }
}
