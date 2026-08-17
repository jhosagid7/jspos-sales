<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe Detallado de Caja - {{ $dateFrom }} al {{ $dateTo }}</title>
    <style>
        @page {
            margin: 1cm;
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
            margin-bottom: 10px;
        }
        .header td {
            vertical-align: top;
        }
        .business-info h2 {
            margin: 0;
            font-size: 13pt;
            text-transform: uppercase;
        }
        .business-info p {
            margin: 1px 0;
            font-size: 7.5pt;
        }
        .report-info {
            text-align: right;
            font-size: 7.5pt;
        }
        .report-title {
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 10px 0;
            border-bottom: 2px solid #333;
            padding-bottom: 4px;
            letter-spacing: 0.5px;
        }
        .info-table {
            width: 100%;
            margin-bottom: 12px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 6px;
        }
        .info-table td {
            font-size: 8pt;
            padding: 2px 0;
        }
        .section-header {
            background-color: #333;
            color: #fff;
            font-weight: bold;
            font-size: 8.5pt;
            padding: 4px 8px;
            margin-top: 15px;
            margin-bottom: 6px;
            text-transform: uppercase;
        }
        .sub-header {
            background-color: #f2f2f2;
            color: #000;
            font-weight: bold;
            font-size: 8pt;
            padding: 3px 6px;
            margin-top: 8px;
            margin-bottom: 4px;
            border-left: 3px solid #0284c7;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .table th {
            background-color: #f8f9fa;
            border-bottom: 1.5px solid #ccc;
            padding: 4px 6px;
            text-align: left;
            font-size: 7pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .table td {
            padding: 3px 6px;
            border-bottom: 1px solid #eee;
            font-size: 7pt;
            vertical-align: middle;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .fw-bold {
            font-weight: bold;
        }
        .total-row td {
            font-weight: bold;
            border-top: 1.5px solid #333;
            background-color: #fafafa;
        }
        .badge {
            display: inline-block;
            padding: 1px 4px;
            background-color: #e9ecef;
            border-radius: 3px;
            font-size: 6.5pt;
            color: #495057;
            font-weight: bold;
        }
        .badge-venta {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        .badge-credito {
            background-color: #fff3cd;
            color: #664d03;
        }
        .signature-table {
            width: 100%;
            margin-top: 40px;
            page-break-inside: avoid;
        }
        .signature-line {
            border-top: 1px solid #000;
            width: 180px;
            margin: 0 auto;
            padding-top: 4px;
            font-size: 7.5pt;
            text-align: center;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 7pt;
            color: #777;
            border-top: 1px solid #eee;
            padding-top: 6px;
        }
    </style>
</head>
<body>
    <!-- Encabezado de Empresa -->
    <table class="header">
        <tr>
            <td width="60%" class="business-info">
                <h2>{{ $config->business_name }}</h2>
                <p>{{ $config->address }}</p>
                <p>NIT: {{ $config->taxpayer_id }} | TEL: {{ $config->phone }}</p>
            </td>
            <td width="40%" class="report-info">
                <p><strong>Fecha Impresión:</strong> {{ now()->format('d/m/Y h:i a') }}</p>
                <p><strong>Página:</strong> 1 de 1</p>
            </td>
        </tr>
    </table>

    <div class="report-title">Informe Detallado de Caja</div>

    <!-- Metadatos de Consulta -->
    <table class="info-table">
        <tr>
            <td width="50%"><strong>Rango de Fechas:</strong> {{ $dateFrom }} al {{ $dateTo }}</td>
            <td width="50%" class="text-right"><strong>Moneda Principal:</strong> {{ $symbol }}</td>
        </tr>
        <tr>
            <td><strong>Cajero(s):</strong> {{ $user_name }}</td>
            <td class="text-right"><strong>Modo del Reporte:</strong> {{ $unify ? 'Unificado' : 'Separado (Ventas / Créditos)' }}</td>
        </tr>
    </table>

    <!-- MODO UNIFICADO -->
    @if($unify)
        
        <!-- EFECTIVO UNIFICADO -->
        @if($includeCash && !empty($cashDetails['unified']))
            <div class="section-header">Consolidado Global de Efectivo (CASH)</div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Moneda / Denominación</th>
                        <th class="text-right">Cantidad Física</th>
                        <th class="text-right">Total en {{ $symbol }}</th>
                    </tr>
                </thead>
                <tbody>
                    @php $subtotalCash = 0; @endphp
                    @foreach($cashDetails['unified'] as $curr => $amt)
                        @php 
                            $equiv = $convertToPrimary($amt, $curr);
                            $subtotalCash += $equiv;
                        @endphp
                        <tr>
                            <td class="fw-bold">Efectivo {{ $getLabel($curr) }}</td>
                            <td class="text-right fw-bold">{{ number_format($amt, 2) }} {{ $curr }}</td>
                            <td class="text-right fw-bold">{{ $symbol }} {{ number_format($equiv, 4) }}</td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="2" class="text-right">SUBTOTAL GLOBAL EFECTIVO EN CAJA:</td>
                        <td class="text-right">{{ $symbol }} {{ number_format($subtotalCash, 4) }}</td>
                    </tr>
                </tbody>
            </table>
        @endif

        <!-- BANCOS UNIFICADOS -->
        @if(!empty($digitalPayments['unified']['bank']))
            <div class="section-header">Conciliación Global de Bancos</div>
            @foreach($digitalPayments['unified']['bank'] as $bank => $currenciesInBank)
                <div class="sub-header">{{ strtoupper($bank) }}</div>
                @foreach($currenciesInBank as $curr => $items)
                    <table class="table">
                        <thead>
                            <tr>
                                <th width="12%">F. Voucher</th>
                                <th width="12%">Procedencia</th>
                                <th width="14%">Referencia</th>
                                <th width="12%">Factura</th>
                                <th width="26%">Cliente</th>
                                <th width="12%" class="text-right">Monto {{ $curr }}</th>
                                <th width="12%" class="text-right">Total en {{ $symbol }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php 
                                $subtotalBankCurr = 0; 
                                $subtotalBankPrimary = 0;
                            @endphp
                            @foreach($items as $item)
                                @php
                                    $subtotalBankCurr += $item['amount'];
                                    $subtotalBankPrimary += $item['equiv_usd'] * $convertToPrimary(1, 'USD'); // normalizado a principal
                                @endphp
                                <tr>
                                    <td>{{ $item['date'] }}</td>
                                    <td>
                                        <span class="badge {{ $item['origin'] === 'VENTA' ? 'badge-venta' : 'badge-credito' }}">
                                            {{ $item['origin'] }}
                                        </span>
                                    </td>
                                    <td>{{ $item['ref'] }}</td>
                                    <td class="fw-bold">{{ $item['invoice'] }}</td>
                                    <td class="fw-bold">{{ strtoupper($item['customer']) }}</td>
                                    <td class="text-right">{{ number_format($item['amount'], 2) }}</td>
                                    <td class="text-right fw-bold">{{ $symbol }} {{ number_format($item['equiv_usd'] * $convertToPrimary(1, 'USD'), 4) }}</td>
                                </tr>
                            @endforeach
                            <tr class="total-row">
                                <td colspan="5" class="text-right">TOTAL CONCILIADO {{ strtoupper($bank) }} ({{ $curr }}):</td>
                                <td class="text-right">{{ number_format($subtotalBankCurr, 2) }}</td>
                                <td class="text-right">{{ $symbol }} {{ number_format($subtotalBankPrimary, 4) }}</td>
                            </tr>
                        </tbody>
                    </table>
                @endforeach
            @endforeach
        @endif

        <!-- ZELLE UNIFICADO -->
        @if(!empty($digitalPayments['unified']['zelle']))
            <div class="section-header">Conciliación Global de Zelle (USD)</div>
            <table class="table">
                <thead>
                    <tr>
                        <th width="12%">F. Voucher</th>
                        <th width="18%">Quien Envía</th>
                        <th width="14%">Referencia</th>
                        <th width="12%">Factura</th>
                        <th width="18%">Cliente</th>
                        <th width="13%" class="text-right">Monto Zelle</th>
                        <th width="13%" class="text-right">Monto Usado</th>
                    </tr>
                </thead>
                <tbody>
                    @php 
                        $subtotalZelleCurr = 0; 
                        $subtotalZellePrimary = 0;
                    @endphp
                    @foreach($digitalPayments['unified']['zelle'] as $item)
                        @php
                            $subtotalZelleCurr += $item['amount'];
                            $subtotalZellePrimary += $item['equiv_usd'] * $convertToPrimary(1, 'USD');
                        @endphp
                        <tr>
                            <td>{{ $item['date'] }}</td>
                            <td class="fw-bold">{{ strtoupper($item['zelle_sender']) }}</td>
                            <td>{{ $item['ref'] }}</td>
                            <td class="fw-bold">{{ $item['invoice'] }}</td>
                            <td class="fw-bold">{{ strtoupper($item['customer']) }}</td>
                            <td class="text-right fw-bold">${{ number_format($item['zelle_total'], 2) }}</td>
                            <td class="text-right fw-bold">${{ number_format($item['amount'], 2) }}</td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="5" class="text-right">TOTAL CONCILIADO ZELLE (USD):</td>
                        <td class="text-right">${{ number_format($subtotalZelleCurr, 2) }}</td>
                        <td class="text-right">{{ $symbol }} {{ number_format($subtotalZellePrimary, 4) }}</td>
                    </tr>
                </tbody>
            </table>
        @endif

        <!-- USDT UNIFICADO -->
        @if(!empty($digitalPayments['unified']['usdt']))
            <div class="section-header">Conciliación Global de USDT ($)</div>
            <table class="table">
                <thead>
                    <tr>
                        <th width="12%">F. Voucher</th>
                        <th width="18%">Billetera / Emisor</th>
                        <th width="14%">TxID Referencia</th>
                        <th width="12%">Factura</th>
                        <th width="18%">Cliente</th>
                        <th width="13%" class="text-right">Monto USDT</th>
                        <th width="13%" class="text-right">Monto Usado</th>
                    </tr>
                </thead>
                <tbody>
                    @php 
                        $subtotalUsdtCurr = 0; 
                        $subtotalUsdtPrimary = 0;
                    @endphp
                    @foreach($digitalPayments['unified']['usdt'] as $item)
                        @php
                            $subtotalUsdtCurr += $item['amount'];
                            $subtotalUsdtPrimary += $item['equiv_usd'] * $convertToPrimary(1, 'USD');
                        @endphp
                        <tr>
                            <td>{{ $item['date'] }}</td>
                            <td class="fw-bold">{{ strtoupper($item['zelle_sender'] ?? $item['sender_name'] ?? 'USDT') }}</td>
                            <td>{{ $item['ref'] }}</td>
                            <td class="fw-bold">{{ $item['invoice'] }}</td>
                            <td class="fw-bold">{{ strtoupper($item['customer']) }}</td>
                            <td class="text-right fw-bold">${{ number_format($item['zelle_total'] ?? $item['amount'], 2) }}</td>
                            <td class="text-right fw-bold">${{ number_format($item['amount'], 2) }}</td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="5" class="text-right">TOTAL CONCILIADO USDT (USD):</td>
                        <td class="text-right">${{ number_format($subtotalUsdtCurr, 2) }}</td>
                        <td class="text-right">{{ $symbol }} {{ number_format($subtotalUsdtPrimary, 4) }}</td>
                    </tr>
                </tbody>
            </table>
        @endif

    @else
        
        <!-- MODO SEPARADO -->

        <!-- ======================= SECCIÓN 1: VENTAS DEL DÍA ======================= -->
        <div style="border-bottom: 2px solid #ccc; padding-bottom: 2px; margin-top: 15px;">
            <span style="font-size: 10pt; font-weight: bold; text-transform: uppercase;">1. Ventas del Día (Contado)</span>
        </div>

        <!-- Efectivo Ventas -->
        @if($includeCash && !empty($cashDetails['sales']))
            <div class="sub-header">Resumen de Efectivo (CASH) - Ventas</div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Moneda / Denominación</th>
                        <th class="text-right">Cantidad Física</th>
                        <th class="text-right">Total en {{ $symbol }}</th>
                    </tr>
                </thead>
                <tbody>
                    @php $subtotalCashSales = 0; @endphp
                    @foreach($cashDetails['sales'] as $curr => $amt)
                        @php 
                            $equiv = $convertToPrimary($amt, $curr);
                            $subtotalCashSales += $equiv;
                        @endphp
                        <tr>
                            <td class="fw-bold">Efectivo {{ $getLabel($curr) }}</td>
                            <td class="text-right fw-bold">{{ number_format($amt, 2) }} {{ $curr }}</td>
                            <td class="text-right fw-bold">{{ $symbol }} {{ number_format($equiv, 4) }}</td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="2" class="text-right">SUBTOTAL EFECTIVO VENTAS:</td>
                        <td class="text-right">{{ $symbol }} {{ number_format($subtotalCashSales, 4) }}</td>
                    </tr>
                </tbody>
            </table>
        @endif

        <!-- Bancos Ventas -->
        @if(!empty($digitalPayments['sales']['bank']))
            <div class="sub-header">Detalle Bancario - Ventas</div>
            @foreach($digitalPayments['sales']['bank'] as $bank => $currenciesInBank)
                @foreach($currenciesInBank as $curr => $items)
                    <table class="table">
                        <thead>
                            <tr>
                                <th width="15%">F. Voucher</th>
                                <th width="15%">Referencia</th>
                                <th width="15%">Factura</th>
                                <th width="25%">Cliente</th>
                                <th width="15%" class="text-right">Monto {{ $curr }}</th>
                                <th width="15%" class="text-right">Total en {{ $symbol }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php 
                                $subtotalBankSalesCurr = 0; 
                                $subtotalBankSalesPrimary = 0;
                            @endphp
                            @foreach($items as $item)
                                @php
                                    $subtotalBankSalesCurr += $item['amount'];
                                    $subtotalBankSalesPrimary += $item['equiv_usd'] * $convertToPrimary(1, 'USD');
                                @endphp
                                <tr>
                                    <td>{{ $item['date'] }}</td>
                                    <td>{{ $item['ref'] }}</td>
                                    <td class="fw-bold">{{ $item['invoice'] }}</td>
                                    <td class="fw-bold">{{ strtoupper($item['customer']) }}</td>
                                    <td class="text-right">{{ number_format($item['amount'], 2) }}</td>
                                    <td class="text-right fw-bold">{{ $symbol }} {{ number_format($item['equiv_usd'] * $convertToPrimary(1, 'USD'), 4) }}</td>
                                </tr>
                            @endforeach
                            <tr class="total-row">
                                <td colspan="4" class="text-right">SUBTOTAL {{ strtoupper($bank) }} VENTAS ({{ $curr }}):</td>
                                <td class="text-right">{{ number_format($subtotalBankSalesCurr, 2) }}</td>
                                <td class="text-right">{{ $symbol }} {{ number_format($subtotalBankSalesPrimary, 4) }}</td>
                            </tr>
                        </tbody>
                    </table>
                @endforeach
            @endforeach
        @endif

        <!-- Zelle Ventas -->
        @if(!empty($digitalPayments['sales']['zelle']))
            <div class="sub-header">Detalle Zelle - Ventas (USD)</div>
            <table class="table">
                <thead>
                    <tr>
                        <th width="12%">F. Voucher</th>
                        <th width="18%">Quien Envía</th>
                        <th width="14%">Referencia</th>
                        <th width="12%">Factura</th>
                        <th width="18%">Cliente</th>
                        <th width="13%" class="text-right">Monto Zelle</th>
                        <th width="13%" class="text-right">Monto Usado</th>
                    </tr>
                </thead>
                <tbody>
                    @php 
                        $subtotalZelleSalesCurr = 0; 
                        $subtotalZelleSalesPrimary = 0;
                    @endphp
                    @foreach($digitalPayments['sales']['zelle'] as $item)
                        @php
                            $subtotalZelleSalesCurr += $item['amount'];
                            $subtotalZelleSalesPrimary += $item['equiv_usd'] * $convertToPrimary(1, 'USD');
                        @endphp
                        <tr>
                            <td>{{ $item['date'] }}</td>
                            <td class="fw-bold">{{ strtoupper($item['zelle_sender']) }}</td>
                            <td>{{ $item['ref'] }}</td>
                            <td class="fw-bold">{{ $item['invoice'] }}</td>
                            <td class="fw-bold">{{ strtoupper($item['customer']) }}</td>
                            <td class="text-right fw-bold">${{ number_format($item['zelle_total'], 2) }}</td>
                            <td class="text-right fw-bold">${{ number_format($item['amount'], 2) }}</td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="5" class="text-right">SUBTOTAL ZELLE VENTAS (USD):</td>
                        <td class="text-right">${{ number_format($subtotalZelleSalesCurr, 2) }}</td>
                        <td class="text-right">{{ $symbol }} {{ number_format($subtotalZelleSalesPrimary, 4) }}</td>
                    </tr>
                </tbody>
            </table>
        @endif


        <!-- ======================= SECCIÓN 2: CRÉDITOS RECIBIDOS ======================= -->
        <div style="border-bottom: 2px solid #ccc; padding-bottom: 2px; margin-top: 25px; page-break-before: auto;">
            <span style="font-size: 10pt; font-weight: bold; text-transform: uppercase;">2. Recaudo de Créditos (Cobranza)</span>
        </div>

        <!-- Efectivo Créditos -->
        @if($includeCash && !empty($cashDetails['credits']))
            <div class="sub-header">Resumen de Efectivo (CASH) - Recaudo Créditos</div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Moneda / Denominación</th>
                        <th class="text-right">Cantidad Física</th>
                        <th class="text-right">Total en {{ $symbol }}</th>
                    </tr>
                </thead>
                <tbody>
                    @php $subtotalCashCredits = 0; @endphp
                    @foreach($cashDetails['credits'] as $curr => $amt)
                        @php 
                            $equiv = $convertToPrimary($amt, $curr);
                            $subtotalCashCredits += $equiv;
                        @endphp
                        <tr>
                            <td class="fw-bold">Efectivo {{ $getLabel($curr) }}</td>
                            <td class="text-right fw-bold">{{ number_format($amt, 2) }} {{ $curr }}</td>
                            <td class="text-right fw-bold">{{ $symbol }} {{ number_format($equiv, 4) }}</td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="2" class="text-right">SUBTOTAL EFECTIVO RECAUDO:</td>
                        <td class="text-right">{{ $symbol }} {{ number_format($subtotalCashCredits, 4) }}</td>
                    </tr>
                </tbody>
            </table>
        @endif

        <!-- Bancos Créditos -->
        @if(!empty($digitalPayments['credits']['bank']))
            <div class="sub-header">Detalle Bancario - Recaudo Créditos</div>
            @foreach($digitalPayments['credits']['bank'] as $bank => $currenciesInBank)
                @foreach($currenciesInBank as $curr => $items)
                    <table class="table">
                        <thead>
                            <tr>
                                <th width="12%">F. Voucher</th>
                                <th width="14%">Referencia</th>
                                <th width="12%">Factura</th>
                                <th width="26%">Cliente</th>
                                <th width="18%" class="text-right">Monto {{ $curr }}</th>
                                <th width="18%" class="text-right">Total en {{ $symbol }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php 
                                $subtotalBankCreditsCurr = 0; 
                                $subtotalBankCreditsPrimary = 0;
                            @endphp
                            @foreach($items as $item)
                                @php
                                    $subtotalBankCreditsCurr += $item['amount'];
                                    $subtotalBankCreditsPrimary += $item['equiv_usd'] * $convertToPrimary(1, 'USD');
                                @endphp
                                <tr>
                                    <td>{{ $item['date'] }}</td>
                                    <td>{{ $item['ref'] }}</td>
                                    <td class="fw-bold">{{ $item['invoice'] }}</td>
                                    <td class="fw-bold">{{ strtoupper($item['customer']) }}</td>
                                    <td class="text-right">{{ number_format($item['amount'], 2) }}</td>
                                    <td class="text-right fw-bold">{{ $symbol }} {{ number_format($item['equiv_usd'] * $convertToPrimary(1, 'USD'), 4) }}</td>
                                </tr>
                            @endforeach
                            <tr class="total-row">
                                <td colspan="4" class="text-right">SUBTOTAL {{ strtoupper($bank) }} RECAUDO ({{ $curr }}):</td>
                                <td class="text-right">{{ number_format($subtotalBankCreditsCurr, 2) }}</td>
                                <td class="text-right">{{ $symbol }} {{ number_format($subtotalBankCreditsPrimary, 4) }}</td>
                            </tr>
                        </tbody>
                    </table>
                @endforeach
            @endforeach
        @endif

        <!-- Zelle Créditos -->
        @if(!empty($digitalPayments['credits']['zelle']))
            <div class="sub-header">Detalle Zelle - Recaudo Créditos (USD)</div>
            <table class="table">
                <thead>
                    <tr>
                        <th width="12%">F. Voucher</th>
                        <th width="18%">Quien Envía</th>
                        <th width="14%">Referencia</th>
                        <th width="12%">Factura</th>
                        <th width="18%">Cliente</th>
                        <th width="13%" class="text-right">Monto Zelle</th>
                        <th width="13%" class="text-right">Monto Usado</th>
                    </tr>
                </thead>
                <tbody>
                    @php 
                        $subtotalZelleCreditsCurr = 0; 
                        $subtotalZelleCreditsPrimary = 0;
                    @endphp
                    @foreach($digitalPayments['credits']['zelle'] as $item)
                        @php
                            $subtotalZelleCreditsCurr += $item['amount'];
                            $subtotalZelleCreditsPrimary += $item['equiv_usd'] * $convertToPrimary(1, 'USD');
                        @endphp
                        <tr>
                            <td>{{ $item['date'] }}</td>
                            <td class="fw-bold">{{ strtoupper($item['zelle_sender']) }}</td>
                            <td>{{ $item['ref'] }}</td>
                            <td class="fw-bold">{{ $item['invoice'] }}</td>
                            <td class="fw-bold">{{ strtoupper($item['customer']) }}</td>
                            <td class="text-right fw-bold">${{ number_format($item['zelle_total'], 2) }}</td>
                            <td class="text-right fw-bold">${{ number_format($item['amount'], 2) }}</td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="5" class="text-right">SUBTOTAL ZELLE RECAUDO (USD):</td>
                        <td class="text-right">${{ number_format($subtotalZelleCreditsCurr, 2) }}</td>
                        <td class="text-right">{{ $symbol }} {{ number_format($subtotalZelleCreditsPrimary, 4) }}</td>
                    </tr>
                </tbody>
            </table>
        @endif

    @endif

    <!-- ======================= RESUMEN FINAL A ENTREGAR ======================= -->
    <div style="page-break-inside: avoid; margin-top: 30px;">
        <table class="table" style="border: 2px solid #0284c7;">
            <thead>
                <tr style="background-color: #0284c7; color: #fff;">
                    <th colspan="2" style="font-size: 11pt; padding: 8px 12px; color: #fff;">TOTAL GENERAL CONCILIADO EN CAJA (A ENTREGAR)</th>
                    <th class="text-right" style="font-size: 11pt; padding: 8px 12px; color: #fff;">{{ $symbol }} {{ number_format($grandTotalIncomeUSD * $convertToPrimary(1, 'USD'), 4) }}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="3" style="font-size: 7.5pt; color: #666; font-style: italic; background-color: #fafafa; padding: 6px 12px;">
                        * Este total global consolidado incluye la sumatoria de todos los ingresos detallados en efectivo, cuentas bancarias nacionales e internacionales y transferencias Zelle, expresados y consolidados bajo la tasa oficial de la moneda de referencia del sistema.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Firmas -->
    <table class="signature-table">
        <tr>
            <td width="50%">
                <div class="signature-line">Firma Cajero</div>
            </td>
            <td width="50%">
                <div class="signature-line">Firma Supervisor</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        <p>Documento generado por el sistema de ventas JSPos v1.10.105 | Módulo de Auditoría Detallada de Arqueo</p>
    </div>
</body>
</html>
