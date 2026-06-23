<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Actividad del Cliente</title>
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
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }
        .header td {
            vertical-align: top;
        }
        .business-info h2 {
            margin: 0;
            font-size: 11pt;
            color: #2c3e50;
        }
        .report-info {
            text-align: right;
            font-size: 8pt;
        }
        .report-title {
            text-align: center;
            font-size: 11pt;
            font-weight: bold;
            margin-top: 10px;
            margin-bottom: 10px;
            text-decoration: underline;
            text-transform: uppercase;
            color: #2c3e50;
        }
        .filter-info {
            margin-bottom: 10px;
            font-size: 8pt;
            background-color: #f8f9fa;
            padding: 5px;
            border: 1px solid #e9ecef;
            border-radius: 3px;
        }
        .kpi-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .kpi-table th {
            background-color: #1a252f;
            color: white;
            padding: 4px 6px;
            font-size: 7.5pt;
            font-weight: bold;
            text-align: left;
            text-transform: uppercase;
        }
        .kpi-table td {
            padding: 5px 6px;
            border: 1px solid #ddd;
            font-size: 7.2pt;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table th {
            background-color: #34495e;
            color: white;
            border: 1px solid #34495e;
            padding: 4px 5px;
            text-align: center;
            font-size: 7.5pt;
            text-transform: uppercase;
            font-weight: bold;
        }
        .table td {
            padding: 4px 5px;
            border: 1px solid #dee2e6;
            font-size: 7.2pt;
            vertical-align: middle;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }

        .section-title {
            font-size: 9pt;
            font-weight: bold;
            color: #2c3e50;
            margin-top: 15px;
            margin-bottom: 5px;
            border-bottom: 1px solid #2c3e50;
            padding-bottom: 2px;
            text-transform: uppercase;
        }

        .footer-signatures {
            width: 100%;
            margin-top: 30px;
        }
        .signature-line {
            border-top: 1px solid #333;
            width: 80%;
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
                Fecha Emisión: {{ $date }}<br>
                Generado por: {{ strtoupper($user->name ?? 'N/A') }}
            </td>
        </tr>
    </table>

    <div class="report-title">Reporte de Actividad y Análisis de Compras</div>
    
    <div class="filter-info">
        <strong>Parámetros de Consulta:</strong><br>
        • Rango de Fechas: {{ $dateFromStr ? \Carbon\Carbon::parse($dateFromStr)->format('d/m/Y') : 'Inicio' }} al {{ $dateToStr ? \Carbon\Carbon::parse($dateToStr)->format('d/m/Y') : 'Fin' }}<br>
        • Agrupación temporal: {{ strtoupper($periodType) }} | Métrica analizada: {{ $metric === 'count' ? 'CANTIDAD DE COMPRAS' : 'MONTO COMPRADO (USD)' }}
    </div>

    <!-- KPIs de Resumen -->
    <div class="section-title">Resumen de Indicadores Clave (KPIs)</div>
    <table class="kpi-table">
        <thead>
            <tr>
                <th style="width: 25%;">Cliente</th>
                <th style="width: 12%; text-align: right;">Total Comprado (USD)</th>
                <th style="width: 10%; text-align: center;">Cantidad Compras</th>
                <th style="width: 13%; text-align: right;">Ticket Promedio (USD)</th>
                <th style="width: 12%; text-align: center;">Última Compra</th>
                <th style="width: 28%;">Top Productos</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($kpis as $custId => $kpi)
                <tr>
                    <td class="font-bold">{{ $kpi['name'] }}</td>
                    <td class="text-right font-bold">${{ number_format($kpi['total_amount'], 2) }}</td>
                    <td class="text-center font-bold">{{ $kpi['sales_count'] }}</td>
                    <td class="text-right font-bold">${{ number_format($kpi['avg_ticket'], 2) }}</td>
                    <td class="text-center font-bold text-info">{{ $kpi['last_purchase_at'] }}</td>
                    <td>
                        <ul style="margin: 0; padding-left: 12px; list-style-type: square; font-size: 6.8pt;">
                            @forelse($kpi['top_products'] as $prod)
                                <li>{{ $prod->product_name }} ({{ number_format($prod->total_qty, 0) }} uds)</li>
                            @empty
                                <li style="list-style-type: none; margin-left: -12px;" class="text-muted">Ninguno</li>
                            @endforelse
                        </ul>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Tabla Comparativa de Tendencias -->
    <div class="section-title">Evolución Comparativa por Periodo</div>
    <table class="table">
        <thead>
            <tr>
                <th style="width: 20%;">Periodo</th>
                @foreach ($kpis as $custId => $kpi)
                    <th>{{ $kpi['name'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @php
                $grandTotals = array_fill_keys(array_keys($kpis), 0);
            @endphp
            @forelse($labels as $labelIndex => $periodLabel)
                <tr>
                    <td class="text-center font-bold" style="background-color: #f8f9fa;">{{ $periodLabel }}</td>
                    @foreach ($kpis as $custId => $kpi)
                        @php
                            $val = $datasets[array_search($kpi['name'], array_column($datasets, 'label'))]['data'][$labelIndex] ?? 0;
                            $grandTotals[$custId] += $val;
                        @endphp
                        <td class="text-center">
                            @if($metric === 'count')
                                {{ number_format($val, 0) }}
                            @else
                                ${{ number_format($val, 2) }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="20" class="text-center text-muted">No se registraron compras en los periodos indicados.</td>
                </tr>
            @endforelse
        </tbody>
        @if(!empty($labels))
            <tfoot>
                <tr style="background-color: #eaeded; font-weight: bold;">
                    <td class="text-center">TOTAL ACUMULADO:</td>
                    @foreach ($kpis as $custId => $kpi)
                        <td class="text-center">
                            @if($metric === 'count')
                                {{ number_format($grandTotals[$custId], 0) }}
                            @else
                                ${{ number_format($grandTotals[$custId], 2) }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            </tfoot>
        @endif
    </table>

    <!-- Registro Detallado de Facturas -->
    <div class="section-title">Registro Detallado de Facturas de Compra</div>
    <table class="table">
        <thead>
            <tr>
                <th style="width: 10%;">Folio/Nro</th>
                <th style="width: 15%;">Fecha/Hora</th>
                <th style="width: 25%;">Cliente</th>
                <th style="width: 15%; text-align: right;">Base USD</th>
                <th style="width: 10%; text-align: right;">Flete USD</th>
                <th style="width: 15%; text-align: right;">Total USD</th>
                <th style="width: 10%;">Estatus</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($detailedSales as $sale)
                <tr>
                    <td class="text-center font-bold">{{ $sale->invoice_number ?: $sale->id }}</td>
                    <td class="text-center">{{ \Carbon\Carbon::parse($sale->created_at)->format('d/m/Y H:i') }}</td>
                    <td>{{ $sale->customer->name ?? 'N/A' }}</td>
                    <td class="text-right">${{ number_format($sale->base_amount, 2) }}</td>
                    <td class="text-right">${{ number_format($sale->total_freight, 2) }}</td>
                    <td class="text-right font-bold">${{ number_format($sale->total_usd, 2) }}</td>
                    <td class="text-center">
                        @if($sale->status === 'paid')
                            <span style="color: green;">PAGADO</span>
                        @elseif($sale->status === 'pending')
                            <span style="color: orange;">PENDIENTE</span>
                        @else
                            <span>{{ strtoupper($sale->status) }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted">No se encontraron transacciones en el periodo.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Firmas -->
    <table class="footer-signatures" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="width: 33%; text-align: center; vertical-align: bottom;">
                <div class="signature-line"></div>
                <div style="font-size: 7.5pt; font-weight: bold;">ANALISTA COMERCIAL</div>
            </td>
            <td style="width: 33%; text-align: center; vertical-align: bottom;">
                <div class="signature-line"></div>
                <div style="font-size: 7.5pt; font-weight: bold;">GERENCIA DE VENTAS</div>
            </td>
            <td style="width: 34%; text-align: center; vertical-align: bottom;">
                <div class="signature-line"></div>
                <div style="font-size: 7.5pt; font-weight: bold;">DIRECCIÓN GENERAL</div>
            </td>
        </tr>
    </table>

</body>
</html>
