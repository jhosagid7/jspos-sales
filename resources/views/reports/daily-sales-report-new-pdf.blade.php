<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ventas Diarias</title>
    <style>
        @page {
            margin: 0.5cm;
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
            padding: 0px 3px;
            border-bottom: 1px solid #ddd;
            font-size: 7.2pt;
            vertical-align: middle;
            height: 14px;
            white-space: nowrap;
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
            max-width: 250px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
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
            margin-top: 30px;
        }
        .signature-box {
            width: 45%;
            text-align: center;
            display: inline-block;
            vertical-align: top;
        }
        .signature-line {
            border-top: 1px solid #000;
            width: 200px;
            margin: 0 auto 5px auto;
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
        Moneda de Referencia : Dólares
    </div>


    {{-- Summary Block --}}
    <div class="summary-block">
        <table class="summary-table">
            <tr>
                <td width="50%" style="vertical-align: top; border-right: 1px solid #ccc;">
                    <span class="summary-section-title">CIERRE DE OPERACIONES DE FACTURAS</span>
                    <table width="100%">
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
                        <tr>
                            <td class="summary-label">Total Transacciones :</td>
                            <td class="summary-value">{{ number_format($summary['total_count'], 4) }}</td>
                        </tr>
                        <tr>
                            <td class="summary-label">Total Exento :</td>
                            <td class="summary-value">0.0000</td>
                        </tr>
                        <tr style="border-top: 1px solid #eee;">
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
                        <tr style="border-top: 1px solid #eee;">
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
                <th>Documento</th>
                <th>Descripción</th>
                <th class="text-right">Monto Neto</th>
                <th class="text-right">Impuestos</th>
                <th class="text-right">Contado</th>
                <th class="text-right">Crédito</th>
                <th class="text-right">Bolívares</th>
                <th class="text-right">Divisas</th>
            </tr>
        </thead>
        <tbody>
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
                        $paidToday = 0;
                        $vedPaid = 0;
                        $divisaPaid = 0;

                        foreach($sale->paymentDetails as $payment) {
                            $rate = $payment->exchange_rate > 0 ? $payment->exchange_rate : 1;
                            $amtUSD = $payment->amount / $rate;
                            $paidToday += $amtUSD;
                            
                            if($payment->currency_code == 'VED' || $payment->currency_code == 'VES') {
                                $vedPaid += $amtUSD;
                            } else {
                                $divisaPaid += $amtUSD;
                            }
                        }
                        
                        // Handle raw cash sales (no payment details record)
                        if($sale->paymentDetails->count() == 0 && $sale->type == 'cash') {
                            $rate = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;
                            $amtUSD = $sale->cash / $rate;
                            $paidToday += $amtUSD;
                            if($sale->primary_currency_code == 'VED' || $sale->primary_currency_code == 'VES') {
                                $vedPaid += $amtUSD;
                            } else {
                                $divisaPaid += $amtUSD;
                            }
                        }

                        $creditUSD = 0;
                        if($sale->status != 'paid') {
                            $creditUSD = max(0, $sale->total_usd - $paidToday);
                        }
                    @endphp
                    <tr>
                        <td>{{ $sale->invoice_number ?? $sale->id }}</td>
                        <td>
                            <span class="desc-text">{{ strtoupper($sale->customer->name) }} ({{ $sale->customer->taxpayer_id }})</span>
                            @foreach($sale->paymentDetails as $payment)
                                @if(in_array($payment->method, ['bank', 'zelle', 'deposit']))
                                     <span class="pay-info"> | {{ $payment->method == 'zelle' ? 'Zelle' : ($payment->bank->name ?? 'Banco') }}: {{ $payment->reference }}</span>
                                @endif
                            @endforeach
                        </td>
                        <td class="text-right">{{ number_format($sale->total_usd, 4) }}</td>
                        <td class="text-right">0.0000</td>
                        <td class="text-right">{{ number_format($paidToday, 4) }}</td>
                        <td class="text-right">{{ $creditUSD > 0.0001 ? number_format($creditUSD, 4) : '0.0000' }}</td>
                        <td class="text-right">{{ number_format($vedPaid, 4) }}</td>
                        <td class="text-right">{{ number_format($divisaPaid, 4) }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
        <tfoot>
            <tr style="border-top: 2px solid #000; font-weight: bold;">
                <td colspan="2" class="text-right">TOTALES:</td>
                <td class="text-right">{{ number_format($summary['total_bruto'], 4) }}</td>
                <td class="text-right">0.0000</td>
                <td class="text-right">{{ number_format($summary['total_contado'], 4) }}</td>
                <td class="text-right">{{ number_format($summary['total_credito'], 4) }}</td>
                <td class="text-right">{{ number_format($summary['total_ved'], 4) }}</td>
                <td class="text-right">{{ number_format($summary['total_divisa'], 4) }}</td>
            </tr>
        </tfoot>
    </table>

    @if(count($returns) > 0)
    <div style="font-weight: bold; font-size: 10pt; margin-bottom: 5px; margin-top: 15px;">Notas de Crédito (Devoluciones)</div>
    <table class="table">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>NC Número</th>
                <th>Factura Orig.</th>
                <th>Cliente</th>
                <th class="text-right">Monto Devuelto (USD)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($returns as $return)
                <tr>
                    <td>{{ $return->created_at->format('d/m/Y') }}</td>
                    <td>{{ $return->id }}</td>
                    <td>{{ $return->sale->invoice_number ?? $return->sale_id }}</td>
                    <td>{{ strtoupper($return->sale->customer->name ?? 'N/A') }}</td>
                    <td class="text-right">
                        @php $rate = ($return->sale && $return->sale->primary_exchange_rate > 0) ? $return->sale->primary_exchange_rate : 1; @endphp
                        {{ number_format($return->total_returned / $rate, 4) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="border-top: 2px solid #000; font-weight: bold;">
                <td colspan="4" class="text-right">TOTAL NC:</td>
                <td class="text-right">{{ number_format($totalsByCategory['NOTAS DE CREDITO (NC)'], 4) }}</td>
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

    <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
        <tr>
            {{-- Column 1: Income Breakdown (USD) --}}
            <td style="width: 35%; vertical-align: top;">
                <table style="width: 100%; font-size: 7.5pt;">
                    @php $grandTotalIncomeUSD = 0; @endphp
                    @foreach($totalsByCategory as $category => $total)
                        @if($total > 0 || str_contains($category, 'EFECTIVO'))
                        <tr>
                            <td style="padding: 1px 0;">{{ strtoupper($category) }}:</td>
                            <td class="text-right" style="padding: 1px 5px;">{{ number_format($total, 4) }}</td>
                        </tr>
                        @php $grandTotalIncomeUSD += $total; @endphp
                        @endif
                    @endforeach
                    <tr style="border-top: 1px solid #000; font-weight: bold;">
                        <td style="padding-top: 3px;">Total Ingreso:</td>
                        <td class="text-right" style="padding: 3px 5px;">{{ number_format($grandTotalIncomeUSD, 4) }}</td>
                    </tr>
                </table>
            </td>

            {{-- Column 2: Details by Currency --}}
            <td style="width: 30%; vertical-align: top; padding-left: 10px;">
                <div style="background-color: #f0f0f0; padding: 2px; text-align: center; border: 1px solid #ccc; font-weight: bold; margin-bottom: 5px;">
                    Detalle por Moneda
                </div>
                <table style="width: 100%; font-size: 7.5pt;">
                    @foreach($totalsByCurrency as $currCode => $amount)
                        @if($amount > 0)
                        <tr>
                            <td>Total {{ $currCode }}:</td>
                            <td class="text-right">{{ number_format($amount, 2) }}</td>
                        </tr>
                        @endif
                    @endforeach
                </table>
            </td>

            {{-- Column 3: Signatures --}}
            <td style="width: 35%; vertical-align: top; text-align: right; padding-left: 20px;">
                <div style="margin-top: 10px;">
                    <div style="border-top: 1px solid #000; width: 180px; margin-left: auto; text-align: center; font-size: 7.5pt;">
                        <strong>ENTREGADO POR</strong><br>(OPERADOR)
                    </div>
                </div>
                <div style="margin-top: 40px;">
                    <div style="border-top: 1px solid #000; width: 180px; margin-left: auto; text-align: center; font-size: 7.5pt;">
                        <strong>RECIBIDO POR</strong>
                    </div>
                </div>
            </td>
        </tr>
    </table>

</body>
</html>
