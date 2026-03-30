<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ventas Diarias</title>
    <style>
        @page {
            margin: 0.3cm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 8pt;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header {
            width: 100%;
            margin-bottom: 5px;
        }
        .header td {
            vertical-align: top;
        }
        .business-info h2 {
            margin: 0;
            font-size: 12pt;
        }
        .report-info {
            text-align: right;
            font-size: 8pt;
        }
        .report-title {
            text-align: center;
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 10px;
            text-decoration: underline;
        }
        
        /* Summary Table Style */
        .summary-block {
            width: 100%;
            margin-bottom: 15px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 5px 0;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-table td {
            padding: 1px 5px;
            font-size: 7.5pt;
        }
        .summary-label {
            font-weight: normal;
        }
        .summary-value {
            text-align: right;
            font-weight: bold;
        }
        .summary-section-title {
            font-weight: bold;
            text-decoration: underline;
            padding-bottom: 3px;
            display: block;
        }

        /* Main Table Style */
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table th {
            background-color: #e0e0e0;
            border: 1px solid #999;
            padding: 3px;
            text-align: left;
            font-size: 7.5pt;
        }
        .table td {
            padding: 2px 3px;
            border-bottom: 1px solid #ddd;
            font-size: 7.2pt;
            vertical-align: top;
        }
        .table th {
            background-color: #f2f2f2;
            border: 1px solid #999;
            padding: 2px 3px;
            text-align: left;
            font-size: 7.5pt;
            white-space: nowrap;
        }
        .desc-text {
            display: inline-block;
            max-width: 100%;
            white-space: normal;
        }
        .pay-info {
            font-size: 6.5pt;
            color: #555;
            font-weight: normal;
        }
        .customer-header {
            background-color: #f0f0f0;
            font-weight: bold;
            padding: 3px;
            border: 1px solid #ccc;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
        .footer-signatures {
            width: 100%;
            margin-top: 15px;
        }
        .signature-box {
            width: 100%;
            text-align: center;
            margin-bottom: 20px;
        }
        .signature-line {
            border-top: 1px solid #333;
            width: 80%;
            margin: 0 auto 5px auto;
        }
        .summary-label-bold {
            font-weight: bold;
            font-size: 7.5pt;
        }
        .currency-header {
            background-color: #f0f0f0;
            text-align: center;
            font-weight: bold;
            padding: 2px;
            border: 1px solid #ccc;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>

    <table class="header">
        <tr>
            <td class="business-info" width="60%">
                <h2>{{ $config->business_name }}</h2>
                <div style="font-size: 8pt;">
                    {{ $config->address }}<br>
                    TELÉFONOS: {{ $config->phone }}<br>
                    RIF: {{ $config->taxpayer_id }}
                </div>
            </td>
            <td class="report-info">
                Fecha : {{ \Carbon\Carbon::now()->format('d/m/Y') }}<br>
                Hora : {{ \Carbon\Carbon::now()->format('h:i:s a') }}<br>
                Pág : 1
            </td>
        </tr>
    </table>

    <div class="report-title">REPORTE DE VENTAS DIARIAS</div>
    
    <div style="margin-bottom: 10px; font-size: 8pt;">
        @if($dateFrom && $dateTo)
            Periodo : {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}
        @else
            Fecha : {{ \Carbon\Carbon::now()->format('d/m/Y') }}
        @endif
        <br>
        Moneda de Referencia : Dólares<br>
        Operador : {{ strtoupper($user->name ?? 'N/A') }}
    </div>


    {{-- Summary Block --}}
    <div class="summary-block">
        <table class="summary-table">
            <tr>
                <td width="50%" style="vertical-align: top; border-right: 1px solid #ccc;">
                    <span class="summary-section-title">CIERRE DE OPERACIONES DE FACTURAS</span>
                    <table width="100%">
                        <tr>
                            <td class="summary-label">Total Facturas Procesadas :</td>
                            <td class="summary-value">{{ number_format($summary['total_count'], 0) }}</td>
                        </tr>
                        <tr>
                            <td class="summary-label">Total Facturas Eliminadas :</td>
                            <td class="summary-value">{{ count($deletedSales ?? []) }}</td>
                        </tr>
                        <tr>
                            <td class="summary-label">Total Ventas Brutas :</td>
                            <td class="summary-value">{{ number_format($summary['total_bruto'], 4) }}</td>
                        </tr>
                        <tr>
                            <td class="summary-label">Total Flete :</td>
                            <td class="summary-value">{{ number_format($summary['total_flete'], 4) }}</td>
                        </tr>
                        <tr>
                            <td class="summary-label">Total Contado :</td>
                            <td class="summary-value">{{ number_format($summary['total_contado'], 4) }}</td>
                        </tr>
                        <tr>
                            <td class="summary-label">Total Crédito en Operaciones :</td>
                            <td class="summary-value">{{ number_format($summary['total_credito'], 4) }}</td>
                        </tr>
                        <tr style="border-top: 1.5pt solid #000;">
                            <td class="summary-label">Total :</td>
                            <td class="summary-value">{{ number_format($summary['total_bruto'], 4) }}</td>
                        </tr>
                    </table>
                </td>
                <td width="50%" style="vertical-align: top; padding-left: 15px;">
                    <div style="height: 15px;"></div> {{-- Spacer --}}
                    <table width="100%">
                        <tr>
                            <td class="summary-label">Total VED Pasado a USD :</td>
                            <td class="summary-value">{{ number_format($summary['total_ved'], 4) }}</td>
                        </tr>
                        <tr>
                            <td class="summary-label">Total Divisas Pasado a USD :</td>
                            <td class="summary-value">{{ number_format($summary['total_divisa'], 4) }}</td>
                        </tr>
                        <tr style="border-top: 1.5pt solid #000;">
                            <td class="summary-label">Total Ingresos :</td>
                            <td class="summary-value">{{ number_format($summary['total_contado'], 4) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <div style="font-weight: bold; font-size: 10pt; margin-bottom: 5px;">Transacciones del día</div>

    <table class="table">
        <thead>
            <tr>
                <th style="width: 50px;">Documento</th>
                <th style="width: auto;">Descripción</th>
                <th style="width: 65px;" class="text-right">Monto Neto</th>
                <th style="width: 50px;" class="text-right">Impuestos</th>
                <th style="width: 65px;" class="text-right">Contado</th>
                <th style="width: 65px;" class="text-right">Crédito</th>
                <th style="width: 65px;" class="text-right">Bolívares</th>
                <th style="width: 65px;" class="text-right">Pesos</th>
                <th style="width: 65px;" class="text-right">Divisas</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $grandRawVed = 0;
                $grandRawCop = 0;
                $grandTotalNeto = 0;
                $grandTotalPaid = 0;
                $grandTotalCredit = 0;
                $grandTotalDivisa = 0;
            @endphp
            @foreach ($data as $key => $groupData)
                @if($groupBy != 'none')
                    <tr>
                        <td colspan="8" class="customer-header">
                            {{ strtoupper($groupData['name']) }}
                        </td>
                    </tr>
                @endif

                @foreach ($groupData['sales'] as $sale)
                    @php
                        $totalReturnedUSD = 0;
                        foreach($sale->returns as $return) {
                            if($return->status == 'approved') {
                                $rate = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;
                                $totalReturnedUSD += ($return->total_returned / $rate);
                            }
                        }
                        $netSaleUSD = $sale->total_usd - $totalReturnedUSD;
                        // 2. Calcular Pagos Netos (Pagos - Vueltos)
                        $paidToday = 0;
                        $vedPaid = 0; // Raw VED
                        $copPaid = 0; // Raw COP
                        $divisaPaid = 0; // Netted USD Divisa

                        // Sumar pagos
                        foreach($sale->paymentDetails as $payment) {
                            $rate = $payment->exchange_rate > 0 ? $payment->exchange_rate : 1;
                            $amtUSD = $payment->amount / $rate;
                            $paidToday += $amtUSD;
                            
                            $curr = strtoupper($payment->currency_code);
                            if($curr == 'VED' || $curr == 'VES') {
                                $vedPaid += $payment->amount; 
                            } elseif($curr == 'COP') {
                                $copPaid += $payment->amount;
                            } else {
                                $divisaPaid += $amtUSD;
                            }
                        }
                        
                        // Restar vueltos
                        foreach($sale->changeDetails as $change) {
                            $rateC = $change->exchange_rate > 0 ? $change->exchange_rate : 1;
                            $amtUSD_C = $change->amount / $rateC;
                            $paidToday -= $amtUSD_C;

                            $currC = strtoupper($change->currency_code);
                            if($currC == 'VED' || $currC == 'VES') {
                                $vedPaid -= $change->amount;
                            } elseif($currC == 'COP') {
                                $copPaid -= $change->amount;
                            } else {
                                $divisaPaid -= $amtUSD_C;
                            }
                        }
                        
                        // Net total cash sales (fallback)
                        if($sale->paymentDetails->count() == 0 && $sale->type == 'cash') {
                            $rate = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;
                            $netCash = $sale->cash - $sale->change;
                            $amtUSD = $netCash / $rate;
                            $paidToday += $amtUSD;
                            $currS = strtoupper($sale->primary_currency_code);
                            if($currS == 'VED' || $currS == 'VES') {
                                $vedPaid += $netCash;
                            } elseif($currS == 'COP') {
                                $copPaid += $netCash;
                            } else {
                                $divisaPaid += $amtUSD;
                            }
                        }
                        
                        $paidToday = $paidToday - $totalReturnedUSD;
                        $divisaPaid = $divisaPaid - $totalReturnedUSD;

                        $grandRawVed += $vedPaid;

                        // 3. Crédito sobre el monto NETO
                        $creditUSD = 0;
                        if($sale->status != 'paid' && $sale->status != 'returned') {
                            $creditUSD = max(0, $netSaleUSD - $paidToday);
                        }

                        // Acumuladores del Pie de Página
                        $grandTotalNeto += $netSaleUSD;
                        $grandTotalPaid += $paidToday;
                        $grandTotalCredit += $creditUSD;
                        $grandTotalDivisa += $divisaPaid;
                        $grandRawCop += $copPaid; // Added COP accumulator
                    @endphp
                    <tr>
                        <td>{{ $sale->invoice_number ?? $sale->id }}</td>
                        <td style="white-space: normal;">
                            <div class="desc-text" style="font-weight: bold; border-bottom: 1px dashed #eee; display: block; max-width: none;">{{ strtoupper($sale->customer->name) }} ({{ $sale->customer->taxpayer_id }})</div>
                            @foreach($sale->paymentDetails as $payment)
                                @if(in_array($payment->payment_method, ['bank', 'zelle', 'deposit']))
                                     @php
                                         $rate = $payment->exchange_rate > 0 ? $payment->exchange_rate : 1;
                                         $usdEquiv = $payment->amount / $rate;
                                     @endphp
                                     <div class="pay-info" style="display: block; border-left: 2px solid #ddd; padding-left: 3px; margin-top: 1px;">
                                         {{ $payment->payment_method == 'zelle' ? 'Zelle' : ($payment->bank_name ?? 'Banco') }}: {{ $payment->reference_number }} 
                                         <span style="color: #888;">(Tasa: {{ number_format($payment->exchange_rate, 4) }})</span> 
                                         <span style="font-weight: bold;">[${{ number_format($usdEquiv, 4) }}]</span>
                                     </div>
                                @endif
                            @endforeach
                        </td>
                        <td class="text-right">{{ number_format($netSaleUSD, 4) }}</td>
                        <td class="text-right">0.0000</td>
                        <td class="text-right">{{ number_format($paidToday, 4) }}</td>
                        <td class="text-right">{{ $creditUSD > 0.0001 ? number_format($creditUSD, 4) : '0.0000' }}</td>
                        <td class="text-right">{{ number_format($vedPaid, 4) }}</td>
                        <td class="text-right">{{ number_format($copPaid, 4) }}</td>
                        <td class="text-right">{{ number_format($divisaPaid, 4) }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
        <tfoot>
            <tr style="border-top: 2px solid #000; font-weight: bold;">
                <td colspan="2" class="text-right">TOTALES:</td>
                <td class="text-right">{{ number_format($grandTotalNeto, 4) }}</td>
                <td class="text-right">0.0000</td>
                <td class="text-right">{{ number_format($grandTotalPaid, 4) }}</td>
                <td class="text-right">{{ number_format($grandTotalCredit, 4) }}</td>
                <td class="text-right">{{ number_format($grandRawVed, 4) }}</td>
                <td class="text-right">{{ number_format($grandRawCop, 4) }}</td>
                <td class="text-right">{{ number_format($grandTotalDivisa, 4) }}</td>
            </tr>
        </tfoot>
    </table>

    @if(count($returns) > 0)
    <div style="font-weight: bold; font-size: 10pt; margin-bottom: 5px; margin-top: 10px;">Notas de Crédito (Devoluciones)</div>
    <table class="table">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>NC Número</th>
                <th>Factura Orig.</th>
                <th>Cliente</th>
                <th class="text-right">Monto (USD)</th>
                <th>Solicitante</th>
                <th>Aprobador</th>
                <th>Motivo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($returns as $return)
                <tr>
                    <td>{{ $return->created_at->format('d/m/Y') }}</td>
                    <td>{{ $return->id }}</td>
                    <td>{{ $return->sale->invoice_number ?? $return->sale_id }}</td>
                    <td><span class="desc-text">{{ strtoupper($return->customer->name ?? $return->sale->customer->name ?? 'N/A') }}</span></td>
                    <td class="text-right">
                        @php $rate = ($return->sale && $return->sale->primary_exchange_rate > 0) ? $return->sale->primary_exchange_rate : 1; @endphp
                        {{ number_format($return->total_returned / $rate, 4) }}
                    </td>
                    <td><span class="desc-text">{{ $return->requester->name ?? 'N/A' }}</span></td>
                    <td><span class="desc-text">{{ $return->approver->name ?? 'N/A' }}</span></td>
                    <td><span class="desc-text">{{ $return->reason }}</span></td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="border-top: 2px solid #000; font-weight: bold;">
                <td colspan="4" class="text-right">TOTAL NC:</td>
                <td class="text-right">{{ number_format($summary['total_nc_raw'] ?? 0, 4) }}</td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
    </table>
    @endif

    @if(count($deletedSales) > 0)
    <div style="font-weight: bold; font-size: 10pt; margin-bottom: 5px; margin-top: 15px;">Facturas Eliminadas del Día</div>
    <table class="table">
        <thead>
            <tr>
                <th>Fecha Elim.</th>
                <th>Documento</th>
                <th>Cliente</th>
                <th>Monto (USD)</th>
                <th>Solicitado por</th>
                <th>Aprobado por</th>
                <th>Motivo de Eliminación</th>
            </tr>
        </thead>
        <tbody>
            @foreach($deletedSales as $ds)
                <tr>
                    <td>{{ $ds->deletion_approved_at->format('d/m/Y h:i a') }}</td>
                    <td>{{ $ds->invoice_number ?? $ds->id }}</td>
                    <td><span class="desc-text">{{ strtoupper($ds->customer->name ?? 'N/A') }}</span></td>
                    <td class="text-right">{{ number_format($ds->total_usd, 4) }}</td>
                    <td><span class="desc-text">{{ $ds->requester->name ?? 'N/A' }}</span></td>
                    <td><span class="desc-text">{{ $ds->approver->name ?? 'N/A' }}</span></td>
                    <td><span class="desc-text">{{ $ds->deletion_reason ?? 'No especificado' }}</span></td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
        <tr>
            {{-- Column 1: Income Breakdown (USD) --}}
            <td style="width: 32%; vertical-align: top;">
                <table style="width: 100%; font-size: 7.5pt;">
                @php
                    $salesSubtotal  = 0;
                    $walletSubtotal = 0;
                    $salesItems     = [];
                    $walletItems    = [];

                    foreach ($totalsByCategory as $category => $total) {
                        $isWallet = str_contains(strtoupper($category), 'BILLETERA') || str_contains(strtoupper($category), 'PAGO BILLETERA');
                        if ($isWallet) {
                            $walletItems[$category] = $total;
                            if (str_contains(strtoupper($category), 'PAGO BILLETERA')) {
                                $walletSubtotal -= $total;
                            } else {
                                $walletSubtotal += $total;
                            }
                        } else {
                            $salesItems[$category] = $total;
                            $salesSubtotal += $total;
                        }
                    }
                    // $grandTotalIncomeUSD is now passed from Controller correctly.
                @endphp

                {{-- === SECCIÓN VENTAS === --}}
                <table style="width: 100%; font-size: 7.5pt;">
                    <tr>
                        <td colspan="2" style="font-weight: bold; font-size: 7pt; border-bottom: 1pt solid #555; padding-bottom: 2px; padding-top: 4px; color: #333;">
                            -- VENTAS
                        </td>
                    </tr>
                    @foreach($salesItems as $category => $total)
                        <tr>
                            <td class="summary-label-bold" style="padding: 1px 0;">{{ strtoupper($category) }}:</td>
                            <td class="text-right" style="padding: 1px 5px; font-weight: normal;">{{ number_format($total, 4) }}</td>
                        </tr>
                    @endforeach
                    <tr style="border-top: 1pt solid #aaa;">
                        <td class="summary-label-bold" style="padding-top: 2px;">Subtotal Ventas:</td>
                        <td class="text-right" style="padding-top: 2px; padding-right: 5px; font-weight: bold;">{{ number_format($salesSubtotal, 4) }}</td>
                    </tr>
                </table>

                @if(count($walletItems) > 0)
                {{-- === SECCIÓN BILLETERA === --}}
                <table style="width: 100%; font-size: 7.5pt; margin-top: 6px;">
                    <tr>
                        <td colspan="2" style="font-weight: bold; font-size: 7pt; border-bottom: 1pt solid #555; padding-bottom: 2px; padding-top: 4px; color: #555;">
                            -- BILLETERA (CLIENTE)
                        </td>
                    </tr>
                    @foreach($walletItems as $category => $total)
                        @php $isPago = str_contains(strtoupper($category), 'PAGO BILLETERA'); @endphp
                        <tr>
                            <td class="summary-label-bold" style="padding: 1px 0;">{{ strtoupper($category) }}:</td>
                            <td class="text-right" style="padding: 1px 5px; font-weight: normal;">
                                {{ $isPago ? '-' : '+' }}{{ number_format($total, 4) }}
                            </td>
                        </tr>
                    @endforeach
                    <tr style="border-top: 1pt solid #aaa; background-color: #fcfcfc;">
                        <td class="summary-label-bold" style="padding-top: 2px;">Saldo Billetera Consumido (Anterior):</td>
                        <td class="text-right" style="padding-top: 2px; padding-right: 5px; font-weight: bold; color: #b30000;">{{ number_format($walletSubtotal, 4) }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" style="font-size: 5.5pt; color: #777; font-style: italic; text-align: right; padding-right: 5px;">
                            * Este monto YA fue entregado en fechas anteriores.
                        </td>
                    </tr>
                </table>
                @endif

                {{-- TOTAL FINAL --}}
                <table style="width: 100%; font-size: 7.5pt; margin-top: 4px;">
                    <tr style="border-top: 1.5pt solid #333;">
                        <td class="summary-label-bold" style="padding-top: 3px;">Total en Caja (Entregar):</td>
                        <td class="text-right" style="font-weight: bold; padding-top: 3px; padding-right: 5px;">{{ number_format($grandTotalIncomeUSD, 4) }}</td>
                    </tr>
                </table>
            </td>

            {{-- Column 2: Details by Currency --}}
            <td style="width: 23%; vertical-align: top; padding-left: 10px; padding-right: 10px;">
                <div class="currency-header">
                    Arqueo Físico por Moneda
                </div>
                <table style="width: 100%; font-size: 8pt;">
                    @foreach($totalsByCurrencyPhys as $currCode => $amount)
                        @if(abs($amount) > 0.0001)
                        <tr>
                            <td style="font-weight: bold;">Total {{ $currCode }}:</td>
                            <td class="text-right">{{ number_format($amount, 2) }}</td>
                        </tr>
                        @endif
                    @endforeach
                </table>
                <div style="font-size: 6pt; color: #666; margin-top: 5px; text-align: center;">
                    * Entregar este monto físico.
                </div>
            </td>

            {{-- Column 3: Signatures Side by Side --}}
            <td style="width: 45%; vertical-align: top; padding-left: 10px;">
                <table style="width: 100%; margin-top: 30px;">
                    <tr>
                        <td style="width: 50%; text-align: center; vertical-align: top;">
                            <div style="border-top: 1px solid #333; width: 90%; margin: 0 auto 5px auto;"></div>
                            <div style="font-size: 7.5pt; font-weight: bold;">ENTREGADO POR</div>
                            <div style="font-size: 7.5pt; font-weight: bold;">(OPERADOR)</div>
                        </td>
                        <td style="width: 50%; text-align: center; vertical-align: top;">
                            <div style="border-top: 1px solid #333; width: 90%; margin: 0 auto 5px auto;"></div>
                            <div style="font-size: 7.5pt; font-weight: bold;">RECIBIDO POR</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    </div>

</body>
</html>
