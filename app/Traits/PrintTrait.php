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
use App\Services\CustomWindowsPrintConnector;

trait PrintTrait
{
    use UtilTrait;

    protected function getPrinterConfig() {
        $config = Configuration::first();
        if (!$config) return null;

        // Determine printer name: Device > User > Global
        $printerName = null;
        $printerWidth = '80mm';
        
        // 1. Check Device Authorization
        $deviceToken = \Illuminate\Support\Facades\Cookie::get('device_token');
        $isNetwork = false;
        $printerUser = null;
        $printerPassword = null;

        if ($deviceToken) {
            $deviceAuth = \App\Models\DeviceAuthorization::where('uuid', $deviceToken)->first();
            if ($deviceAuth && !empty($deviceAuth->printer_name)) {
                $printerName = $deviceAuth->printer_name;
                $printerWidth = $deviceAuth->printer_width ?? '80mm';
                $isNetwork = $deviceAuth->is_network;
                $printerUser = $deviceAuth->printer_user;
                $printerPassword = $deviceAuth->printer_password;
            }
        }

        // 2. Check User (if no device printer)
        if (empty($printerName)) {
            $user = Auth::user();
            if ($user && $user->printer_name) {
                $printerName = $user->printer_name;
                $printerWidth = $user->printer_width ?? '80mm';
                $isNetwork = $user->is_network;
                $printerUser = $user->printer_user;
                $printerPassword = $user->printer_password;
            }
        }

        // 3. Check Global Config (if no user printer)
        if (empty($printerName)) {
            $printerName = $config->printer_name;
            $printerWidth = $config->printer_width ?? '80mm';
            $isNetwork = $config->is_network;
            $printerUser = $config->printer_user;
            $printerPassword = $config->printer_password;
        }
        
        // Final fallback
        if (empty($printerName)) {
            $printerName = $config->printer_name;
        }

        if ($isNetwork && $printerUser && $printerPassword) {
                // Let's try to detect if it is a UNC path to convert it to smb format
                $cleanName = str_replace('\\\\', '', $printerName); // Remove leading \\
                $parts = explode('\\', $cleanName);
                
                if (count($parts) >= 2) {
                    $computer = $parts[0];
                    $share = $parts[1];
                     // Need to URL Encode user and pass if they have special chars for the URI format to be valid
                     // But parse_url needs them encoded to separate correctly if they contain @ or :
                     // However, our Custom Connector will handle raw strings if we pass safely or we should encode?
                     // Standard URL encoding is safest for URI format.
                     $encUser = urlencode($printerUser);
                     $encPass = urlencode($printerPassword);
                     $printerName = "smb://{$encUser}:{$encPass}@{$computer}/{$share}";
                } else {
                    $encUser = urlencode($printerUser);
                    $encPass = urlencode($printerPassword);
                    $printerName = "smb://{$encUser}:{$encPass}@{$cleanName}";
                }
        }

        return [
            'config' => $config,
            'printerName' => $printerName,
            'printerWidth' => $printerWidth
        ];
    }

    function printSale($saleId)
    {

        try {

            $printConfig = $this->getPrinterConfig();
            
            if ($printConfig) {
                $config = $printConfig['config'];
                $printerName = $printConfig['printerName'];
                $printerWidth = $printConfig['printerWidth'];

                $sale = Sale::with(['customer', 'user', 'details', 'details.product'])->find($saleId);

                // Use Custom Connector
                $connector = new CustomWindowsPrintConnector($printerName);
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
                $widthConfig = $printerWidth;

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
            $printConfig = $this->getPrinterConfig();

            if ($printConfig) {
                $config = $printConfig['config'];
                $printerName = $printConfig['printerName'];
                $printerWidth = $printConfig['printerWidth'];

                $connector = new CustomWindowsPrintConnector($printerName);
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
                $widthConfig = $printerWidth;

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
            $this->dispatch('noty', msg: 'ERROR AL IMPRIMIR PAGO: ' . $th->getMessage());
        }
    }

    // recibo de pago / abono
    public  function printPayable($payId)
    {
        try {
            $printConfig = $this->getPrinterConfig();

            if ($printConfig) {
                $config = $printConfig['config'];
                $printerName = $printConfig['printerName'];
                $printerWidth = $printConfig['printerWidth'];

                $connector = new CustomWindowsPrintConnector($printerName);
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
                $widthConfig = $printerWidth;

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
            $this->dispatch('noty', msg: 'ERROR AL IMPRIMIR ABONO: ' . $th->getMessage());
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




    function printCashCount($user_name, $dfrom, $dto, $totales, $salesTotal, $cash, $nequi, $deposit, $payments, $credit, $pcash, $pdeposit, $pnequi, $salesByCurrency = [], $paymentsByCurrency = [], $walletAdded = 0, $walletUsed = 0, $grandTotal = 0)
    {
        try {
            $printConfig = $this->getPrinterConfig();

            if ($printConfig) {
                $config = $printConfig['config'];
                $printerName = $printConfig['printerName'];
                $printerWidth = $printConfig['printerWidth'];

                $connector = new CustomWindowsPrintConnector($printerName);
                $printer = new Printer($connector);

                $widthConfig = $printerWidth;
                $is58mm = $widthConfig === '58mm';
                $separator = $is58mm ? "--------------------------------" : "=============================================";

                $primary = \App\Helpers\CurrencyHelper::getPrimaryCurrency();
                $currencySymbol = $primary ? $primary->symbol : '$';

                // --- HEADER ---
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->setTextSize(1, 1);
                $printer->text(strtoupper($config->business_name) . "\n");
                $printer->text("CORTE DE CAJA\n");
                $printer->setJustification(Printer::JUSTIFY_LEFT);
                $printer->text($separator . "\n");
                $printer->text("Fecha: " . Carbon::parse($dfrom)->format('d/m/Y') . " - " . Carbon::parse($dto)->format('d/m/Y') . "\n");
                $printer->text("Cajero: " . $user_name . "\n");
                $printer->text($separator . "\n\n");

                // Helper for labels
                $getCurrencyLabel = function($code) {
                    $c = \App\Models\Currency::where('code', $code)->first();
                    return $c ? $c->label . " (" . $code . ")" : $code;
                };

                // --- SECTION 1: VENTAS DEL DÍA ---
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->text("VENTAS DEL DÍA (FLUJO NETO)\n");
                $printer->setJustification(Printer::JUSTIFY_LEFT);
                $printer->text($separator . "\n");

                // Cash
                if (!empty($salesByCurrency['cash'])) {
                    $printer->text("EFECTIVO:\n");
                    foreach ($salesByCurrency['cash'] as $currency => $amount) {
                         if ($currency === '_CUSTODIA_') {
                             $label = 'BILLETERA (CUSTODIA)';
                         } else {
                             $label = $getCurrencyLabel($currency);
                         }
                         $printer->text("  " . $label . ": " . number_format($amount, 2) . "\n");
                    }
                }
                // Bank
                if (!empty($salesByCurrency['deposit'])) {
                    $printer->text("BANCO:\n");
                    foreach ($salesByCurrency['deposit'] as $bankName => $currencies) {
                        if (is_array($currencies)) {
                             $printer->text("  " . $bankName . ":\n");
                             foreach ($currencies as $curr => $amt) {
                                  $printer->text("    " . $getCurrencyLabel($curr) . ": " . number_format($amt, 2) . "\n");
                             }
                        } else {
                             $printer->text("  Otros: " . $getCurrencyLabel($bankName) . ": " . number_format($currencies, 2) . "\n");
                        }
                    }
                }
                // Zelle
                if (!empty($salesByCurrency['zelle'])) {
                    $printer->text("ZELLE:\n");
                    foreach ($salesByCurrency['zelle'] as $sender => $amount) {
                         $printer->text("  " . substr($sender, 0, 18) . ": " . number_format($amount, 2) . "\n");
                    }
                }
                
                $printer->text("\nTOTAL VENTAS RECIBIDAS: " . $currencySymbol . number_format($salesTotal - $credit, 2) . "\n");
                $printer->text("VENTAS A CRÉDITO: " . $currencySymbol . number_format($credit, 2) . "\n\n");


                // --- SECTION 2: PAGOS RECIBIDOS ---
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->text("PAGOS DE CRÉDITOS RECIBIDOS\n");
                $printer->setJustification(Printer::JUSTIFY_LEFT);
                $printer->text($separator . "\n");

                // Cash
                if (!empty($paymentsByCurrency['cash'])) {
                    $printer->text("EFECTIVO:\n");
                    foreach ($paymentsByCurrency['cash'] as $currency => $amount) {
                         $printer->text("  " . $getCurrencyLabel($currency) . ": " . number_format($amount, 2) . "\n");
                    }
                }
                // Bank
                if (!empty($paymentsByCurrency['deposit'])) {
                    $printer->text("BANCO:\n");
                    foreach ($paymentsByCurrency['deposit'] as $bankName => $currencies) {
                        if (is_array($currencies)) {
                             $printer->text("  " . $bankName . ":\n");
                             foreach ($currencies as $curr => $amt) {
                                  $printer->text("    " . $getCurrencyLabel($curr) . ": " . number_format($amt, 2) . "\n");
                             }
                        } else {
                             $printer->text("  Otros: " . $getCurrencyLabel($bankName) . ": " . number_format($currencies, 2) . "\n");
                        }
                    }
                }
                // Zelle
                if (!empty($paymentsByCurrency['zelle'])) {
                    $printer->text("ZELLE:\n");
                    foreach ($paymentsByCurrency['zelle'] as $sender => $amount) {
                         $printer->text("  " . substr($sender, 0, 18) . ": " . number_format($amount, 2) . "\n");
                    }
                }
                
                $printer->text("\nTOTAL PAGOS RECIBIDOS: " . $currencySymbol . number_format($payments, 2) . "\n\n");


                // --- SECTION 3: RESUMEN TOTAL ---
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->text("RESUMEN TOTAL\n");
                $printer->setJustification(Printer::JUSTIFY_LEFT);
                $printer->text($separator . "\n");

                $printer->text("TOTAL EFECTIVO: \n");
                $totalCashFinal = [];
                // Add sales cash flows (already net)
                foreach (($salesByCurrency['cash'] ?? []) as $c => $a) {
                    if ($c === '_CUSTODIA_') continue; // Custody handled separately
                    $totalCashFinal[$c] = ($totalCashFinal[$c] ?? 0) + $a;
                }
                // Add credit payments cash
                foreach (($paymentsByCurrency['cash'] ?? []) as $c => $a) {
                    $totalCashFinal[$c] = ($totalCashFinal[$c] ?? 0) + $a;
                }
                foreach ($totalCashFinal as $c => $a) {
                     $printer->text("  " . $getCurrencyLabel($c) . ": " . number_format($a, 2) . "\n");
                }

                $printer->text("TOTAL BANCO: \n");
                $totalBankFinal = [];
                // Add sales bank flows
                foreach (($salesByCurrency['deposit'] ?? []) as $bn => $currAmt) {
                    if (is_array($currAmt)) {
                        foreach ($currAmt as $c => $a) { $totalBankFinal[$bn][$c] = ($totalBankFinal[$bn][$c] ?? 0) + $a; }
                    } else {
                        $totalBankFinal['Otros'][$bn] = ($totalBankFinal['Otros'][$bn] ?? 0) + $currAmt;
                    }
                }
                // Add credit payments bank flows
                foreach (($paymentsByCurrency['deposit'] ?? []) as $bn => $currAmt) {
                    if (is_array($currAmt)) {
                        foreach ($currAmt as $c => $a) { $totalBankFinal[$bn][$c] = ($totalBankFinal[$bn][$c] ?? 0) + $a; }
                    } else {
                        $totalBankFinal['Otros'][$bn] = ($totalBankFinal['Otros'][$bn] ?? 0) + $currAmt;
                    }
                }
                foreach ($totalBankFinal as $bn => $currs) {
                     $printer->text("  " . $bn . ":\n");
                     foreach ($currs as $c => $a) {
                         $printer->text("    " . $getCurrencyLabel($c) . ": " . number_format($a, 2) . "\n");
                     }
                }

                $zelleTotal = 0;
                $zelleTotal += array_sum($salesByCurrency['zelle'] ?? []);
                $zelleTotal += array_sum($paymentsByCurrency['zelle'] ?? []);
                $printer->text("TOTAL ZELLE: $" . number_format($zelleTotal, 2) . "\n\n");

                // --- SECTION 4: BILLETERA / CUSTODIA ---
                if ($walletAdded > 0 || $walletUsed > 0) {
                    $printer->setJustification(Printer::JUSTIFY_CENTER);
                    $printer->text("MOVIMIENTOS BILLETERA\n");
                    $printer->setJustification(Printer::JUSTIFY_LEFT);
                    $printer->text($separator . "\n");
                    $printer->text("Custodia Hoy (+): " . $currencySymbol . number_format($walletAdded, 2) . "\n");
                    $printer->text("Consumo Billetera (-): " . $currencySymbol . number_format($walletUsed, 2) . "\n");
                    $printer->text($separator . "\n\n");
                }

                $printer->text($separator . "\n");
                $printer->setTextSize(1, 1);
                
                // If grandTotal was not passed, fallback to old formula (but it should be passed)
                $finalTotal = $grandTotal > 0 ? $grandTotal : ($salesTotal - $credit + $payments);
                
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->text("TOTAL EN CAJA (A ENTREGAR):\n");
                $printer->setTextSize(1, 2);
                $printer->text($currencySymbol . " " . number_format($finalTotal, 2) . "\n");
                $printer->setTextSize(1, 1);
                $printer->text($separator . "\n");

                $printer->feed(3);
                $printer->cut();
                $printer->close();
            } else {
                Log::info("La configuración de impresora no está disponible.");
            }
        } catch (\Exception $th) {
            Log::info("Error al intentar imprimir el corte de caja \n {$th->getMessage()} ");
            $this->dispatch('noty', msg: 'ERROR AL IMPRIMIR CORTE: ' . $th->getMessage());
        }
    }
    function printOrder($orderId)
    {
        try {
            $printConfig = $this->getPrinterConfig();

            if ($printConfig) {
                $config = $printConfig['config'];
                $printerName = $printConfig['printerName'];
                $printerWidth = $printConfig['printerWidth'];

                $order = Order::with(['customer', 'user', 'details', 'details.product'])->find($orderId);

                $connector = new CustomWindowsPrintConnector($printerName);
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
                $widthConfig = $printerWidth;

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
            $this->dispatch('noty', msg: 'ERROR AL IMPRIMIR ORDEN: ' . $th->getMessage());
        }
    }

    function printPaymentHistory($saleId)
    {
        try {
            $printConfig = $this->getPrinterConfig();
            
            if ($printConfig) {
                $config = $printConfig['config'];
                $printerName = $printConfig['printerName'];
                $printerWidth = $printConfig['printerWidth'];

                $sale = Sale::with(['customer', 'payments', 'returns', 'user'])->find($saleId);
                
                $connector = new CustomWindowsPrintConnector($printerName);
                $printer = new Printer($connector);

                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->setTextSize(1, 1);

                $printer->text(strtoupper($config->business_name) . "\n");
                $printer->setTextSize(1, 1);
                $printer->text("Historial de Pagos\n\n");

                // Determine widths based on configuration
                $widthConfig = $printerWidth;

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



                $totalReturnsUSD = 0;
                $returns = $sale->returns->where('refund_method', 'debt_reduction')->where('status', 'approved');
                foreach ($returns as $return) {
                    $date = Carbon::parse($return->created_at)->format('d/m/y');
                    $amount = number_format($return->total_returned, 2);
                    $method = 'Nota Credito';
                    
                    $amountStr = $amount . " USD";

                    $printer->text("$date  $method\n");
                    $printer->setJustification(Printer::JUSTIFY_RIGHT);
                    $printer->text("$amountStr\n");
                    $printer->setJustification(Printer::JUSTIFY_LEFT);

                    $rate = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;
                    $amountUSD = $return->total_returned / $rate;
                    $totalReturnsUSD += $amountUSD;
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

                $balanceUSD = $totalSaleUSD - $totalPaidUSD - $totalReturnsUSD;
                if($balanceUSD < 0) $balanceUSD = 0;
                
                // Convert to System Currency (Primary)
                $primaryRate = $primaryCurrency ? $primaryCurrency->exchange_rate : 1;
                $totalPaidSystem = $totalPaidUSD * $primaryRate;
                $totalReturnsSystem = $totalReturnsUSD * $primaryRate;
                $balanceSystem = $balanceUSD * $primaryRate;

                $printer->setJustification(Printer::JUSTIFY_RIGHT);
                $printer->text("Total Venta (USD): $" . number_format($totalSaleUSD, 2) . "\n");
                $printer->text("Total Abonado (USD): $" . number_format($totalPaidUSD, 2) . "\n");
                if ($totalReturnsUSD > 0) {
                     $printer->text("Notas de Credito (USD): $" . number_format($totalReturnsUSD, 2) . "\n");
                }
                $printer->text("Saldo Pendiente (USD): $" . number_format($balanceUSD, 2) . "\n");
                
                $printer->text("\n");
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
            $this->dispatch('noty', msg: 'ERROR IMPRIMIENDO HISTORIAL: ' . $th->getMessage());
        }
    }
    function printInternalTicket($saleId)
    {
        try {
            $printConfig = $this->getPrinterConfig();

            if ($printConfig) {
                $config = $printConfig['config'];
                $printerName = $printConfig['printerName'];
                $printerWidth = $printConfig['printerWidth'];

                $sale = Sale::with(['customer', 'user', 'details', 'details.product'])->find($saleId);

                $connector = new CustomWindowsPrintConnector($printerName);
                $printer = new Printer($connector);

                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->setTextSize(1, 1);

                // Header
                $printer->text("*** COMPROBANTE CONTABLE INTERNO ***\n");
                $printer->text("(NO ENTREGAR AL CLIENTE)\n\n");
                
                $printer->setJustification(Printer::JUSTIFY_LEFT);
                $printer->text("Folio: " . ($sale->invoice_number ?? $sale->id) . "\n");
                $printer->text("Fecha: " . Carbon::parse($sale->created_at)->format('d/m/Y h:i A') . "\n");
                $printer->text("Vendedor: " . $sale->user->name . "\n");
                $printer->text("Cliente: " . $sale->customer->name . "\n");
                
                // Calculate percentages
                $commPercent = $sale->applied_commission_percent ?? 0;
                $diffPercent = $sale->applied_exchange_diff_percent ?? 0;
                $freightPercent = $sale->applied_freight_percent ?? 0;
                
                $combinedPercent = ($commPercent + $diffPercent) / 100;

                // Separator
                $widthConfig = $printerWidth;
                $is58mm = $widthConfig === '58mm';
                $separator = $is58mm ? "--------------------------------" : "=============================================";
                
                $printer->text($separator . "\n");
                
                // Table Header
                if ($is58mm) {
                    // 58mm: Desc line, then details
                    $printer->text("DESCRIPCION\n");
                    $printer->text("CANT   P.BASE    T.BASE\n");
                } else {
                    // 80mm: Desc(18) Cant(5) Unit(9) Total(9) ~ 41 chars
                    $maskHead = "%-18.18s %-5.5s %-9.9s %-9.9s"; 
                    $printer->text(sprintf($maskHead, 'DESCRIPCION', 'CANT', 'P.BASE', 'T.BASE') . "\n");
                }
                $printer->text($separator . "\n");

                $totalBase = 0;
                $currencySymbol = '$'; 
                if ($sale->primary_currency_code) {
                    $currencySymbol = \App\Helpers\CurrencyHelper::getSymbol($sale->primary_currency_code);
                }

                // Calculations
                $totalFreightAmount = $sale->details->sum('freight_amount');
                
                // Split Freight Logic
                $configFreightTotal = $sale->details->filter(function($d) {
                    return in_array($d->product->freight_type, ['global', 'none']);
                })->sum('freight_amount');

                $productFreightTotal = $sale->details->filter(function($d) {
                    return !in_array($d->product->freight_type, ['global', 'none']);
                })->sum('freight_amount');
                
                foreach ($sale->details as $item) {
                     $qty = $item->quantity;
                     $finalImporte = $item->quantity * $item->sale_price;
                     $itemFreight = $item->freight_amount;
                     
                     // Reverse Calculation
                     $cleanTotal = max(0, $finalImporte - $itemFreight);
                     $itemTotalBase = $cleanTotal / (1 + $combinedPercent);
                     $baseUnit = ($qty > 0) ? ($itemTotalBase / $qty) : 0;
                     
                     $totalBase += $itemTotalBase;

                     // Print Item
                     $pName = $item->product->name;
                     $pQty = number_format($qty, 2);
                     $pBase = number_format($baseUnit, 2);
                     $pTotal = number_format($itemTotalBase, 2);

                     if ($is58mm) {
                         $printer->text($pName . "\n");
                         $printer->setJustification(Printer::JUSTIFY_RIGHT);
                         $printer->text("$pQty x $pBase = $pTotal\n");
                         $printer->setJustification(Printer::JUSTIFY_LEFT);
                     } else {
                         // 80mm
                         // If name is long, split it?
                         $maskRow = "%-18.18s %-5.5s %-9.9s %-9.9s";
                         $descParts = $this->cortar($pName, 18);
                         
                         $row = sprintf($maskRow, $descParts[0], $pQty, $pBase, $pTotal);
                         $printer->text($row . "\n");
                         
                         if (isset($descParts[1])) {
                             $printer->text(sprintf("%-18.18s", $descParts[1]) . "\n");
                         }
                     }
                }

                $printer->text($separator . "\n");
                
                // Subtotal Base
                $printer->setJustification(Printer::JUSTIFY_RIGHT);
                $printer->text("SUBTOTAL BASE: " . $currencySymbol . number_format($totalBase, 2) . "\n");
                $printer->text($separator . "\n");

                // Cargos Adicionales
                $printer->setJustification(Printer::JUSTIFY_LEFT);
                $printer->text("DESGLOSE CARGOS:\n");
                $printer->setJustification(Printer::JUSTIFY_RIGHT);
                
                if ($commPercent > 0) {
                     $amt = $totalBase * ($commPercent / 100);
                     $printer->text("Comision (" . number_format($commPercent, 2) . "%): " . $currencySymbol . number_format($amt, 2) . "\n");
                }
                
                if ($configFreightTotal > 0) {
                     $printer->text("Flete (Config " . number_format($freightPercent, 2) . "%): " . $currencySymbol . number_format($configFreightTotal, 2) . "\n");
                }

                if ($productFreightTotal > 0) {
                     $printer->text("Flete (Productos): " . $currencySymbol . number_format($productFreightTotal, 2) . "\n");
                }

                if ($diffPercent > 0) {
                     $amt = $totalBase * ($diffPercent / 100);
                     $printer->text("Dif. Cambiaria (" . number_format($diffPercent, 2) . "%): " . $currencySymbol . number_format($amt, 2) . "\n");
                }

                $printer->text($separator . "\n");
                
                // Total Facturado
                $printer->setTextSize(1, 2); // Larger font for total
                $printer->text("TOTAL: " . $currencySymbol . number_format($sale->total, 2) . "\n");
                $printer->setTextSize(1, 1);
                $printer->setJustification(Printer::JUSTIFY_LEFT);

                $printer->feed(3);
                $printer->cut();
                $printer->close();
            } else {
                Log::info("Configuración de impresora no encontrada");
            }
        } catch (\Exception $th) {
            Log::error("Error printing internal ticket: " . $th->getMessage());
            $this->dispatch('noty', msg: 'ERROR IMPRIMIENDO TICKET INTERNO: ' . $th->getMessage());
        }
    }
}

