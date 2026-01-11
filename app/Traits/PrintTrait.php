<?php

namespace App\Traits;

use Carbon\Carbon;
use App\Models\Sale;
use App\Models\Order;
use App\Models\Payable;
use App\Models\Payment;
use Mike42\Escpos\Printer;
use App\Models\Configuration;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

trait PrintTrait
{
    use UtilTrait;

    function printSale($saleId)
    {

        try {

            $config = Configuration::first();

            if ($config) {

                $sale = Sale::with(['customer', 'user', 'details', 'details.product'])->find($saleId);

                // Determine printer name: User assigned or Global default
                $printerName = Auth::user()->printer_name ?? $config->printer_name;
                
                // If user has empty string as printer name, fallback to config
                if (empty($printerName)) {
                    $printerName = $config->printer_name;
                }

                $connector = new WindowsPrintConnector($printerName);
                $printer = new Printer($connector);

                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->setTextSize(1, 1);

                $printer->text(strtoupper($config->business_name) . "\n");
                $printer->setTextSize(1, 1);
                $printer->text("$config->address \n");
                $printer->text("NIT: $config->taxpayer_id \n");
                $printer->text("TEL: $config->phone \n\n");

                $printer->setJustification(Printer::JUSTIFY_LEFT);
                //$printer->text("=============================================\n");
                $printer->text("Folio: " . $sale->id . "\n");
                $printer->text("Fecha: " . Carbon::parse($sale->created_at)->format('d/m/Y h:m:s') . "\n");
                $printer->text("Cajero: " . $sale->user->name . " \n");
                $condition = $sale->type == 'credit' ? 'CRÉDITO' : 'CONTADO';
                $printer->text("Condición: " . $condition . "\n");
                //$printer->text("=============================================\n");



                $currencySymbol = '$';
                if ($sale->primary_currency_code) {
                    $currencySymbol = \App\Helpers\CurrencyHelper::getSymbol($sale->primary_currency_code);
                } else {
                    $primary = \App\Helpers\CurrencyHelper::getPrimaryCurrency();
                    if ($primary) {
                        $currencySymbol = $primary->symbol;
                    }
                }

                // Determine widths based on configuration
                if (!empty(Auth::user()->printer_name)) {
                     $widthConfig = Auth::user()->printer_width ?? '80mm';
                } else {
                     $widthConfig = $config->printer_width ?? '80mm';
                }

                $is58mm = $widthConfig === '58mm';
                
                if ($is58mm) {
                    // 58mm ~32 chars
                    $maskHead = "%-16.16s %-5.5s %-9.9s"; // 16+1+5+1+9 = 32
                    $maskRow = $maskHead;
                    $col1Width = 16;
                    $separator = "--------------------------------";
                } else {
                    // 80mm ~42-48 chars
                    $maskHead = "%-30s %-5s %-8s";
                    $maskRow = $maskHead;
                    $col1Width = 30;
                    $separator = "=============================================";
                }

                $headersName = sprintf($maskHead, 'DESCRIPCION', 'CANT', 'PRECIO');
                $printer->text($separator . "\n");
                $printer->text($headersName . "\n");
                $printer->text($separator . "\n");

                foreach ($sale->details as $item) {

                    $descripcion_1 = $this->cortar($item->product->name, $col1Width);
                    $row_1 = sprintf($maskRow, $descripcion_1[0], $item->quantity, $currencySymbol . number_format($item->sale_price, 2));
                    $printer->text($row_1 . "\n");

                    if (isset($descripcion_1[1])) {
                        $row_2 = sprintf($maskRow, $descripcion_1[1], '', '', '');
                        $printer->text($row_2 . "\n");
                    }
                }

                $printer->text($separator . "\n");

                $printer->text("CLIENTE: " . $sale->customer->name  . "\n\n");


                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->text("NO. DE ARTICULOS $sale->items" . "\n");

                $printer->setJustification(Printer::JUSTIFY_LEFT);

                $desglose = $this->desgloseMonto($sale->total);
                $printer->text("SUBTOTAL....... " . $currencySymbol . number_format($desglose['subtotal'], 2) . "\n");
                $printer->text("IVA............ " . $currencySymbol . number_format($desglose['iva'], 2) . "\n");
                $printer->text("TOTAL.......... " . $currencySymbol . number_format($sale->total, 2) . "\n");

                if ($sale->type == 'cash') {
                    $printer->text("EFECTIVO....... " . $currencySymbol . number_format($sale->cash, 2) . "\n");
                    if (floatval($sale->change) > 0)  $printer->text("\nCAMBIO......... " . $currencySymbol . number_format($sale->change, 2) . "\n");
                } else {
                    $printer->text($sale->type == 'credit' ? "FORMA DE PAGO: CRÉDITO" :  "FORMA DE PAGO:  DEPÓSITO" .  "\n");
                }

                $printer->feed(3);
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->text("$config->leyend\n");
                $printer->text("$config->website\n");
                $printer->feed(3);
                $printer->cut();
                $printer->close();
            } else {
                Log::info("La tabla configurations está vacía, no es posible imprimir la venta");
            }
            //
        } catch (\Exception $th) {
            Log::info("Error al intentar imprimir el comprobante de venta \n {$th->getMessage()}");
            $this->dispatch('noty', msg: 'ERROR AL IMPRIMIR: ' . $th->getMessage());
        }
    }

    // recibo de pago / abono
    public  function printPayment($payId)
    {
        try {
            $config = Configuration::first();

            if ($config) {
                $connector = new WindowsPrintConnector($config->printer_name);
                $printer = new Printer($connector);
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->setTextSize(1, 1);

                $printer->text(strtoupper($config->business_name) . "\n");

                $printer->setTextSize(1, 1);
                $printer->text("$config->address \n");
                $printer->text("NIT: $config->taxpayer_id \n");
                $printer->text("TEL: $config->phone \n\n");

                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->text("==  Comprobante de Pago ==" . "\n\n");

                $printer->setJustification(Printer::JUSTIFY_LEFT);

                $payment = Payment::with(['sale', 'zelleRecord'])->where('id', $payId)->first();

                $currencySymbol = '$';
                if ($payment->sale->primary_currency_code) {
                    $currencySymbol = \App\Helpers\CurrencyHelper::getSymbol($payment->sale->primary_currency_code);
                } else {
                    $primary = \App\Helpers\CurrencyHelper::getPrimaryCurrency();
                    if ($primary) {
                        $currencySymbol = $primary->symbol;
                    }
                }

                // Determine widths based on configuration
                if (!empty(Auth::user()->printer_name)) {
                     $widthConfig = Auth::user()->printer_width ?? '80mm';
                } else {
                     $widthConfig = $config->printer_width ?? '80mm';
                }

                $is58mm = $widthConfig === '58mm';
                $separator = $is58mm ? "--------------------------------" : "=============================================";

                $printer->text("Folio:" . $payment->id . "\n");
                $printer->text("Fecha:" . Carbon::parse($payment->created_at)->format('d-m-Y H:i') . "\n");
                $printer->text("Cliente:" . $payment->sale->customer->name . "\n");
                $printer->text($separator . "\n");
                $printer->text("Compra: " . $currencySymbol . $payment->sale->total . "\n");
                $printer->text("Abono: " . $currencySymbol . $payment->amount . "\n");

                if ($payment->sale->debt <= 0) {
                    $printer->text("CRÉDITO LIQUIDADO \n");
                } else {
                    $printer->text("Deuda actual: " . $currencySymbol . $payment->sale->debt . "\n\n");
                }

                //    $printer->text("Forma de Pago:" . ($payment->pay_way == 'cash' ? 'EFECTIVO' : 'DEPÓSITO')  . "\n");

                $printer->text("Forma de Pago: ");
                switch ($payment->pay_way) {
                    case 'cash':
                        $printer->text("EFECTIVO\n");
                        break;
                    case 'deposit':
                        $printer->text("DEPÓSITO\n");
                        break;
                    case 'zelle':
                        $printer->text("ZELLE\n");
                        break;
                    default:
                        $printer->text("EFECTIVO\n");
                }



                if ($payment->pay_way == 'deposit') {
                    $printer->text($payment->bank . "\n");
                    $printer->text("No. Cuenta:" . $payment->account_number . "\n");
                    $printer->text("No. Depósito:" . $payment->deposit_number . "\n");
                } elseif ($payment->pay_way == 'zelle' && $payment->zelleRecord) {
                    $printer->text("Emisor: " . $payment->zelleRecord->sender_name . "\n");
                    $printer->text("Fecha: " . \Carbon\Carbon::parse($payment->zelleRecord->zelle_date)->format('d/m/Y') . "\n");
                    if ($payment->zelleRecord->reference) {
                        $printer->text("Ref: " . $payment->zelleRecord->reference . "\n");
                    }
                }



                $printer->text($separator . "\n");
                $printer->text("Atiende:" . $payment->sale->user->name . "\n");


                $printer->feed(3);
                $printer->cut();
                $printer->close();
            } else {
                Log::info("La tabla configurations está vacía, no es posible imprimir el comprobante de pago");
            }
            //
        } catch (\Exception $th) {
            Log::info("Error al intentar imprimir el comprobante de pago \n {$th->getMessage()}");
        }
    }

    // recibo de pago / abono
    public  function printPayable($payId)
    {
        try {
            $config = Configuration::first();

            if ($config) {
                $connector = new WindowsPrintConnector($config->printer_name);
                $printer = new Printer($connector);
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->setTextSize(1, 1);

                $printer->text(strtoupper($config->business_name) . "\n");

                $printer->setTextSize(1, 1);
                $printer->text("$config->address \n");
                $printer->text("NIT: $config->taxpayer_id \n");
                $printer->text("TEL: $config->phone \n\n");

                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->text("==  Comprobante de Pago ==" . "\n\n");

                $printer->setJustification(Printer::JUSTIFY_LEFT);

                $payable = Payable::with('purchase')->where('id', $payId)->first();

                $currencySymbol = '$';
                if ($payable->purchase->primary_currency_code) {
                    $currencySymbol = \App\Helpers\CurrencyHelper::getSymbol($payable->purchase->primary_currency_code);
                } else {
                    $primary = \App\Helpers\CurrencyHelper::getPrimaryCurrency();
                    if ($primary) {
                        $currencySymbol = $primary->symbol;
                    }
                }

                // Determine widths based on configuration
                if (!empty(Auth::user()->printer_name)) {
                     $widthConfig = Auth::user()->printer_width ?? '80mm';
                } else {
                     $widthConfig = $config->printer_width ?? '80mm';
                }

                $is58mm = $widthConfig === '58mm';
                $separator = $is58mm ? "--------------------------------" : "=============================================";

                $printer->text("Folio:" . $payable->id . "\n");
                $printer->text("Fecha:" . Carbon::parse($payable->created_at)->format('d-m-Y H:i') . "\n");
                $printer->text("Proveedor:" . $payable->purchase->supplier->name . "\n");
                $printer->text($separator . "\n");
                $printer->text("Compra: " . $currencySymbol . $payable->purchase->total . "\n");
                $printer->text("Abono: " . $currencySymbol . $payable->amount . "\n");

                if ($payable->purchase->debt <= 0) {
                    $printer->text("CRÉDITO LIQUIDADO \n");
                } else {
                    $printer->text("Deuda actual: " . $currencySymbol . $payable->purchase->debt . "\n\n");
                }

                //    $printer->text("Forma de Pago:" . ($payment->pay_way == 'cash' ? 'EFECTIVO' : 'DEPÓSITO')  . "\n");

                $printer->text("Forma de Pago: ");
                switch ($payable->pay_way) {
                    case 'cash':
                        $printer->text("EFECTIVO\n");
                        break;
                    case 'deposit':
                        $printer->text("DEPÓSITO\n");
                        break;
                    default:
                        $printer->text("EFECTIVO\n");
                }



                if ($payable->pay_way == 'deposit') {
                    $printer->text($payable->bank . "\n");
                    $printer->text("No. Cuenta:" . $payable->account_number . "\n");
                    $printer->text("No. Depósito:" . $payable->deposit_number . "\n");
                }



                $printer->text("=============================================" . "\n");
                $printer->text("Atiende:" . $payable->purchase->user->name . "\n");


                $printer->feed(3);
                $printer->cut();
                $printer->close();
            } else {
                Log::info("La tabla configurations está vacía, no es posible imprimir el comprobante de pago");
            }
            //
        } catch (\Exception $th) {
            Log::info("Error al intentar imprimir el comprobante de pago \n {$th->getMessage()}");
        }
    }



    // Definir una función para cortar una cadena si es más larga que un límite y devolver un arreglo
    function cortar($cadena, $limite)
    {
        // Crear un arreglo vacío
        $resultado = array();
        // Si la cadena es más corta o igual que el límite, se agrega al arreglo sin modificar
        if (strlen($cadena) <= $limite) {
            $resultado[] = $cadena;
        }
        // Si la cadena es más larga que el límite, se busca el último espacio dentro del límite
        else {
            $ultimo_espacio = strrpos(substr($cadena, 0, $limite), ' ');
            // Se agrega al arreglo la primera parte de la cadena hasta el último espacio
            $resultado[] = substr($cadena, 0, $ultimo_espacio);
            // Se agrega al arreglo la segunda parte de la cadena desde el último espacio más uno
            $resultado[] = substr($cadena, $ultimo_espacio + 1);
        }
        // Se devuelve el arreglo
        return $resultado;
    }




    function printCashCount($user_name, $dfrom, $dto, $totales, $salesTotal, $cash, $nequi, $deposit, $payments, $credit, $pcash, $pdeposit, $pnequi, $salesByCurrency = [], $paymentsByCurrency = [])
    {
        try {

            $config = Configuration::first();

            if ($config) {
                $connector = new WindowsPrintConnector($config->printer_name);
                $printer = new Printer($connector);

                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->setTextSize(1, 1);

                $printer->text(strtoupper($config->business_name) . "\n");
                $printer->setTextSize(1, 1);
                $printer->text("Corte de Caja \n");
                //$printer->text("NIT: $config->taxpayer_id \n\n");


                $printer->setJustification(Printer::JUSTIFY_LEFT);

                // Determine widths based on configuration
                if (!empty(Auth::user()->printer_name)) {
                     $widthConfig = Auth::user()->printer_width ?? '80mm';
                } else {
                     $widthConfig = $config->printer_width ?? '80mm';
                }

                $is58mm = $widthConfig === '58mm';
                $separator = $is58mm ? "--------------------------------" : "=============================================";

                $printer->text($separator . "\n");
                $printer->text("Desde: " . Carbon::parse($dfrom)->format('d/m/Y') . "\n");
                $printer->text("Hasta: " . Carbon::parse($dto)->format('d/m/Y') . "\n");
                $printer->text("Usuario: " . $user_name . " \n");
                $printer->text($separator . "\n");

                $primary = \App\Helpers\CurrencyHelper::getPrimaryCurrency();
                $currencySymbol = $primary ? $primary->symbol : '$';

                $printer->text("RESUMEN GENERAL\n");
                $printer->text("VENTAS TOTALES: " . $currencySymbol . number_format($salesTotal, 2) . "\n");
                $printer->text("  Contado: " . $currencySymbol . number_format($cash, 2) . "\n");

                $printer->text("  Banco: " . $currencySymbol . number_format($deposit, 2) . "\n");
                $printer->text("  Crédito: " . $currencySymbol . number_format($credit, 2) . "\n");
                $printer->text("---------" . "\n");
                $printer->text("ABONOS RECIBIDOS: " . $currencySymbol . number_format($payments, 2) . "\n");
                $printer->text("  Contado: " . $currencySymbol . number_format($pcash, 2) . "\n");

                $printer->text("  Banco: " . $currencySymbol . number_format($pdeposit, 2) . "\n");
                $printer->text($separator . "\n");

                // DETAILED SALES BREAKDOWN
                if (!empty($salesByCurrency)) {
                    $printer->setJustification(Printer::JUSTIFY_CENTER);
                    $printer->text("DETALLE VENTAS POR MONEDA\n");
                    $printer->setJustification(Printer::JUSTIFY_LEFT);
                    $printer->text("---------------------------------------------\n");

                    // Helper to get currency label
                    $getCurrencyLabel = function($code) {
                        $c = \App\Models\Currency::where('code', $code)->first();
                        return $c ? $c->label . " (" . $code . ")" : $code;
                    };

                    // Cash Sales
                    if (!empty($salesByCurrency['cash'])) {
                        $printer->text("EFECTIVO:\n");
                        foreach ($salesByCurrency['cash'] as $currency => $amount) {
                            $printer->text("  " . $getCurrencyLabel($currency) . ": " . number_format($amount, 2) . "\n");
                        }
                    }


                // Deposit Payments
                if (!empty($paymentsByCurrency['deposit'])) {
                    $printer->text("BANCO:\n");
                    foreach ($paymentsByCurrency['deposit'] as $bankName => $currencies) {
                        $printer->text("  " . $bankName . ":\n");
                        foreach ($currencies as $currency => $amount) {
                            $printer->text("    " . $getCurrencyLabel($currency) . ": " . number_format($amount, 2) . "\n");
                        }
                    }
                }
                     $printer->text($separator . "\n");
                }


                $printer->feed(3);
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->cut();
                $printer->close();
            } else {
                Log::info("La tabla configurations está vacía, no es posible imprimir el corte de caja");
            }
            //
        } catch (\Exception $th) {
            Log::info("Error al intentar imprimir el corte de caja \n {$th->getMessage()} ");
        }
    }

    function printOrder($orderId)
    {
        try {
            $config = Configuration::first();

            if ($config) {
                $order = Order::with(['customer', 'user', 'details', 'details.product'])->find($orderId);

                // Determine printer name: User assigned or Global default
                $printerName = Auth::user()->printer_name ?? $config->printer_name;
                
                // If user has empty string as printer name, fallback to config
                if (empty($printerName)) {
                    $printerName = $config->printer_name;
                }

                $connector = new WindowsPrintConnector($printerName);
                $printer = new Printer($connector);

                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->setTextSize(1, 1);

                $printer->text(strtoupper($config->business_name) . "\n");
                $printer->setTextSize(1, 1);
                $printer->text("$config->address \n");
                $printer->text("NIT: $config->taxpayer_id \n");
                $printer->text("TEL: $config->phone \n\n");

                // Add Title
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->setTextSize(1, 2);
                $printer->text("** ORDEN DE VENTA **\n\n");
                $printer->setTextSize(1, 1);

                $printer->setJustification(Printer::JUSTIFY_LEFT);
                //$printer->text("=============================================\n");
                $printer->text("Folio: " . $order->id . "\n");
                $printer->text("Fecha: " . Carbon::parse($order->created_at)->format('d/m/Y h:m:s') . "\n");
                $printer->text("Cajero: " . $order->user->name . " \n");
                //$printer->text("=============================================\n");

                // Determine widths based on configuration
                // If user has a specific printer assigned, use their width preference (defaulting to 80mm if not set)
                // Otherwise use global config width
                if (!empty(Auth::user()->printer_name)) {
                     $widthConfig = Auth::user()->printer_width ?? '80mm';
                } else {
                     $widthConfig = $config->printer_width ?? '80mm';
                }

                $is58mm = $widthConfig === '58mm';
                
                if ($is58mm) {
                    // 58mm ~32 chars
                    $maskHead = "%-16.16s %-5.5s %-9.9s"; // 16+1+5+1+9 = 32
                    $maskRow = $maskHead;
                    $col1Width = 16;
                    $separator = "--------------------------------";
                } else {
                    // 80mm ~42-48 chars (using 42 as safe default from previous code)
                    $maskHead = "%-30s %-5s %-8s";
                    $maskRow = $maskHead;
                    $col1Width = 30;
                    $separator = "=============================================";
                }

                $headersName = sprintf($maskHead, 'DESCRIPCION', 'CANT', 'PRECIO');
                $printer->text($separator . "\n");
                $printer->text($headersName . "\n");
                $printer->text($separator . "\n");

                foreach ($order->details as $item) {
                    $descripcion_1 = $this->cortar($item->product->name, $col1Width);
                    $row_1 = sprintf($maskRow, $descripcion_1[0], $item->quantity, '$' . number_format($item->sale_price, 2));
                    $printer->text($row_1 . "\n");

                    if (isset($descripcion_1[1])) {
                        $row_2 = sprintf($maskRow, $descripcion_1[1], '', '', '');
                        $printer->text($row_2 . "\n");
                    }
                }

                $printer->text($separator . "\n");

                $printer->text("CLIENTE: " . $order->customer->name  . "\n\n");

                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->text("NO. DE ARTICULOS $order->items" . "\n");

                $printer->setJustification(Printer::JUSTIFY_LEFT);

                $desglose = $this->desgloseMonto($order->total);
                $printer->text("SUBTOTAL....... $" . number_format($desglose['subtotal'], 2) . "\n");
                $printer->text("IVA............ $" . number_format($desglose['iva'], 2) . "\n");
                $printer->text("TOTAL.......... $" . number_format($order->total, 2) . "\n");

                if ($order->type == 'cash') {
                    $printer->text("EFECTIVO....... $" . number_format($order->cash, 2) . "\n");
                    if (floatval($order->change) > 0)  $printer->text("\nCAMBIO......... $" . number_format($order->change, 2) . "\n");
                } else {
                    $printer->text($order->type == 'credit' ? "FORMA DE PAGO: CRÉDITO" :  "FORMA DE PAGO:  DEPÓSITO" .  "\n");
                }

                $printer->feed(3);
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->text("$config->leyend\n");
                $printer->text("$config->website\n");
                $printer->feed(3);
                $printer->cut();
                $printer->close();
            } else {
                Log::info("La tabla configurations está vacía, no es posible imprimir la venta");
            }
            //
        } catch (\Exception $th) {
            Log::info("Error al intentar imprimir el comprobante de venta \n {$th->getMessage()}");
        }
    }

    function printPaymentHistory($saleId)
    {
        try {
            $config = Configuration::first();
            if ($config) {
                $sale = Sale::with(['customer', 'payments', 'user'])->find($saleId);
                
                $connector = new WindowsPrintConnector($config->printer_name);
                $printer = new Printer($connector);

                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->setTextSize(1, 1);

                $printer->text(strtoupper($config->business_name) . "\n");
                $printer->setTextSize(1, 1);
                $printer->text("Historial de Pagos\n\n");

                // Determine widths based on configuration
                if (!empty(Auth::user()->printer_name)) {
                     $widthConfig = Auth::user()->printer_width ?? '80mm';
                } else {
                     $widthConfig = $config->printer_width ?? '80mm';
                }

                $is58mm = $widthConfig === '58mm';
                $separator = $is58mm ? "--------------------------------" : "=============================================";

                $printer->setJustification(Printer::JUSTIFY_LEFT);
                $printer->text($separator . "\n");
                $printer->text("Folio Venta: " . $sale->id . "\n");
                $printer->text("Fecha Emisión: " . Carbon::parse($sale->created_at)->format('d/m/Y H:i') . "\n");
                $printer->text("Cliente: " . $sale->customer->name . "\n");
                $printer->text($separator . "\n");

                $mask = "%-10.10s %-10.10s %-10.10s";
                $printer->text(sprintf($mask, "FECHA", "MONTO", "METODO") . "\n");
                $printer->text($separator . "\n");

                $totalPaidUSD = 0;
                $primaryCurrency = \App\Models\Currency::where('is_primary', 1)->first();
                $primaryCode = $primaryCurrency ? $primaryCurrency->code : 'USD';

                foreach ($sale->payments as $payment) {
                    $date = Carbon::parse($payment->created_at)->format('d/m/y');
                    $amount = number_format($payment->amount, 2);
                    $method = $payment->pay_way == 'cash' ? 'Efectivo' : ($payment->pay_way == 'deposit' ? 'Banco' : $payment->pay_way);
                    
                    // Add currency code to amount
                    $amountStr = $amount . " " . $payment->currency;

                    $printer->text("$date  $method\n");
                    $printer->setJustification(Printer::JUSTIFY_RIGHT);
                    $printer->text("$amountStr\n");
                    $printer->setJustification(Printer::JUSTIFY_LEFT);

                    // Calculate USD total
                    $rate = $payment->exchange_rate > 0 ? $payment->exchange_rate : 1;
                    $amountUSD = $payment->amount / $rate;
                    $totalPaidUSD += $amountUSD;
                }

                $printer->text($separator . "\n");
                
                // Totals
                $totalSaleUSD = $sale->total; 
                if ($sale->total_usd > 0) {
                    $totalSaleUSD = $sale->total_usd;
                } else {
                     $rate = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;
                     $totalSaleUSD = $sale->total / $rate;
                }

                $balanceUSD = $totalSaleUSD - $totalPaidUSD;
                if($balanceUSD < 0) $balanceUSD = 0;
                
                // Convert to System Currency (Primary)
                $primaryRate = $primaryCurrency ? $primaryCurrency->exchange_rate : 1;
                $totalPaidSystem = $totalPaidUSD * $primaryRate;
                $balanceSystem = $balanceUSD * $primaryRate;

                $printer->setJustification(Printer::JUSTIFY_RIGHT);
                $printer->text("Total Pagado (USD): $" . number_format($totalPaidUSD, 2) . "\n");
                $printer->text("Total Pagado ($primaryCode): $" . number_format($totalPaidSystem, 2) . "\n");
                $printer->text("Saldo Pendiente ($primaryCode): $" . number_format($balanceSystem, 2) . "\n");

                if ($sale->days_overdue > 0) {
                    $printer->text("\n");
                    $printer->setJustification(Printer::JUSTIFY_CENTER);
                    $printer->text("*** CUENTA VENCIDA ***\n");
                    $printer->text("Días de atraso: " . $sale->days_overdue . "\n");
                }

                $printer->feed(3);
                $printer->cut();
                $printer->close();
            }
        } catch (\Exception $th) {
            Log::error("Error printing payment history: " . $th->getMessage());
        }
    }
}

