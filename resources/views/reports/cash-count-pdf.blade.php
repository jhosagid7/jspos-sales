<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Corte de Caja - {{ $dateFrom }} al {{ $dateTo }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; color: #333; margin: 0; padding: 0; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 0; color: #000; text-transform: uppercase; font-size: 16px; }
        .header p { margin: 2px 0; font-size: 10px; }
        .info-table { width: 100%; margin-bottom: 15px; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
        .info-table td { padding: 3px 0; }
        .section-title { background: #f4f4f4; padding: 5px 10px; font-weight: bold; font-size: 12px; margin: 15px 0 10px 0; border-left: 4px solid #33a2ff; }
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .data-table th { background: #eee; text-align: left; padding: 6px 8px; border-bottom: 1px solid #ccc; }
        .data-table td { padding: 6px 8px; border-bottom: 1px solid #eee; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
        .total-row { background: #f9f9f9; font-weight: bold; }
        .footer { margin-top: 50px; text-align: center; }
        .signature-table { width: 100%; margin-top: 60px; }
        .signature-line { border-top: 1px solid #000; width: 200px; margin: 0 auto; padding-top: 5px; }
        .badge { display: inline-block; padding: 2px 6px; background: #eee; border-radius: 4px; font-size: 9px; }
        .zelle-item { border-bottom: 1px dashed #eee; padding: 4px 0; }
        .bank-group { margin-bottom: 5px; }
        .bank-name { font-weight: bold; color: #004085; display: block; margin-bottom: 2px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ strtoupper($config->business_name) }}</h2>
        <p>{{ $config->address }}</p>
        <p>NIT: {{ $config->taxpayer_id }} | TEL: {{ $config->phone }}</p>
        <p style="font-size: 14px; font-weight: bold; margin-top: 10px;">INFORME DE CORTE DE CAJA</p>
    </div>

    <table class="info-table">
        <tr>
            <td width="50%"><strong>Rango de Fechas:</strong> {{ $dateFrom }} al {{ $dateTo }}</td>
            <td width="50%" class="text-right"><strong>Fecha Impresión:</strong> {{ now()->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td><strong>Cajero(s):</strong> {{ $user_name }}</td>
            <td class="text-right"><strong>Moneda Principal:</strong> {{ $symbol }}</td>
        </tr>
    </table>

    {{-- SECTION 1: VENTAS DEL DÍA --}}
    <div class="section-title">VENTAS DEL DÍA</div>
    <table class="data-table">
        <thead>
            <tr>
                <th width="40%">Forma de Pago / Detalle</th>
                <th width="30%" class="text-right">Detalle Moneda</th>
                <th width="30%" class="text-right">Total en {{ $symbol }}</th>
            </tr>
        </thead>
        <tbody>
            {{-- Efectivo --}}
            @if(!empty($salesByCurrency['cash']))
                @foreach($salesByCurrency['cash'] as $curr => $amt)
                    <tr>
                        <td>Efectivo {{ $getLabel($curr) }}</td>
                        <td class="text-right">{{ number_format($amt, 2) }} {{ $curr }}</td>
                        <td class="text-right">{{ $symbol }} {{ number_format($convertToPrimary($amt, $curr), 2) }}</td>
                    </tr>
                @endforeach
            @endif

            {{-- Banco --}}
            @if(!empty($salesByCurrency['deposit']))
                @foreach($salesByCurrency['deposit'] as $bn => $currs)
                    @if(is_array($currs))
                        @foreach($currs as $curr => $amt)
                            <tr>
                                <td><span class="bank-name">{{ $bn }}</span></td>
                                <td class="text-right">{{ number_format($amt, 2) }} {{ $curr }}</td>
                                <td class="text-right">{{ $symbol }} {{ number_format($convertToPrimary($amt, $curr), 2) }}</td>
                            </tr>
                        @endforeach
                    @else
                         <tr>
                            <td>Banco / Otros: {{ $getLabel($bn) }}</td>
                            <td class="text-right">{{ number_format($currs, 2) }} {{ $bn }}</td>
                            <td class="text-right">{{ $symbol }} {{ number_format($convertToPrimary($currs, $bn), 2) }}</td>
                        </tr>
                    @endif
                @endforeach
            @endif

            {{-- Zelle --}}
            @if(!empty($salesByCurrency['zelle']))
                @foreach($salesByCurrency['zelle'] as $sender => $amt)
                    <tr>
                        <td>Zelle: <span class="badge">{{ substr($sender, 0, 30) }}</span></td>
                        <td class="text-right">{{ number_format($amt, 2) }} USD</td>
                        <td class="text-right">{{ $symbol }} {{ number_format($convertToPrimary($amt, 'USD'), 2) }}</td>
                    </tr>
                @endforeach
            @endif

            <tr class="total-row">
                <td colspan="2" class="text-right">TOTAL VENTAS RECIBIDAS (NETO):</td>
                <td class="text-right">{{ $symbol }} {{ number_format($salesTotal - $credit, 2) }}</td>
            </tr>
            <tr>
                <td colspan="2" class="text-right">VENTAS A CRÉDITO:</td>
                <td class="text-right">{{ $symbol }} {{ number_format($credit, 2) }}</td>
            </tr>
            <tr class="total-row" style="background: #eef;">
                <td colspan="2" class="text-right">TOTAL VENTAS BRUTAS:</td>
                <td class="text-right">{{ $symbol }} {{ number_format($salesTotal, 2) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- SECTION 2: PAGOS DE CRÉDITOS --}}
    <div class="section-title">PAGOS DE CRÉDITOS RECIBIDOS</div>
    <table class="data-table">
        <thead>
            <tr>
                <th width="40%">Forma de Pago / Detalle</th>
                <th width="30%" class="text-right">Detalle Moneda</th>
                <th width="30%" class="text-right">Total en {{ $symbol }}</th>
            </tr>
        </thead>
        <tbody>
            @if(!empty($paymentsByCurrency['cash']))
                @foreach($paymentsByCurrency['cash'] as $curr => $amt)
                    <tr>
                        <td>Efectivo {{ $getLabel($curr) }}</td>
                        <td class="text-right">{{ number_format($amt, 2) }} {{ $curr }}</td>
                        <td class="text-right">{{ $symbol }} {{ number_format($convertToPrimary($amt, $curr), 2) }}</td>
                    </tr>
                @endforeach
            @endif

             @if(!empty($paymentsByCurrency['deposit']))
                @foreach($paymentsByCurrency['deposit'] as $bn => $currs)
                    @if(is_array($currs))
                        @foreach($currs as $curr => $amt)
                            <tr>
                                <td><span class="bank-name">{{ $bn }}</span></td>
                                <td class="text-right">{{ number_format($amt, 2) }} {{ $curr }}</td>
                                <td class="text-right">{{ $symbol }} {{ number_format($convertToPrimary($amt, $curr), 2) }}</td>
                            </tr>
                        @endforeach
                    @else
                         <tr>
                            <td>Banco / Otros: {{ $getLabel($bn) }}</td>
                            <td class="text-right">{{ number_format($currs, 2) }} {{ $bn }}</td>
                            <td class="text-right">{{ $symbol }} {{ number_format($convertToPrimary($currs, $bn), 2) }}</td>
                        </tr>
                    @endif
                @endforeach
            @endif

            @if(!empty($paymentsByCurrency['zelle']))
                @foreach($paymentsByCurrency['zelle'] as $sender => $amt)
                    <tr>
                        <td>Zelle: <span class="badge">{{ substr($sender, 0, 30) }}</span></td>
                        <td class="text-right">{{ number_format($amt, 2) }} USD</td>
                        <td class="text-right">{{ $symbol }} {{ number_format($convertToPrimary($amt, 'USD'), 2) }}</td>
                    </tr>
                @endforeach
            @endif

            @if(empty($paymentsByCurrency['cash']) && empty($paymentsByCurrency['deposit']) && empty($paymentsByCurrency['zelle']))
                <tr>
                    <td colspan="3" style="text-align: center; color: #999;">Sin movimientos de pagos</td>
                </tr>
            @endif

            <tr class="total-row">
                <td colspan="2" class="text-right">TOTAL PAGOS RECIBIDOS:</td>
                <td class="text-right">{{ $symbol }} {{ number_format($payments, 2) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- SECTION 3: RESUMEN GENERAL --}}
    <div style="page-break-inside: avoid;">
        <div class="section-title">RESUMEN TOTAL (SALES + PAYMENTS)</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th width="40%">Categoría</th>
                    <th width="30%" class="text-right">Detalle Moneda</th>
                    <th width="30%" class="text-right">Subtotal en {{ $symbol }}</th>
                </tr>
            </thead>
            <tbody>
                {{-- Total Cash --}}
                @php $totalCashFull = 0; @endphp
                @foreach($totalCashDetails as $curr => $amt)
                    @php $primaryAmt = $convertToPrimary($amt, $curr); $totalCashFull += $primaryAmt; @endphp
                    <tr>
                        <td>TOTAL EFECTIVO ({{ $curr }})</td>
                        <td class="text-right">{{ number_format($amt, 2) }} {{ $curr }}</td>
                        <td class="text-right">{{ $symbol }} {{ number_format($primaryAmt, 2) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row" style="background-color: #f1f1f1;">
                    <td colspan="2" class="text-right">SUBTOTAL EFECTIVO:</td>
                    <td class="text-right">{{ $symbol }} {{ number_format($totalCashFull, 2) }}</td>
                </tr>

                {{-- Total Banks --}}
                <tr><td colspan="3" style="padding-top: 15px;"></td></tr>
                @php $totalBankFull = 0; @endphp
                @foreach($totalBankDetails as $bn => $currs)
                    @foreach($currs as $curr => $amt)
                        @php $primaryAmt = $convertToPrimary($amt, $curr); $totalBankFull += $primaryAmt; @endphp
                        <tr>
                            <td>TOTAL BANCO: <span class="bank-name">{{ $bn }} ({{ $curr }})</span></td>
                            <td class="text-right">{{ number_format($amt, 2) }} {{ $curr }}</td>
                            <td class="text-right">{{ $symbol }} {{ number_format($primaryAmt, 2) }}</td>
                        </tr>
                    @endforeach
                @endforeach
                <tr class="total-row" style="background-color: #f1f1f1;">
                    <td colspan="2" class="text-right">SUBTOTAL BANCOS:</td>
                    <td class="text-right">{{ $symbol }} {{ number_format($totalBankFull, 2) }}</td>
                </tr>

                {{-- Total Zelle --}}
                <tr><td colspan="3" style="padding-top: 15px;"></td></tr>
                @php $totalZelleFull = 0; @endphp
                @foreach($totalZelleDetails as $sender => $amt)
                    @php $primaryAmt = $convertToPrimary($amt, 'USD'); $totalZelleFull += $primaryAmt; @endphp
                    <tr>
                        <td>TOTAL Zelle: {{ $sender }}</td>
                        <td class="text-right">{{ number_format($amt, 2) }} USD</td>
                        <td class="text-right">{{ $symbol }} {{ number_format($primaryAmt, 2) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row" style="background-color: #f1f1f1;">
                    <td colspan="2" class="text-right">SUBTOTAL ZELLE:</td>
                    <td class="text-right">{{ $symbol }} {{ number_format($totalZelleFull, 2) }}</td>
                </tr>

                {{-- TOTAL GENERAL --}}
                <tr><td colspan="3" style="padding-top: 20px;"></td></tr>
                <tr style="background: #33a2ff; color: #fff; font-size: 14px; font-weight: bold;">
                    <td colspan="2" class="text-right" style="padding: 10px;">TOTAL GENERAL EN CAJA:</td>
                    <td class="text-right" style="padding: 10px;">{{ $symbol }} {{ number_format($totalCashFull + $totalBankFull + $totalZelleFull, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

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
        <p>Documento generado por el sistema de ventas JSPos v1.8.87</p>
    </div>
</body>
</html>
