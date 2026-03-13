<?php

namespace App\Traits;

use App\Services\ConfigurationService;

use Carbon\Carbon;
use App\Models\Sale;
use App\Models\Payment;
use App\Models\Configuration;
use Illuminate\Support\Facades\Log;


use Jhosagid\Invoices\Invoice;
use Jhosagid\Invoices\Classes\Buyer;
use Jhosagid\Invoices\Classes\Party;
use Jhosagid\Invoices\Classes\Seller;
use Jhosagid\Invoices\Classes\InvoiceItem;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\FooterCodeService;
use App\Services\CreditConfigService;


trait PdfInvoiceTrait
{

    public function generatePdfInvoice(Sale $sale, $originalOnly = false)
    {
        try {
            Log::info("PDF Generation requested for Sale ID: {$sale->id} | Status: {$sale->status}");
            
            // Load necessary relations including returns
            $sale->loadMissing(['customer.seller', 'user', 'details.product', 'returns.details']);
            
            $config = Configuration::first();

            if ($config) {
                if ($sale->status == 'paid') {
                    Log::info("Generating PAID invoice for Sale ID: {$sale->id}");
                    return $this->generatePdfInvoicePaid($sale, $originalOnly);
                }
                if ($sale->status == 'pending') {
                    Log::info("Generating PENDING invoice for Sale ID: {$sale->id}");
                    return $this->generatePdfInvoicePending($sale, $originalOnly);
                }
                
                Log::warning("Sale status '{$sale->status}' is not supported for PDF generation. Sale ID: {$sale->id}");
                return response()->json(['error' => "El estado de la venta '{$sale->status}' no permite generar PDF."], 422);

            } else {
                Log::error("Configuration table is empty. Cannot generate PDF.");
                return response()->json(['error' => 'No hay configuración del sistema.'], 500);
            }
        } catch (\Exception $th) {
            Log::error("Error generating PDF for Sale ID: {$sale->id}: " . $th->getMessage());
            return response()->json(['error' => 'Error interno al generar PDF.'], 500);
        }
    }

    public function generatePdfInvoicePaid($sale, $originalOnly = false)
    {
        try {
            $config = Configuration::first();

            if ($config) {

                // $sale = Sale::with(['customer', 'user', 'details', 'details.product'])->find($sale->id);

                $footerData = $this->getInvoiceFooterData($sale);

                $seller = new Party([
                    'name'          => $config->business_name,
                    'CC/NIT'           => $config->taxpayer_id,
                    'address'       => $config->address,
                    'city'           => $config->city,
                    'phone'         => $sale->customer->phone,

                    'custom_fields' => [
                        'email'         => $sale->customer->email,
                        'vendedor'        => $sale->customer->seller ? $sale->customer->seller->name : 'N/A',
                        'operador'        => $sale->user->name,
                        'footer_code'    => $footerData['footer_code'],
                        'footer_data'    => $footerData
                    ],
                ]);

                $customer = new Party([
                    'name'          => $sale->customer->name,


                    'custom_fields' => [
                        'CC/NIT'           => $sale->customer->taxpayer_id,
                        'address'       => $sale->customer->address,
                        'city'           => $sale->customer->city,
                        'phone'         => $sale->customer->phone,
                        'email'         => $sale->customer->email,
                    ],
                ]);

                $totalFreight = 0;
                $totalTax = 0;
                $totalBaseAccumulator = 0;
                $isBrokenDown = $sale->is_freight_broken_down;

                // Calculate combined tax/diff percentage
                $commPercent = $sale->applied_commission_percent ?? 0;
                $diffPercent = $sale->applied_exchange_diff_percent ?? 0;
                $combinedPercent = ($commPercent + $diffPercent) / 100;

                // Pre-calculate returned quantities
                $returnedQuantities = [];
                if (!$originalOnly && $sale->returns) {
                    foreach ($sale->returns as $return) {
                        foreach ($return->details as $retDetail) {
                            $returnedQuantities[$retDetail->sale_detail_id] = ($returnedQuantities[$retDetail->sale_detail_id] ?? 0) + $retDetail->quantity_returned;
                        }
                    }
                }

                foreach ($sale->details as $detail) {
                    $effectiveQty = $detail->quantity - ($returnedQuantities[$detail->id] ?? 0);
                    
                    if ($isBrokenDown) {
                        $totalFreight += $detail->freight_amount;
                        
                        if ($effectiveQty > 0) {
                            $unitPrice = $detail->sale_price;
                            
                            $items[] = InvoiceItem::make($detail->product->name)
                                ->reference($detail->product->sku ? $detail->product->sku : '')
                                ->pricePerUnit($unitPrice)
                                ->quantity($effectiveQty);
                            
                            $totalBaseAccumulator += ($unitPrice * $effectiveQty);
                        }
                    } else {
                        // Breakdown OFF: sale_price INCLUDES freight
                        if ($effectiveQty > 0) {
                            $unitPrice = $detail->sale_price;
                            
                            $items[] = InvoiceItem::make($detail->product->name)
                                ->reference($detail->product->sku ? $detail->product->sku : '')
                                ->pricePerUnit($unitPrice)
                                ->quantity($effectiveQty);

                            $totalBaseAccumulator += ($unitPrice * $effectiveQty);
                        }
                    }
                }
                
                $notes = [
                    $sale->notes
                ];
                $notes = implode("<br>", $notes);

                $credit_days = $sale->type == 'credit' ? ($footerData['credit_days'] ?? 0) : 0;

                $currencySymbol = '$';
                $currencyCode = 'USD';
                
                if ($sale->primary_currency_code) {
                    $currencySymbol = \App\Helpers\CurrencyHelper::getSymbol($sale->primary_currency_code);
                    $currencyCode = $sale->primary_currency_code;
                } else {
                    $primary = \App\Helpers\CurrencyHelper::getPrimaryCurrency();
                    if ($primary) {
                        $currencySymbol = $primary->symbol;
                        $currencyCode = $primary->code;
                    }
                }

                $logoPath = $config->logo ? public_path('storage/' . $config->logo) : public_path('logo/logo.jpg');
                if (!file_exists($logoPath)) {
                    Log::warning("Logo file not found at: $logoPath. Using default.");
                    $logoPath = public_path('logo/logo.jpg');
                    if (!file_exists($logoPath)) {
                        Log::warning("Default logo not found either. PDF will have no logo.");
                        $logoPath = null;
                        // Or maybe a transparent 1x1 pixel image if the library requires it?
                        // Jhosagid\Invoices likely handles null or missing logo gracefully OR crashes if 'logo' method is called with bad path via dompdf.
                        // Ideally we pass a valid path or empty string.
                    }
                }
                
                Log::info("Using Logo Path: " . ($logoPath ?? 'NONE'));

                $invoice = Invoice::make($config->business_name)->template('invoice-paid-short')
                    ->series('remision_numero')
                    // ability to include translated invoice status
                    // in case it was paid
                    ->status(__('invoices::invoice.paid'))
                    ->sequence($sale->id)
                    ->serialNumberFormat('{SEQUENCE}')
                    ->seller($seller)
                    ->buyer($customer)
                    // ->date(now()->subWeeks(3))
                    ->dateFormat('d-M-Y')
                    ->payUntilDays($credit_days)
                    ->currencySymbol($currencySymbol)
                    ->currencyCode($currencyCode)
                    ->currencyDecimals(ConfigurationService::getDecimalPlaces())
                    ->currencyFormat('{SYMBOL}{VALUE}')
                    ->currencyThousandsSeparator('.')
                    ->currencyDecimalPoint(',')
                    // ->filename($seller->name . ' ' . $customer->name)
                    ->addItems($items)
                    ->notes($notes)
                    ->logo($logoPath ?? '');

                // Set Taxable Amount (Subtotal) - Must be called after invoice creation but before save
                if ($totalBaseAccumulator > 0) {
                    $invoice->taxableAmount($totalBaseAccumulator);
                }

                // Set freight and taxes if breakdown is enabled - Must be before save()
                if ($isBrokenDown) {
                    if ($totalFreight > 0) {
                        $invoice->shipping($totalFreight); 
                    }
                    if ($totalTax > 0) {
                        $invoice->totalTaxes($totalTax);
                    }
                }

                // Save after all properties are set
                $invoice->save('public');

                $link = $invoice->url();
                // Then send email to party with link

                // And return invoice itself to browser or have a different view
                return $invoice->stream();
            } else {
                Log::info("La tabla configurations está vacía, no es posible imprimir la venta");
            }
        } catch (\Exception $th) {
            Log::error("Error generating PAID invoice for Sale ID: {$sale->id}: " . $th->getMessage());
            return response()->json(['error' => 'Error generating PDF: ' . $th->getMessage()], 500);
        }
    }

    public function getSavedPdfInvoicePathPaid(Sale $sale, $customFilename)
    {
        try {
            $config = Configuration::first();

            if ($config) {
                $sale->loadMissing(['customer.seller', 'user', 'details.product', 'returns.details']);
                $footerData = $this->getInvoiceFooterData($sale);

                $seller = new Party([
                    'name'          => $config->business_name,
                    'CC/NIT'           => $config->taxpayer_id,
                    'address'       => $config->address,
                    'city'           => $config->city,
                    'phone'         => $sale->customer->phone,

                    'custom_fields' => [
                        'email'         => $sale->customer->email,
                        'vendedor'        => $sale->customer->seller ? $sale->customer->seller->name : 'N/A',
                        'operador'        => $sale->user->name,
                        'footer_code'    => $footerData['footer_code'],
                        'footer_data'    => $footerData
                    ],
                ]);

                $customer = new Party([
                    'name'          => $sale->customer->name,
                    'custom_fields' => [
                        'CC/NIT'           => $sale->customer->taxpayer_id,
                        'address'       => $sale->customer->address,
                        'city'           => $sale->customer->city,
                        'phone'         => $sale->customer->phone,
                        'email'         => $sale->customer->email,
                    ],
                ]);

                $totalFreight = 0;
                $totalTax = 0;
                $totalBaseAccumulator = 0;
                $isBrokenDown = $sale->is_freight_broken_down;

                $commPercent = $sale->applied_commission_percent ?? 0;
                $diffPercent = $sale->applied_exchange_diff_percent ?? 0;
                $combinedPercent = ($commPercent + $diffPercent) / 100;

                $returnedQuantities = [];
                if (!$originalOnly && $sale->returns) {
                    foreach ($sale->returns as $return) {
                        foreach ($return->details as $retDetail) {
                            $returnedQuantities[$retDetail->sale_detail_id] = ($returnedQuantities[$retDetail->sale_detail_id] ?? 0) + $retDetail->quantity_returned;
                        }
                    }
                }

                foreach ($sale->details as $detail) {
                    $effectiveQty = $detail->quantity - ($returnedQuantities[$detail->id] ?? 0);

                    if ($isBrokenDown) {
                        $totalFreight += $detail->freight_amount;
                        
                        if ($effectiveQty > 0) {
                            $unitPrice = $detail->sale_price;
                            $items[] = InvoiceItem::make($detail->product->name)
                                ->reference($detail->product->sku ? $detail->product->sku : '')
                                ->pricePerUnit($unitPrice)
                                ->quantity($effectiveQty);
                            $totalBaseAccumulator += ($unitPrice * $effectiveQty);
                        }
                    } else {
                        if ($effectiveQty > 0) {
                            $unitPrice = $detail->sale_price;
                            $items[] = InvoiceItem::make($detail->product->name)
                                ->reference($detail->product->sku ? $detail->product->sku : '')
                                ->pricePerUnit($unitPrice)
                                ->quantity($effectiveQty);
                            $totalBaseAccumulator += ($unitPrice * $effectiveQty);
                        }
                    }
                }
                
                $notes = [$sale->notes];
                $notes = implode("<br>", $notes);

                $credit_days = $sale->type == 'credit' ? ($footerData['credit_days'] ?? 0) : 0;

                $currencySymbol = '$';
                $currencyCode = 'USD';
                
                if ($sale->primary_currency_code) {
                    $currencySymbol = \App\Helpers\CurrencyHelper::getSymbol($sale->primary_currency_code);
                    $currencyCode = $sale->primary_currency_code;
                } else {
                    $primary = \App\Helpers\CurrencyHelper::getPrimaryCurrency();
                    if ($primary) {
                        $currencySymbol = $primary->symbol;
                        $currencyCode = $primary->code;
                    }
                }

                $logoPath = $config->logo ? public_path('storage/' . $config->logo) : public_path('logo/logo.jpg');
                if (!file_exists($logoPath)) $logoPath = null;

                $invoice = Invoice::make($config->business_name)->template('invoice-paid-short')
                    ->series('remision_numero')
                    ->status(__('invoices::invoice.paid'))
                    ->sequence($sale->id)
                    ->serialNumberFormat('{SEQUENCE}')
                    ->seller($seller)
                    ->buyer($customer)
                    ->dateFormat('d-M-Y')
                    ->payUntilDays($credit_days)
                    ->currencySymbol($currencySymbol)
                    ->currencyCode($currencyCode)
                    ->currencyDecimals(ConfigurationService::getDecimalPlaces())
                    ->currencyFormat('{SYMBOL}{VALUE}')
                    ->currencyThousandsSeparator('.')
                    ->currencyDecimalPoint(',')
                    ->filename($customFilename)
                    ->addItems($items)
                    ->notes($notes)
                    ->logo($logoPath ?? '');

                if ($totalBaseAccumulator > 0) $invoice->taxableAmount($totalBaseAccumulator);
                if ($isBrokenDown) {
                    if ($totalFreight > 0) $invoice->shipping($totalFreight); 
                    if ($totalTax > 0) $invoice->totalTaxes($totalTax);
                }

                $invoice->save('public');
                return storage_path('app/public/' . $customFilename . '.pdf');
            }
        } catch (\Exception $th) {
            Log::error("Error generating local PAID invoice for Sale ID: {$sale->id}: " . $th->getMessage());
        }
        return null;
    }


    public function generatePdfInvoicePending($sale, $originalOnly = false)
    {
        try {
            Log::info("Generating PENDING invoice logic for Sale ID: {$sale->id}");
            $config = Configuration::first();

            if ($config) {
                $sale->loadMissing(['customer.seller', 'user', 'details.product', 'returns.details']);
                $footerData = $this->getInvoiceFooterData($sale);

                $seller = new Party([
                    'name'          => $config->business_name,
                    'CC/NIT'           => $config->taxpayer_id,
                    'address'       => $config->address,
                    'city'           => $config->city,
                    'phone'         => $sale->customer->phone,

                    'custom_fields' => [
                        'email'         => $sale->customer->email,
                        'vendedor'        => $sale->customer->seller ? $sale->customer->seller->name : 'N/A',
                        'operador'        => $sale->user->name,
                        'footer_code'    => $footerData['footer_code'],
                        'footer_data'    => $footerData
                    ],
                ]);

                $customer = new Party([
                    'name'          => $sale->customer->name,
                    'custom_fields' => [
                        'CC/NIT'           => $sale->customer->taxpayer_id,
                        'address'       => $sale->customer->address,
                        'city'           => $sale->customer->city,
                        'phone'         => $sale->customer->phone,
                        'email'         => $sale->customer->email,
                    ],
                ]);

                $items = [];
                $totalFreight = 0;
                $totalTax = 0;
                $isBrokenDown = $sale->is_freight_broken_down;

                $commPercent = $sale->applied_commission_percent ?? 0;
                $diffPercent = $sale->applied_exchange_diff_percent ?? 0;
                $combinedPercent = ($commPercent + $diffPercent) / 100;

                // Pre-calculate returned quantities
                $returnedQuantities = [];
                if (!$originalOnly && $sale->returns) {
                    foreach ($sale->returns as $return) {
                        foreach ($return->details as $retDetail) {
                            $returnedQuantities[$retDetail->sale_detail_id] = ($returnedQuantities[$retDetail->sale_detail_id] ?? 0) + $retDetail->quantity_returned;
                        }
                    }
                }

                foreach ($sale->details as $detail) {
                    $effectiveQty = $detail->quantity - ($returnedQuantities[$detail->id] ?? 0);
                    
                    if ($isBrokenDown) {
                        $lineFreight = $detail->freight_amount; 
                        $totalFreight += $lineFreight;
                        
                        if ($effectiveQty > 0) {
                            $origLineTotal = $detail->quantity * $detail->sale_price;
                            $origCleanTotal = max(0, $origLineTotal - $lineFreight);
                            $origBaseTotal = $origCleanTotal / (1 + $combinedPercent);
                            $unitPrice = ($detail->quantity > 0) ? ($origBaseTotal / $detail->quantity) : 0;
                            
                            $taxAmountLine = ($unitPrice * $effectiveQty) * $combinedPercent;

                            $item = InvoiceItem::make($detail->product->name)
                                ->reference($detail->product->sku ? $detail->product->sku : '')
                                ->pricePerUnit($unitPrice)
                                ->quantity($effectiveQty);
                            
                            $items[] = $item;
                            $totalTax += $taxAmountLine;
                        }
                    } else {
                        if ($effectiveQty > 0) {
                            $items[] = InvoiceItem::make($detail->product->name)
                                ->reference($detail->product->sku ? $detail->product->sku : '')
                                ->pricePerUnit($detail->sale_price)
                                ->quantity($effectiveQty);
                        }
                    }
                }
                
                // Calculate implicit global freight (Difference between Sale Total and Sum of Line Items)
                // We use calculate totals from items we just built vs Expected Total
                // Actually, let's use the DB values as source of truth for global freight
                // sum(details.quantity * details.sale_price) should equal sale.total IF there is no global freight.
                $sumDetailsTotal = $sale->details->sum(function($d) { return $d->quantity * $d->sale_price; });
                $globalFreight = max(0, $sale->total - $sumDetailsTotal);
                
                if ($globalFreight > 0) {
                     $totalFreight += $globalFreight;
                }

                $notes = [$sale->notes];
                $notes = implode("<br>", $notes);

                $credit_days = $sale->type == 'credit' ? ($footerData['credit_days'] ?? 0) : 0;

                $currencySymbol = '$';
                $currencyCode = 'USD';
                
                if ($sale->primary_currency_code) {
                    $currencySymbol = \App\Helpers\CurrencyHelper::getSymbol($sale->primary_currency_code);
                    $currencyCode = $sale->primary_currency_code;
                } else {
                    $primary = \App\Helpers\CurrencyHelper::getPrimaryCurrency();
                    if ($primary) {
                        $currencySymbol = $primary->symbol;
                        $currencyCode = $primary->code;
                    }
                }

                // Logo Logic with Fallback
                $logoPath = $config->logo ? public_path('storage/' . $config->logo) : public_path('logo/logo.jpg');
                if (!file_exists($logoPath)) {
                    Log::warning("Logo file not found at: $logoPath. Using default.");
                    $logoPath = public_path('logo/logo.jpg');
                    if (!file_exists($logoPath)) {
                        Log::warning("Default logo not found either. PDF will have no logo.");
                        $logoPath = null;
                    }
                }
                
                Log::info("Using Logo Path for Pending: " . ($logoPath ?? 'NONE'));

                $invoice = Invoice::make($config->business_name)->template('invoice-credit-short')
                    ->series('remision_numero')
                    ->status(__('invoices::invoice.credit'))
                    ->sequence($sale->id)
                    ->serialNumberFormat('{SEQUENCE}')
                    ->seller($seller)
                    ->buyer($customer)
                    ->dateFormat('d-M-Y')
                    ->payUntilDays($credit_days)
                    ->currencySymbol($currencySymbol)
                    ->currencyCode($currencyCode)
                    ->currencyDecimals(ConfigurationService::getDecimalPlaces())
                    ->currencyFormat('{SYMBOL}{VALUE}')
                    ->currencyThousandsSeparator('.')
                    ->currencyDecimalPoint(',')
                    ->addItems($items)
                    ->notes($notes)
                    ->logo($logoPath ?? '')
                    ->save('public');

                return $invoice->stream();
            } else {
                 Log::error("Configuration table is empty. Cannot generate PDF.");
                return response()->json(['error' => 'No hay configuración del sistema.'], 500);
            }
         } catch (\Exception $th) {
            Log::error("Error generating PENDING invoice for Sale ID: {$sale->id}: " . $th->getMessage());
            return response()->json(['error' => 'Error generating PDF: ' . $th->getMessage()], 500);
        }
    }

    public function getSavedPdfInvoicePathPending(Sale $sale, $customFilename)
    {
        try {
            $config = Configuration::first();

            if ($config) {
                $sale->loadMissing(['customer.seller', 'user', 'details.product', 'returns.details']);
                $footerData = $this->getInvoiceFooterData($sale);

                $seller = new Party([
                    'name'          => $config->business_name,
                    'CC/NIT'           => $config->taxpayer_id,
                    'address'       => $config->address,
                    'city'           => $config->city,
                    'phone'         => $sale->customer->phone,

                    'custom_fields' => [
                        'email'         => $sale->customer->email,
                        'vendedor'        => $sale->customer->seller ? $sale->customer->seller->name : 'N/A',
                        'operador'        => $sale->user->name,
                        'footer_code'    => $footerData['footer_code'],
                        'footer_data'    => $footerData
                    ],
                ]);

                $customer = new Party([
                    'name'          => $sale->customer->name,
                    'custom_fields' => [
                        'CC/NIT'           => $sale->customer->taxpayer_id,
                        'address'       => $sale->customer->address,
                        'city'           => $sale->customer->city,
                        'phone'         => $sale->customer->phone,
                        'email'         => $sale->customer->email,
                    ],
                ]);

                $items = [];
                $totalFreight = 0;
                $totalTax = 0;
                $isBrokenDown = $sale->is_freight_broken_down;

                $commPercent = $sale->applied_commission_percent ?? 0;
                $diffPercent = $sale->applied_exchange_diff_percent ?? 0;
                $combinedPercent = ($commPercent + $diffPercent) / 100;

                $returnedQuantities = [];
                if (!$originalOnly && $sale->returns) {
                    foreach ($sale->returns as $return) {
                        foreach ($return->details as $retDetail) {
                            $returnedQuantities[$retDetail->sale_detail_id] = ($returnedQuantities[$retDetail->sale_detail_id] ?? 0) + $retDetail->quantity_returned;
                        }
                    }
                }

                foreach ($sale->details as $detail) {
                    $effectiveQty = $detail->quantity - ($returnedQuantities[$detail->id] ?? 0);

                    if ($isBrokenDown) {
                        $lineFreight = $detail->freight_amount; 
                        $totalFreight += $lineFreight;
                        
                        if ($effectiveQty > 0) {
                            $origLineTotal = $detail->quantity * $detail->sale_price;
                            $origCleanTotal = max(0, $origLineTotal - $lineFreight);
                            $origBaseTotal = $origCleanTotal / (1 + $combinedPercent);
                            $unitPrice = ($detail->quantity > 0) ? ($origBaseTotal / $detail->quantity) : 0;
                            
                            $taxAmountLine = ($unitPrice * $effectiveQty) * $combinedPercent;

                            $item = InvoiceItem::make($detail->product->name)
                                ->reference($detail->product->sku ? $detail->product->sku : '')
                                ->pricePerUnit($unitPrice)
                                ->quantity($effectiveQty);
                            
                            $items[] = $item;
                            $totalTax += $taxAmountLine;
                        }
                    } else {
                        if ($effectiveQty > 0) {
                            $items[] = InvoiceItem::make($detail->product->name)
                                ->reference($detail->product->sku ? $detail->product->sku : '')
                                ->pricePerUnit($detail->sale_price)
                                ->quantity($effectiveQty);
                        }
                    }
                }
                
                $sumDetailsTotal = $sale->details->sum(function($d) { return $d->quantity * $d->sale_price; });
                $globalFreight = max(0, $sale->total - $sumDetailsTotal);
                
                if ($globalFreight > 0) {
                     $totalFreight += $globalFreight;
                }

                $notes = [$sale->notes];
                $notes = implode("<br>", $notes);

                $credit_days = $sale->type == 'credit' ? ($footerData['credit_days'] ?? 0) : 0;

                $currencySymbol = '$';
                $currencyCode = 'USD';
                
                if ($sale->primary_currency_code) {
                    $currencySymbol = \App\Helpers\CurrencyHelper::getSymbol($sale->primary_currency_code);
                    $currencyCode = $sale->primary_currency_code;
                } else {
                    $primary = \App\Helpers\CurrencyHelper::getPrimaryCurrency();
                    if ($primary) {
                        $currencySymbol = $primary->symbol;
                        $currencyCode = $primary->code;
                    }
                }

                $logoPath = $config->logo ? public_path('storage/' . $config->logo) : public_path('logo/logo.jpg');
                if (!file_exists($logoPath)) $logoPath = null;

                $invoice = Invoice::make($config->business_name)->template('invoice-credit-short')
                    ->series('remision_numero')
                    ->status(__('invoices::invoice.credit'))
                    ->sequence($sale->id)
                    ->serialNumberFormat('{SEQUENCE}')
                    ->seller($seller)
                    ->buyer($customer)
                    ->dateFormat('d-M-Y')
                    ->payUntilDays($credit_days)
                    ->currencySymbol($currencySymbol)
                    ->currencyCode($currencyCode)
                    ->currencyDecimals(ConfigurationService::getDecimalPlaces())
                    ->currencyFormat('{SYMBOL}{VALUE}')
                    ->currencyThousandsSeparator('.')
                    ->currencyDecimalPoint(',')
                    ->filename($customFilename)
                    ->addItems($items)
                    ->notes($notes)
                    ->logo($logoPath ?? '')
                    ->save('public');

                return storage_path('app/public/' . $customFilename . '.pdf');
            }
        } catch (\Exception $th) {
            Log::error("Error generating local PENDING invoice for Sale ID: {$sale->id}: " . $th->getMessage());
        }
        return null;
    }




    // function printSale($saleId)
    // {

    //     try {

    //         $config = Configuration::first();

    //         if ($config) {

    //             $sale = Sale::with(['customer', 'user', 'details', 'details.product'])->find($saleId);

    //             $connector = new WindowsPrintConnector($config->printer_name);
    //             $printer = new Printer($connector);

    //             $printer->setJustification(Printer::JUSTIFY_CENTER);
    //             $printer->setTextSize(2, 2);

    //             $printer->text(strtoupper($config->business_name) . "\n");
    //             $printer->setTextSize(1, 1);
    //             $printer->text("$config->address \n");
    //             $printer->text("NIT: $config->taxpayer_id \n");
    //             $printer->text("TEL: $config->phone \n\n");

    //             $printer->setJustification(Printer::JUSTIFY_LEFT);
    //             //$printer->text("=============================================\n");
    //             $printer->text("Folio: " . $sale->id . "\n");
    //             $printer->text("Fecha: " . Carbon::parse($sale->created_at)->format('d/m/Y h:m:s') . "\n");
    //             $printer->text("Cajero: " . $sale->user->name . " \n");
    //             //$printer->text("=============================================\n");



    //             $maskHead = "%-30s %-5s %-8s";
    //             $maskRow = $maskHead; //"%-.31s %-4s %-5s";

    //             $headersName = sprintf($maskHead, 'DESCRIPCION', 'CANT', 'PRECIO');
    //             $printer->text("=============================================\n");
    //             $printer->text($headersName . "\n");
    //             $printer->text("=============================================\n");

    //             foreach ($sale->details as $item) {

    //                 $descripcion_1 = $this->cortar($item->product->name, 30);
    //                 $row_1 = sprintf($maskRow, $descripcion_1[0], $item->quantity, '$' . number_format($item->sale_price, 2));
    //                 $printer->text($row_1 . "\n");

    //                 if (isset($descripcion_1[1])) {
    //                     $row_2 = sprintf($maskRow, $descripcion_1[1], '', '', '');
    //                     $printer->text($row_2 . "\n");
    //                 }
    //             }

    //             $printer->text("=============================================" . "\n");

    //             $printer->text("CLIENTE: " . $sale->customer->name  . "\n\n");


    //             $printer->setJustification(Printer::JUSTIFY_CENTER);
    //             $printer->text("NO. DE ARTICULOS $sale->items" . "\n");

    //             $printer->setJustification(Printer::JUSTIFY_LEFT);

    //             $desglose = $this->desgloseMonto($sale->total);
    //             $printer->text("SUBTOTAL....... $" . number_format($desglose['subtotal'], 2) . "\n");
    //             $printer->text("IVA............ $" . number_format($desglose['iva'], 2) . "\n");
    //             $printer->text("TOTAL.......... $" . number_format($sale->total, 2) . "\n");

    //             if ($sale->type == 'cash') {
    //                 $printer->text("EFECTIVO....... $" . number_format($sale->cash, 2) . "\n");
    //                 if (floatval($sale->change) > 0)  $printer->text("\nCAMBIO......... $" . number_format($sale->change, 2) . "\n");
    //             } else {
    //                 $printer->text($sale->type == 'credit' ? "FORMA DE PAGO: CRÉDITO" :  "FORMA DE PAGO:  DEPÓSITO" .  "\n");
    //             }

    //             $printer->feed(3);
    //             $printer->setJustification(Printer::JUSTIFY_CENTER);
    //             $printer->text("$config->leyend\n");
    //             $printer->text("$config->website\n");
    //             $printer->feed(3);
    //             $printer->cut();
    //             $printer->close();
    //         } else {
    //             Log::info("La tabla configurations está vacía, no es posible imprimir la venta");
    //         }
    //         //
    //     } catch (\Exception $th) {
    //         Log::info("Error al intentar imprimir el comprobante de venta \n {$th->getMessage()}");
    //     }
    // }

    // recibo de pago / abono
    // public  function printPayment($payId)
    // {
    //     try {
    //         $config = Configuration::first();

    //         if ($config) {
    //             $connector = new WindowsPrintConnector($config->printer_name);
    //             $printer = new Printer($connector);
    //             $printer->setJustification(Printer::JUSTIFY_CENTER);
    //             $printer->setTextSize(2, 2);

    //             $printer->text(strtoupper($config->business_name) . "\n");

    //             $printer->setTextSize(1, 1);
    //             $printer->text("$config->address \n");
    //             $printer->text("NIT: $config->taxpayer_id \n");
    //             $printer->text("TEL: $config->phone \n\n");

    //             $printer->setJustification(Printer::JUSTIFY_CENTER);
    //             $printer->text("==  Comprobante de Pago ==" . "\n\n");

    //             $printer->setJustification(Printer::JUSTIFY_LEFT);

    //             $payment = Payment::with('sale')->where('id', $payId)->first();

    //             $printer->text("Folio:" . $payment->id . "\n");
    //             $printer->text("Fecha:" . Carbon::parse($payment->created_at)->format('d-m-Y H:i') . "\n");
    //             $printer->text("Cliente:" . $payment->sale->customer->name . "\n");
    //             $printer->text("=============================================" . "\n");
    //             $printer->text("Compra: $" . $payment->sale->total . "\n");
    //             $printer->text("Abono: $" . $payment->amount . "\n");

    //             if ($payment->sale->debt <= 0) {
    //                 $printer->text("CRÉDITO LIQUIDADO \n");
    //             } else {
    //                 $printer->text("Deuda actual: $" . $payment->sale->debt . "\n\n");
    //             }

    //             $printer->text("Forma de Pago:" . ($payment->pay_way == 'cash' ? 'EFECTIVO' : 'DEPÓSITO')  . "\n");

    //             if ($payment->pay_way == 'deposit') {
    //                 $printer->text($payment->bank . "\n");
    //                 $printer->text("No. Cuenta:" . $payment->account_number . "\n");
    //                 $printer->text("No. Depósito:" . $payment->deposit_number . "\n");
    //             }



    //             $printer->text("=============================================" . "\n");
    //             $printer->text("Atiende:" . $payment->sale->user->name . "\n");


    //             $printer->feed(3);
    //             $printer->cut();
    //             $printer->close();
    //         } else {
    //             Log::info("La tabla configurations está vacía, no es posible imprimir el comprobante de pago");
    //         }
    //         //
    //     } catch (\Exception $th) {
    //         Log::info("Error al intentar imprimir el comprobante de pago \n {$th->getMessage()}");
    //     }
    // }



    // Definir una función para cortar una cadena si es más larga que un límite y devolver un arreglo
    // function cortar($cadena, $limite)
    // {
    //     // Crear un arreglo vacío
    //     $resultado = array();
    //     // Si la cadena es más corta o igual que el límite, se agrega al arreglo sin modificar
    //     if (strlen($cadena) <= $limite) {
    //         $resultado[] = $cadena;
    //     }
    //     // Si la cadena es más larga que el límite, se busca el último espacio dentro del límite
    //     else {
    //         $ultimo_espacio = strrpos(substr($cadena, 0, $limite), ' ');
    //         // Se agrega al arreglo la primera parte de la cadena hasta el último espacio
    //         $resultado[] = substr($cadena, 0, $ultimo_espacio);
    //         // Se agrega al arreglo la segunda parte de la cadena desde el último espacio más uno
    //         $resultado[] = substr($cadena, $ultimo_espacio + 1);
    //     }
    //     // Se devuelve el arreglo
    //     return $resultado;
    // }




    // function printCashCount($user_name, $dfrom, $dto, $totales, $payments, $credit)
    // {
    //     try {

    //         $config = Configuration::first();

    //         if ($config) {
    //             $connector = new WindowsPrintConnector($config->printer_name);
    //             $printer = new Printer($connector);

    //             $printer->setJustification(Printer::JUSTIFY_CENTER);
    //             $printer->setTextSize(2, 2);

    //             $printer->text(strtoupper($config->business_name) . "\n");
    //             $printer->setTextSize(1, 1);
    //             $printer->text("Corte de Caja $config->taxpayer_id \n\n");


    //             $printer->setJustification(Printer::JUSTIFY_LEFT);

    //             $printer->text("=============================================\n");
    //             $printer->text("Fechas: desde" . $dfrom . ' hasta ' . $dto . "\n");
    //             $printer->text("Usuario: " . $user_name . " \n");
    //             $printer->text("=============================================\n");

    //             $printer->text("VENTAS TOTALES: " . $totales  . "\n");
    //             $printer->text("VENTAS A CRÉDITO: " . $credit  . "\n");
    //             $printer->text("PAGOS REGISTRADOS: " . $payments  . "\n");

    //             $printer->text("---------" . "\n");


    //             $printer->feed(3);
    //             $printer->setJustification(Printer::JUSTIFY_CENTER);
    //             $printer->cut();
    //             $printer->close();
    //         } else {
    //             Log::info("La tabla configurations está vacía, no es posible imprimir el corte de caja");
    //         }
    //         //
    //     } catch (\Exception $th) {
    //         Log::info("Error al intentar imprimir el corte de caja \n {$th->getMessage()} ");
    //     }
    // }
    public function generatePdfInternalInvoice(Sale $sale, $originalOnly = false)
    {
        try {
            Log::info("Generating INTERNAL PDF invoice for Sale ID: {$sale->id}" . ($originalOnly ? ' (Original)' : ' (Actualizado)'));
            
            $config = Configuration::first();
            if (!$config) {
                return response()->json(['error' => 'No hay configuración del sistema.'], 500);
            }

            // Calculate percentages
            $commPercent = $sale->applied_commission_percent ?? 0;
            $diffPercent = $sale->applied_exchange_diff_percent ?? 0;
            $freightPercent = $sale->applied_freight_percent ?? 0;
            
            $combinedPercent = ($commPercent + $diffPercent) / 100;
            
            $items = [];
            $totalBase = 0;
            
            $currencySymbol = '$';
            // Logic for Currency Symbol
            if ($sale->primary_currency_code) {
                $currencySymbol = \App\Helpers\CurrencyHelper::getSymbol($sale->primary_currency_code);
            } else {
                $primary = \App\Helpers\CurrencyHelper::getPrimaryCurrency();
                if ($primary) {
                    $currencySymbol = $primary->symbol;
                }
            }

            // Calculations based on Original Values to determine Freight ratios
            $rawItemsTotalOriginal = $sale->details->sum(function($d) { return $d->quantity * $d->sale_price; });
            $isAdditive = ($sale->total - $rawItemsTotalOriginal) > 0.01;
            
            // Calculate Implicit Freight (Difference between Sale Original Total and Sum of Line Items)
            $implicitFreightOriginal = max(0, $sale->total - $rawItemsTotalOriginal);
            $datasetFreightSumOriginal = $sale->details->sum('freight_amount');
            
            $trueGlobalFreightOriginal = max(0, $implicitFreightOriginal - $datasetFreightSumOriginal);
            
            $totalFreightAmount = 0;
            $configFreightTotal = 0;
            $productFreightTotal = 0;
            
            // Build a map of total quantity returned per sale_detail_id
            $returnedQtyMap = [];
            if (!$originalOnly) {
                $returnedDetails = \App\Models\SaleReturnDetail::whereIn(
                    'sale_detail_id',
                    $sale->details->pluck('id')
                )->get();
                foreach ($returnedDetails as $rd) {
                    $returnedQtyMap[$rd->sale_detail_id] = ($returnedQtyMap[$rd->sale_detail_id] ?? 0) + $rd->quantity_returned;
                }
            }

            // Calculate global freight ratio based on total effective vs original quantities
            $totalOriginalQty = $sale->details->sum('quantity');
            $totalEffectiveQty = $sale->details->sum(function($d) use ($originalOnly, $returnedQtyMap) {
                $returned = $returnedQtyMap[$d->id] ?? 0;
                return $originalOnly ? $d->quantity : max(0, $d->quantity - $returned);
            });
            $globalFreightRatio = $totalOriginalQty > 0 ? ($totalEffectiveQty / $totalOriginalQty) : 1;
            
            $trueGlobalFreightEffective = $trueGlobalFreightOriginal * $globalFreightRatio;

            foreach ($sale->details as $detail) {
                $returned = $returnedQtyMap[$detail->id] ?? 0;
                $qty = $originalOnly ? $detail->quantity : max(0, $detail->quantity - $returned);
                
                if ($qty <= 0) continue; // Skip returned items completely
                
                $ratio = ($detail->quantity > 0) ? ($qty / $detail->quantity) : 1;
                
                $finalUnitSalePrice = $detail->sale_price;
                $lineFreight = $detail->freight_amount * $ratio; // Scaled freight
                $finalImporte = $finalUnitSalePrice * $qty;
                
                // Add to specific freight buckets
                $totalFreightAmount += $lineFreight;
                if (in_array($detail->product->freight_type, ['global', 'none'])) {
                    $configFreightTotal += $lineFreight;
                } else {
                    $productFreightTotal += $lineFreight;
                }
                
                // 1. Calculate Base Total (Importe Base)
                if ($isAdditive) {
                    $cleanTotal = $finalImporte; 
                    $itemTotalBase = $cleanTotal;
                } else {
                    $cleanTotal = max(0, $finalImporte - $lineFreight);
                    $itemTotalBase = $cleanTotal / (1 + $combinedPercent);
                }
                
                // 2. Calculate Base Unit Price
                $baseUnit = ($qty > 0) ? ($itemTotalBase / $qty) : 0;

                $totalBase += $itemTotalBase;

                $items[] = [
                    'quantity' => number_format($qty, 2),
                    'name' => $detail->product->name,
                    'base_price' => $baseUnit, // Base Unit
                    'total_base' => $itemTotalBase // Base Total
                ];
            }
            
            // Add Global freight back in
            if ($trueGlobalFreightEffective > 0) {
                 $productFreightTotal += $trueGlobalFreightEffective;
                 $totalFreightAmount += $trueGlobalFreightEffective;
            }

            // Recalculate amounts based on Total Base
            $commAmount = $totalBase * ($commPercent / 100);
            $diffAmount = $totalBase * ($diffPercent / 100);
            
            // Computed total = subtotal + all surcharges
            $computedTotal = $totalBase + $commAmount + $diffAmount + $totalFreightAmount;
            
            $data = [
                'company' => $config,
                'sale' => $sale,
                'items' => $items,
                'subtotalBase' => $totalBase,
                'currencySymbol' => $currencySymbol,
                'commPercent' => $commPercent,
                'commAmount' => $commAmount,
                'diffPercent' => $diffPercent,
                'diffAmount' => $diffAmount,
                // Freight details
                'freightPercent' => $freightPercent, // For display in Config line
                'configFreightTotal' => $configFreightTotal,
                'productFreightTotal' => $productFreightTotal,
                'totalFreightAmount' => $totalFreightAmount,
                'computedTotal' => $computedTotal
            ];

            $pdf = Pdf::loadView('pdf.internal-invoice', $data);
            return $pdf->stream('comprobante_interno_' . $sale->id . '.pdf');

        } catch (\Exception $th) {
            Log::error("Error generating INTERNAL PDF for Sale ID: {$sale->id}: " . $th->getMessage());
            return response()->json(['error' => 'Error generating PDF: ' . $th->getMessage()], 500);
        }
    }

    private function getInvoiceFooterData(Sale $sale)
    {
        // Resolve Values
        // Customer & Seller Config
        $customer = $sale->customer;
        $customerConfig = $customer ? $customer->latestCustomerConfig : null;
        
        $seller = $sale->user; // The user who made the sale
        $sellerConfig = $seller ? $seller->latestSellerConfig : null;

        // Freight
        if ($customerConfig && $customerConfig->freight_percent > 0) {
            $freightPercent = floatval($customerConfig->freight_percent);
        } else {
            $freightPercent = $sellerConfig ? floatval($sellerConfig->freight_percent) : 0;
        }

        // Commission
        if (isset($sale->applied_commission_percent)) {
            $commPercent = floatval($sale->applied_commission_percent);
        } elseif ($customerConfig && $customerConfig->commission_percent > 0) {
            $commPercent = floatval($customerConfig->commission_percent);
        } else {
            $commPercent = $sellerConfig ? floatval($sellerConfig->commission_percent) : 0;
        }

        // Diff
        if (isset($sale->applied_exchange_diff_percent)) {
            $diffPercent = floatval($sale->applied_exchange_diff_percent);
        } elseif ($customerConfig && $customerConfig->exchange_diff_percent > 0) {
            $diffPercent = floatval($customerConfig->exchange_diff_percent);
        } else {
            $diffPercent = $sellerConfig ? floatval($sellerConfig->exchange_diff_percent) : 0;
        }

        // USD Discount
        $creditConfig = CreditConfigService::getCreditConfig($customer, $seller);
        $usdDiscount = $creditConfig['usd_payment_discount'];

        // ES Code (Estimated/Base Sale Price)
        // Use the regular_price from details which represents the pure base price before any commercial configs
        $totalBasePrice = 0;

        foreach ($sale->details as $detail) {
            $totalBasePrice += ($detail->regular_price * $detail->quantity);
        }

        $saleBaseTotal = number_format($totalBasePrice, 2, '.', '');
        $estimatedPriceBase = 'ES' . str_replace('.', 'C', $saleBaseTotal);

        // Pronto Pago Rules
        $discountRules = $creditConfig['discount_rules'];

        // Mora
        $moraPercent = 0; // Default 0 as requested if not configured

        // Credit Days
        $creditDays = $sale->type == 'credit' ? ($creditConfig['credit_days'] ?? 0) : 0;

        // Operator
        $operator = \Illuminate\Support\Facades\Auth::user(); 
        
        $code = FooterCodeService::generate(
            $seller ? $seller->name : '',
            $customer ? $customer->name : '',
            $freightPercent,
            $commPercent,
            $diffPercent,
            'FC' . $sale->id, // Invoice Placeholder
            $estimatedPriceBase, // Estimated
            $usdDiscount,
            $discountRules,
            $moraPercent,
            intval($creditDays),
            $operator ? $operator->name : ''
        );

        return [
            'footer_code' => $code,
            'usd_discount' => $usdDiscount,
            'discount_rules' => $discountRules,
            'credit_days' => $creditDays,
            'mora_percent' => $moraPercent
        ];
    }

    public function generateCreditNotePdf(\App\Models\SaleReturn $saleReturn)
    {
        try {
            Log::info("Generating Credit Note PDF for Return ID: {$saleReturn->id}");
            
            $config = Configuration::first();
            if (!$config) {
                return response()->json(['error' => 'No hay configuración del sistema.'], 500);
            }

            $saleReturn->loadMissing(['sale.customer', 'sale.user', 'details.product']);
            $sale = $saleReturn->sale;

            $seller = new Party([
                'name'          => $config->business_name,
                'CC/NIT'           => $config->taxpayer_id,
                'address'       => $config->address,
                'city'           => $config->city,
                'phone'         => $sale->customer->phone,

                'custom_fields' => [
                    'email'         => $sale->customer->email,
                    'operador'        => $sale->user->name,
                ],
            ]);

            $customer = new Party([
                'name'          => $sale->customer->name,
                'custom_fields' => [
                    'CC/NIT'           => $sale->customer->taxpayer_id,
                    'address'       => $sale->customer->address,
                    'city'           => $sale->customer->city,
                    'phone'         => $sale->customer->phone,
                    'email'         => $sale->customer->email,
                ],
            ]);

            $items = [];
            foreach ($saleReturn->details as $detail) {
                if ($detail->quantity_returned > 0) {
                    $items[] = InvoiceItem::make($detail->product->name)
                        ->reference($detail->product->sku ? $detail->product->sku : '')
                        ->pricePerUnit($detail->unit_price)
                        ->quantity($detail->quantity_returned);
                }
            }

            // Fallback for Manual Credit Note (Adjustment)
            if (empty($items)) {
                $items[] = InvoiceItem::make('AJUSTE DE SALDO / NOTA DE CRÉDITO')
                    ->reference('ADJUST')
                    ->pricePerUnit($saleReturn->total_returned)
                    ->quantity(1);
            }

            $refundMethodTranslated = [
                'cash' => 'Efectivo',
                'bank' => 'Banco/Transferencia',
                'wallet' => 'Saldo a Favor',
                'debt_reduction' => 'Reducción de Deuda'
            ][$saleReturn->refund_method] ?? $saleReturn->refund_method;

            $notes = [
                "<strong>Motivo:</strong> " . ($saleReturn->reason ?? 'Devolución de mercancía'),
                "<strong>Método de Reembolso:</strong> " . $refundMethodTranslated,
                "<strong>Factura Original:</strong> #" . $sale->id
            ];
            $notes = implode("<br>", $notes);

            $currencySymbol = '$';
            $currencyCode = 'USD';
            if ($sale->primary_currency_code) {
                $currencySymbol = \App\Helpers\CurrencyHelper::getSymbol($sale->primary_currency_code);
                $currencyCode = $sale->primary_currency_code;
            } else {
                $primary = \App\Helpers\CurrencyHelper::getPrimaryCurrency();
                if ($primary) {
                    $currencySymbol = $primary->symbol;
                    $currencyCode = $primary->code;
                }
            }

            $logoPath = $config->logo ? public_path('storage/' . $config->logo) : public_path('logo/logo.jpg');
            if (!file_exists($logoPath)) $logoPath = null;

            $invoice = Invoice::make($config->business_name)->template('invoice-credit-short')
                ->name('Nota de Crédito')
                ->series('NC')
                ->sequence($saleReturn->id)
                ->serialNumberFormat('{SEQUENCE}')
                ->seller($seller)
                ->buyer($customer)
                ->dateFormat('d-M-Y H:i:s')
                ->date($saleReturn->created_at)
                ->currencySymbol($currencySymbol)
                ->currencyCode($currencyCode)
                ->currencyDecimals(ConfigurationService::getDecimalPlaces())
                ->currencyFormat('{SYMBOL}{VALUE}')
                ->currencyThousandsSeparator('.')
                ->currencyDecimalPoint(',')
                ->addItems($items)
                ->notes($notes)
                ->logo($logoPath ?? '')
                ->save('public');

            return $invoice->stream();

        } catch (\Exception $th) {
            Log::error("Error generating Credit Note PDF for Return ID: {$saleReturn->id}: " . $th->getMessage());
            return response()->json(['error' => 'Error generating Credit Note PDF: ' . $th->getMessage()], 500);
        }
    }
}
