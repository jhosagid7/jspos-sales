<?php

namespace App\Http\Controllers;

use App\Models\Descargo;
use App\Models\Configuration;
use Illuminate\Http\Request;
use Jhosagid\Invoices\Invoice;
use Jhosagid\Invoices\Classes\Buyer;
use Jhosagid\Invoices\Classes\Party;
use Jhosagid\Invoices\Classes\InvoiceItem;

class DescargoController extends Controller
{
    public function pdf(Descargo $descargo)
    {
        $config = Configuration::first();
        $descargo->load(['details.product', 'user', 'warehouse']);

        $seller = new Party([
            'name'          => $config->business_name,
            'CC/NIT'        => $config->taxpayer_id,
            'address'       => $config->address,
            'city'          => $config->city,
            'phone'         => $config->phone,
            'custom_fields' => [
                'email'     => $config->email,
            ],
        ]);

        $buyer = new Party([
            'name'          => 'INTERNAL STOCK OUT',
            'custom_fields' => [
                'Depósito'       => $descargo->warehouse->name,
                'Responsable'    => $descargo->user->name,
                'Autorizado Por' => $descargo->authorized_by,
                'Motivo'         => $descargo->motive,
            ],
        ]);

        $items = [];
        foreach ($descargo->details as $detail) {
            $items[] = InvoiceItem::make($detail->product->name)
                ->reference($detail->product->sku ?? '')
                ->pricePerUnit($detail->cost) // Use the stored cost
                ->quantity($detail->quantity);
        }

        $notes = $descargo->comments ?? '';

        $invoice = Invoice::make($config->business_name)
            ->template('invoice-descargo') // Using the custom descargo template
            ->name($config->business_name)
            ->series('DESCARGO')
            ->sequence($descargo->id)
            ->serialNumberFormat('{SERIES}-{SEQUENCE}')
            ->seller($seller)
            ->buyer($buyer)
            ->dateFormat('d/m/Y')
            ->currencySymbol('')
            ->currencyCode('')
            ->currencyDecimals(2)
            ->addItems($items)
            ->notes($notes)
            ->logo($config->logo ? public_path('storage/' . $config->logo) : public_path('logo/logo.jpg'));

        return $invoice->stream();
    }
}
