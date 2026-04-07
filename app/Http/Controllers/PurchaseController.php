<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Configuration;
use Illuminate\Http\Request;
use Jhosagid\Invoices\Invoice;
use Jhosagid\Invoices\Classes\Buyer;
use Jhosagid\Invoices\Classes\Party;
use Jhosagid\Invoices\Classes\InvoiceItem;
use App\Services\ConfigurationService;

class PurchaseController extends Controller
{
    public function pdf(Purchase $purchase)
    {
        $config = Configuration::first();
        $purchase->load(['details.product', 'user', 'supplier']);

        $seller = new Party([
            'name'          => $config->business_name,
            'address'       => $config->address,
            'phone'         => $config->phone,
            'custom_fields' => [
                'CC/NIT'        => $config->taxpayer_id,
                'email'         => $config->email,
                'city'          => $config->city,
                'operador'      => $purchase->user->name,
                'cloning_qr'    => \DNS2D::getBarcodeHTML("PURCHASE:{$purchase->id}", "QRCODE", 2, 2),
            ],
        ]);

        $buyer = new Party([
            'name'          => $purchase->supplier->name ?? 'PROVEEDOR GENERICO',
            'address'       => $purchase->supplier->address ?? 'N/A',
            'phone'         => $purchase->supplier->phone ?? 'N/A',
            'custom_fields' => [
                'CC/NIT'           => $purchase->supplier->taxpayer_id ?? 'N/A',
            ],
        ]);

        $items = [];
        foreach ($purchase->details as $detail) {
            $items[] = InvoiceItem::make($detail->product->name)
                ->reference($detail->product->sku ?? '')
                ->pricePerUnit($detail->cost)
                ->quantity($detail->quantity);
        }

        $notes = $purchase->notes ?? '';

        $invoice = Invoice::make($config->business_name)
            ->template('invoice-purchase-order')
            ->name('ORDEN DE COMPRA')
            ->series('OC')
            ->sequence($purchase->id)
            ->serialNumberFormat('{SEQUENCE}')
            ->seller($seller)
            ->buyer($buyer)
            ->dateFormat('d/m/Y')
            ->currencySymbol('$')
            ->currencyCode('COP')
            ->currencyDecimals(ConfigurationService::getDecimalPlaces())
            ->addItems($items)
            ->notes($notes)
            ->logo($config->logo ? public_path('storage/' . $config->logo) : public_path('logo/logo.jpg'));

        return $invoice->stream();
    }
}
