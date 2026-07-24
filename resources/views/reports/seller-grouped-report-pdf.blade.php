<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Cobranza por Vendedor</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #222; background: #fff; }

        .header { padding: 12px 20px; background: #1e2a3a; color: #fff; border-bottom: 3px solid #3498db; }
        .header h1 { font-size: 16px; font-weight: bold; letter-spacing: 1px; }
        .header .subtitle { font-size: 10px; color: #a0b4c8; margin-top: 3px; }
        .header .meta { font-size: 9px; color: #7a9ab8; margin-top: 2px; }

        .content { padding: 12px 20px; }

        /* KPIs */
        .kpis { display: table; width: 100%; margin-bottom: 14px; border-collapse: separate; border-spacing: 8px; }
        .kpi { display: table-cell; width: 33%; background: #f4f7fb; border: 1px solid #d0dce8; border-radius: 4px; padding: 8px 10px; vertical-align: top; }
        .kpi.total   { border-top: 3px solid #27ae60; background: #eefaf3; }
        .kpi-label { font-size: 8px; text-transform: uppercase; color: #666; font-weight: bold; letter-spacing: 0.5px; }
        .kpi-sub { font-size: 7px; color: #999; margin-top: 1px; }
        .kpi-usd { font-size: 15px; font-weight: bold; margin-top: 4px; }
        .kpi.total   .kpi-usd { color: #27ae60; }

        /* Table */
        .section-title { font-size: 11px; font-weight: bold; color: #1e2a3a; border-bottom: 2px solid #3498db; padding-bottom: 4px; margin-bottom: 8px; }

        table { width: 100%; border-collapse: collapse; }
        thead tr { background: #1e2a3a; color: #fff; }
        thead th { padding: 6px 8px; text-align: center; font-size: 9px; font-weight: bold; letter-spacing: 0.3px; border: 1px solid #1e2a3a; }
        thead th:first-child { text-align: left; }

        tbody tr:nth-child(even) { background: #f4f7fb; }
        tbody tr:nth-child(odd)  { background: #fff; }
        tbody td { padding: 5px 8px; font-size: 9px; border: 1px solid #d0dce8; }
        tbody td.seller { font-weight: bold; color: #1a2233; background: #e8ecf1; }
        tbody td.num  { text-align: right; }
        tbody td.cnt  { text-align: center; }
        tbody td.subtotal { background: #e8ecf1; font-weight: bold; text-align: right; }
        tbody td.subtotal-val { background: #e8ecf1; font-weight: bold; text-align: right; color: #27ae60; }

        tfoot tr { background: #1e2a3a; color: #fff; }
        tfoot td { padding: 6px 8px; font-size: 9px; font-weight: bold; border: 1px solid #1e2a3a; }
        tfoot td.num { text-align: right; }
        tfoot td.cnt { text-align: center; }

        .footer { margin-top: 14px; border-top: 1px solid #d0dce8; padding-top: 6px; color: #888; font-size: 8px; }
    </style>
</head>
<body>

<div class="header">
    <h1>REPORTE DE COBRANZA POR OPERADOR</h1>
    <div class="subtitle">
        Desglose de métodos de pago y equivalentes en USD
        @if($config) &nbsp;|&nbsp; {{ strtoupper($config->business_name ?? '') }} @endif
    </div>
    <div class="meta">
        Período: {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }}
        @if($dateFrom !== $dateTo) — {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }} @endif
        &nbsp;|&nbsp; Generado: {{ $generatedAt }}
    </div>
</div>

<div class="content">

    {{-- KPIs --}}
    <div class="kpis">
        <div class="kpi total">
            <div class="kpi-label">Total General Cobrado</div>
            <div class="kpi-sub">Suma de todos los pagos equivalentes en USD</div>
            <div class="kpi-usd">USD $ {{ number_format($totalGeneralUsd, 2) }}</div>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="section-title">Detalle por Operador</div>

    <table>
        <thead>
            <tr>
                <th style="text-align:left; width:25%;">Operador</th>
                <th>M&eacute;todo</th>
                <th>Moneda</th>
                @if($showOriginalAmount)
                <th>Monto Original</th>
                @endif
                @if($showExchangeRate)
                <th>Tasa de Cambio</th>
                @endif
                @if($showUsdAmount)
                <th>Equivalente USD</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($reportData as $sellerName => $sellerData)
                @php
                    $sellerTotalUsd = 0;
                @endphp

                @if(isset($splitByDepartment) && $splitByDepartment)
                    @php
                        $rowspan = 0;
                        foreach($sellerData as $deptType => $payments) {
                            $rowspan += 1;
                            $rowspan += count($payments);
                        }
                        $isFirstRow = true;
                    @endphp
                    @foreach($sellerData as $deptType => $payments)
                        <tr>
                            @if($isFirstRow)
                                <td class="seller" rowspan="{{ $rowspan }}">{{ $sellerName }}</td>
                            @endif
                            <td colspan="{{ 2 + ($showOriginalAmount ? 1 : 0) + ($showExchangeRate ? 1 : 0) + ($showUsdAmount ? 1 : 0) }}" style="background:#e8ecf1; font-weight:bold; font-size:9px;">▶ DEP: {{ $deptType }}</td>
                        </tr>
                        @php $isFirstRow = false; @endphp
                        
                        @foreach($payments as $row)
                            @php $sellerTotalUsd += $row->total_usd; @endphp
                            <tr>
                                <td class="cnt" style="text-align:left; padding-left:12px;">{{ strtoupper($row->method) }}</td>
                                <td class="cnt">{{ strtoupper($row->currency) }}</td>
                                @if($showOriginalAmount)
                                <td class="num">{{ number_format($row->total_amount, 2) }}</td>
                                @endif
                                @if($showExchangeRate)
                                <td class="num">{{ number_format($row->avg_rate, 2) }}</td>
                                @endif
                                @if($showUsdAmount)
                                <td class="num">$ {{ number_format($row->total_usd, 2) }}</td>
                                @endif
                            </tr>
                        @endforeach
                    @endforeach
                @else
                    @foreach($sellerData as $index => $row)
                        @php $sellerTotalUsd += $row->total_usd; @endphp
                        <tr>
                            @if($index === 0)
                                <td class="seller" rowspan="{{ count($sellerData) }}">{{ $sellerName }}</td>
                            @endif
                            <td class="cnt">{{ strtoupper($row->method) }}</td>
                            <td class="cnt">{{ strtoupper($row->currency) }}</td>
                            @if($showOriginalAmount)
                            <td class="num">{{ number_format($row->total_amount, 2) }}</td>
                            @endif
                            @if($showExchangeRate)
                            <td class="num">{{ number_format($row->avg_rate, 2) }}</td>
                            @endif
                            @if($showUsdAmount)
                            <td class="num">$ {{ number_format($row->total_usd, 2) }}</td>
                            @endif
                        </tr>
                    @endforeach
                @endif
                <tr>
                    <td colspan="{{ 2 + ($showOriginalAmount ? 1 : 0) + ($showExchangeRate ? 1 : 0) }}" class="subtotal">SUBTOTAL OPERADOR:</td>
                    @if($showUsdAmount)
                    <td class="subtotal-val">$ {{ number_format($sellerTotalUsd, 2) }}</td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center; color:#999; padding:12px;">
                        Sin cobros registrados para el per&iacute;odo seleccionado.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if($reportData->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="{{ 3 + ($showOriginalAmount ? 1 : 0) + ($showExchangeRate ? 1 : 0) }}" class="num">TOTAL GENERAL COBRADO USD:</td>
                @if($showUsdAmount)
                <td class="num" style="color:#7ef0b4;">$ {{ number_format($totalGeneralUsd, 2) }}</td>
                @endif
            </tr>
        </tfoot>
        @endif
    </table>

    @if(isset($showSignatures) && $showSignatures)
    <!-- Firmas -->
    <table style="width: 100%; margin-top: 40px; border: none;">
        <tr style="background: transparent;">
            <td style="border: none; text-align: center; width: 50%;">
                <div style="border-top: 1px solid #333; width: 60%; margin: 0 auto; padding-top: 5px;">
                    Firma Cajero
                </div>
            </td>
            <td style="border: none; text-align: center; width: 50%;">
                <div style="border-top: 1px solid #333; width: 60%; margin: 0 auto; padding-top: 5px;">
                    Firma Supervisor
                </div>
            </td>
        </tr>
    </table>
    @endif

    <div class="footer">
        Este reporte fue generado autom&aacute;ticamente por JSPOS.
        @if($config) {{ $config->business_name ?? '' }} @endif
    </div>
</div>

</body>
</html>
