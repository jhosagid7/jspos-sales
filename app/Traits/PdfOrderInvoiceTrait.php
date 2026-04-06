<?php

namespace App\Traits;

use Carbon\Carbon;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Configuration;
use Illuminate\Support\Facades\Log;
use App\Services\CreditConfigService;
use App\Services\FooterCodeService;
use App\Services\ConfigurationService;


use Jhosagid\Invoices\Invoice;
use Jhosagid\Invoices\Classes\Buyer;
use Jhosagid\Invoices\Classes\Party;
use Jhosagid\Invoices\Classes\Seller;
use Jhosagid\Invoices\Classes\InvoiceItem;


trait PdfOrderInvoiceTrait
{

    public function generatePdfOrderInvoice(Order $order)
    {
        try {
            // dd($order);
            $config = Configuration::first();

            if ($config) {
                if ($order->status == 'processed') {
                    return $this->generatePdfOrderInvoiceProcessed($order);
                }
                if ($order->status == 'pending') {
                    // dd($order);
                    return $this->generatePdfOrderInvoicePending($order);
                }
            } else {
                Log::info("La tabla configurations está vacía, no es posible imprimir la ordern");
            }
        } catch (\Exception $th) {
            Log::info("Error al intentar imprimir la remisión de orden \n {$th->getMessage()}");
        }
    }

    public function generatePdfOrderInvoiceProcessed($order)
    {
        try {
            $config = Configuration::first();

            if ($config) {
                $order->loadMissing(['customer.seller.banks', 'user', 'details.product', 'details.order']);
                $footerData = $this->getOrderInvoiceFooterData($order);

                $seller = new Party([
                    'name'          => $config->business_name,
                    'CC/NIT'           => $config->taxpayer_id,
                    'address'       => $config->address,
                    'city'           => $config->city,
                    'phone'         => $config->phone,

                    'custom_fields' => [
                        'email'         => $order->customer->email,
                        'vendedor'      => $order->customer->seller ? $order->customer->seller->name : 'N/A',
                        'vendedor_banks' => $order->customer->seller ? $order->customer->seller->banks : collect(),
                        'operador'      => $order->user->name,
                        'footer_code'   => $footerData['footer_code'],
                        'footer_data'   => $footerData,
                        'cloning_qr'    => \DNS2D::getBarcodeHTML("ORD:{$order->id}", "QRCODE", 2, 2) 
                    ],
                ]);

                $customer = new Party([
                    'name'          => $order->customer->name,


                    'custom_fields' => [
                        'CC/NIT'           => $order->customer->taxpayer_id,
                        'address'       => $order->customer->address,
                        'city'           => $order->customer->city,
                        'phone'         => $order->customer->phone,
                        'email'         => $order->customer->email,
                    ],
                ]);

                foreach ($order->details as $detail) {

                    $items[] = InvoiceItem::make($detail->product->name)->reference($detail->product->sku ? $detail->product->sku : '')->pricePerUnit($detail->sale_price)->quantity($detail->quantity);
                }

                $notes = [
                    $order->notes
                ];
                $notes = implode("<br>", $notes);

                $credit_days = $order->type == 'credit' ? ($footerData['credit_days'] ?? 0) : 0;

                $invoice = Invoice::make($config->business_name)->template('invoice-order-processed')
                    ->series('orden-de-compra-numero')
                    // ability to include translated invoice status
                    // in case it was paid
                    ->status(__('invoices::invoice.order_processed'))
                    ->sequence($order->id)
                    ->serialNumberFormat($order->order_number)
                    ->seller($seller)
                    ->buyer($customer)
                    // ->date(now()->subWeeks(3))
                    ->dateFormat('d-M-Y')
                    ->payUntilDays($credit_days)
                    ->currencySymbol('$')
                    ->currencyCode('Peso(s)')
                    ->currencyDecimals(ConfigurationService::getDecimalPlaces())
                    ->currencyFormat('{SYMBOL}{VALUE}')
                    ->currencyThousandsSeparator('.')
                    ->currencyDecimalPoint(',')
                    // ->filename($seller->name . ' ' . $customer->name)
                    ->addItems($items)
                    ->notes($notes)
                    ->logo($config->logo && file_exists(public_path('storage/' . $config->logo)) ? public_path('storage/' . $config->logo) : public_path('logo/logo.jpg'))
                    // You can additionally save generated invoice to configured disk
                    ->save('public');

                $link = $invoice->url();
                // Then send email to party with link

                // And return invoice itself to browser or have a different view
                return $invoice->stream();
            } else {
                Log::info("La tabla configurations está vacía, no es posible imprimir la venta");
            }
        } catch (\Exception $th) {
            Log::info("Error al intentar imprimir la remisión de venta \n {$th->getMessage()}");
        }
    }

    public function generatePdfOrderInvoicePending($order)
    {

        try {
            $config = Configuration::first();

            if ($config) {
                $order->loadMissing(['customer.seller.banks', 'user', 'details.product', 'details.order']);

                $footerData = $this->getOrderInvoiceFooterData($order);

                $seller = new Party([
                    'name'          => $config->business_name,
                    'vat'           => $config->taxpayer_id,
                    'address'       => $config->address,
                    'city'           => 'Bogota',
                    'phone'         => $order->customer->phone,

                    'custom_fields' => [
                        'email'         => $order->customer->email,
                        'vendedor'      => $order->customer->seller ? $order->customer->seller->name : 'N/A',
                        'vendedor_banks' => $order->customer->seller ? $order->customer->seller->banks : collect(),
                        'operador'      => $order->user->name,
                        'footer_code'   => $footerData['footer_code'],
                        'footer_data'   => $footerData,
                        'cloning_qr'    => \DNS2D::getBarcodeHTML("ORD:{$order->id}", "QRCODE", 2, 2) 
                    ],
                ]);

                $customer = new Party([
                    'name'          => $order->customer->name,


                    'custom_fields' => [
                        'CC/NIT'           => $order->customer->taxpayer_id,
                        'address'       => $order->customer->address,
                        'city'           => $order->customer->city,
                        'phone'         => $order->customer->phone,
                        'email'         => $order->customer->email,
                    ],
                ]);

                foreach ($order->details as $detail) {

                    $items[] = InvoiceItem::make($detail->product->name)->reference($detail->product->sku ? $detail->product->sku : '')->pricePerUnit($detail->sale_price)->quantity($detail->quantity);
                }

                $notes = [
                    $order->notes,
                ];
                $notes = implode("<br>", $notes);

                $credit_days = $order->type == 'credit' ? ($footerData['credit_days'] ?? 0) : 0;

                $invoice = Invoice::make($config->business_name)->template('invoice-order-pending')
                    ->series('orden-de-compra-numero')
                    // ability to include translated invoice status
                    // in case it was paid
                    ->status(__('invoices::invoice.order_pending'))
                    ->sequence($order->id)
                    ->serialNumberFormat($order->order_number)
                    ->seller($seller)
                    ->buyer($customer)
                    // ->date(now()->subWeeks(3))
                    ->dateFormat('d-M-Y')
                    ->payUntilDays($credit_days)
                    ->currencySymbol('$')
                    ->currencyCode('Peso(s)')
                    ->currencyDecimals(ConfigurationService::getDecimalPlaces())
                    ->currencyFormat('{SYMBOL}{VALUE}')
                    ->currencyThousandsSeparator('.')
                    ->currencyDecimalPoint(',')
                    // ->filename($seller->name . ' ' . $customer->name)
                    ->addItems($items)
                    ->notes($notes)
                    ->logo($config->logo && file_exists(public_path('storage/' . $config->logo)) ? public_path('storage/' . $config->logo) : public_path('logo/logo.jpg'))
                    // You can additionally save generated invoice to configured disk
                    ->save('public');

                $link = $invoice->url();
                // Then send email to party with link

                // And return invoice itself to browser or have a different view
                return $invoice->stream();
            } else {
                Log::info("La tabla configurations está vacía, no es posible imprimir la orden");
            }
        } catch (\Exception $th) {
            Log::info("Error al intentar imprimir la remisión de orden \n {$th->getMessage()}");
        }
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

    private function getOrderInvoiceFooterData(Order $order)
    {
        // Resolve Values
        // Customer & Seller Config
        $customer = $order->customer;
        $customerConfig = $customer ? $customer->latestCustomerConfig : null;
        
        $seller = $order->user; // The user who made the order
        $sellerConfig = $seller ? $seller->latestSellerConfig : null;

        // Freight
        if ($customerConfig && $customerConfig->freight_percent > 0) {
            $freightPercent = floatval($customerConfig->freight_percent);
        } else {
            $freightPercent = $sellerConfig ? floatval($sellerConfig->freight_percent) : 0;
        }

        // Commission
        if (isset($order->applied_commission_percent)) {
            $commPercent = floatval($order->applied_commission_percent);
        } elseif ($customerConfig && $customerConfig->commission_percent > 0) {
            $commPercent = floatval($customerConfig->commission_percent);
        } else {
            $commPercent = $sellerConfig ? floatval($sellerConfig->commission_percent) : 0;
        }

        // Diff
        if (isset($order->applied_exchange_diff_percent)) {
            $diffPercent = floatval($order->applied_exchange_diff_percent);
        } elseif ($customerConfig && $customerConfig->exchange_diff_percent > 0) {
            $diffPercent = floatval($customerConfig->exchange_diff_percent);
        } else {
            $diffPercent = $sellerConfig ? floatval($sellerConfig->exchange_diff_percent) : 0;
        }

        // USD Discount
        $creditConfig = CreditConfigService::getCreditConfig($customer, $seller);
        $usdDiscount = $creditConfig['usd_payment_discount'];

        // ES Code (Estimated/Base Order Price)
        $totalBasePrice = 0;

        foreach ($order->details as $detail) {
            $totalBasePrice += ($detail->regular_price * $detail->quantity);
        }

        $orderBaseTotal = number_format($totalBasePrice, 2, '.', '');
        $estimatedPriceBase = 'ES' . str_replace('.', 'C', $orderBaseTotal);

        // Pronto Pago Rules
        $discountRules = $creditConfig['discount_rules'];

        // Mora
        $moraPercent = 0; // Default 0 as requested if not configured

        // Credit Days
        $creditDays = $order->type == 'credit' ? ($creditConfig['credit_days'] ?? 0) : 0;

        // Operator
        $operator = \Illuminate\Support\Facades\Auth::user(); 
        
        $code = FooterCodeService::generate(
            $seller ? $seller->name : '',
            $customer ? $customer->name : '',
            $freightPercent,
            $commPercent,
            $diffPercent,
            'OC' . $order->id, // Order Placeholder
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
}
