<?php

namespace App\Http\Controllers;

use App\Models\Cargo;
use App\Models\Configuration;
use Illuminate\Http\Request;
use Jhosagid\Invoices\Invoice;
use Jhosagid\Invoices\Classes\Buyer;
use Jhosagid\Invoices\Classes\Party;
use Jhosagid\Invoices\Classes\InvoiceItem;

class CargoController extends Controller
{
    public function pdf(Cargo $cargo)
    {
        $config = Configuration::first();
        $cargo->load(['details.product', 'user', 'warehouse']);

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
            'name'          => 'INTERNAL STOCK ADJUSTMENT',
            'custom_fields' => [
                'Depósito'       => $cargo->warehouse->name,
                'Responsable'    => $cargo->user->name,
                'Autorizado Por' => $cargo->authorized_by,
                'Motivo'         => $cargo->motive,
            ],
        ]);

        $items = [];
        foreach ($cargo->details as $detail) {
            $items[] = InvoiceItem::make($detail->product->name)
                ->reference($detail->product->sku ?? '')
                ->pricePerUnit($detail->cost) // Use the stored cost
                ->quantity($detail->quantity);
        }

        $notes = $cargo->comments ?? '';

        $invoice = Invoice::make($config->business_name)
            ->template('invoice-cargo') // Using the custom cargo template
            ->name($config->business_name)
            ->series('CARGO')
            ->sequence($cargo->id)
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
