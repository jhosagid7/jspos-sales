<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Análisis de Ventas</title>
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
            text-align: center;
            text-transform: uppercase;
        }
        .kpi-table td {
            padding: 5px 6px;
            border: 1px solid #ddd;
            font-size: 7.2pt;
            text-align: center;
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
        .text-success { color: green; }
        .text-danger { color: red; }
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

    <div class="report-title">Reporte de Análisis de Ventas y Crecimiento</div>
    
    <div class="filter-info">
        <strong>Parámetros de Consulta:</strong><br>
        • Rango de Fechas: {{ $dateFromStr ? \Carbon\Carbon::parse($dateFromStr)->format('d/m/Y') : 'Inicio' }} al {{ $dateToStr ? \Carbon\Carbon::parse($dateToStr)->format('d/m/Y') : 'Fin' }}<br>
        • Agrupación temporal: {{ strtoupper($periodType) }} | Métrica: {{ strtoupper($metric) }}
    </div>

    <!-- KPIs de Resumen -->
    <div class="section-title">Resumen de Indicadores Clave (KPIs) del Periodo</div>
    <table class="kpi-table">
        <thead>
            <tr>
                <th>Ventas Totales USD</th>
                <th>Ventas Netas (Margen)</th>
                <th>Comisiones Devengadas</th>
                <th>Ticket Promedio</th>
                <th>Nro. Facturas</th>
                <th>Crecimiento %</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="font-bold">${{ number_format($kpis['total_sales'], 2) }}</td>
                <td class="font-bold text-success">${{ number_format($kpis['net_sales'], 2) }}</td>
                <td class="font-bold text-warning">${{ number_format($kpis['total_commission'], 2) }}</td>
                <td class="font-bold">${{ number_format($kpis['avg_ticket'], 2) }}</td>
                <td class="font-bold">{{ $kpis['sales_count'] }}</td>
                <td class="font-bold {{ $kpis['growth_percent'] >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ $kpis['growth_percent'] >= 0 ? '+' : '' }}{{ number_format($kpis['growth_percent'], 1) }}%
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Tabla Comparativa de Tendencias -->
    <div class="section-title">Evolución Comparativa por Periodo</div>
    <table class="table">
        <thead>
            <tr>
                <th style="width: 25%;">Periodo</th>
                <th>Ventas USD</th>
                <th>Facturas</th>
                <th>Comisiones USD</th>
                <th>Venta Neta USD</th>
                <th>Crecimiento %</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalSales = 0;
                $totalCount = 0;
                $totalComm = 0;
                $totalNet = 0;
                $lastVal = null;
            @endphp
            @forelse($results as $rowIndex => $row)
                @php
                    $totalSales += $row->total_amount;
                    $totalCount += $row->sales_count;
                    $totalComm += $row->total_commission;
                    $totalNet += $row->net_sales;

                    $rowGrowth = 0;
                    $rowGrowthArrow = '';
                    $rowGrowthClass = '';
                    
                    if ($lastVal !== null) {
                        if ($lastVal > 0) {
                            $rowGrowth = (($row->total_amount - $lastVal) / $lastVal) * 100;
                        } else {
                            $rowGrowth = $row->total_amount > 0 ? 100 : 0;
                        }

                        if ($rowGrowth > 0) {
                            $rowGrowthArrow = '+';
                            $rowGrowthClass = 'text-success';
                        } elseif ($rowGrowth < 0) {
                            $rowGrowthClass = 'text-danger';
                        }
                    }
                    $lastVal = $row->total_amount;
                @endphp
                <tr>
                    <td class="text-center font-bold" style="background-color: #f8f9fa;">{{ $row->period_label }}</td>
                    <td class="text-center font-bold">${{ number_format($row->total_amount, 2) }}</td>
                    <td class="text-center">{{ number_format($row->sales_count, 0) }}</td>
                    <td class="text-center">${{ number_format($row->total_commission, 2) }}</td>
                    <td class="text-center text-success">${{ number_format($row->net_sales, 2) }}</td>
                    <td class="text-center font-bold {{ $rowGrowthClass }}">
                        @if($rowIndex === 0)
                            <span class="text-muted">Inicio</span>
                        @else
                            {{ $rowGrowthArrow }}{{ number_format($rowGrowth, 1) }}%
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted">No se registraron ventas en los periodos indicados.</td>
                </tr>
            @endforelse
        </tbody>
        @if(!empty($results))
            <tfoot>
                <tr style="background-color: #eaeded; font-weight: bold;">
                    <td class="text-center">TOTAL ACUMULADO:</td>
                    <td class="text-center">${{ number_format($totalSales, 2) }}</td>
                    <td class="text-center">{{ number_format($totalCount, 0) }}</td>
                    <td class="text-center">${{ number_format($totalComm, 2) }}</td>
                    <td class="text-center text-success">${{ number_format($totalNet, 2) }}</td>
                    <td class="text-center">-</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <!-- Registro Detallado de Facturas -->
    <div class="section-title">Registro Detallado de Facturas Emitidas @if($detailedSales->count() >= 100) (Últimas 100 facturas) @endif</div>
    <table class="table">
        <thead>
            <tr>
                <th style="width: 10%;">Folio/Nro</th>
                <th style="width: 15%;">Fecha/Hora</th>
                <th style="width: 25%;">Cliente</th>
                <th style="width: 20%;">Vendedor</th>
                <th style="width: 15%; text-align: right;">Total USD</th>
                <th style="width: 15%;">Estatus</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($detailedSales as $sale)
                <tr>
                    <td class="text-center font-bold">{{ $sale->invoice_number ?: $sale->id }}</td>
                    <td class="text-center">{{ \Carbon\Carbon::parse($sale->created_at)->format('d/m/Y H:i') }}</td>
                    <td>{{ $sale->customer->name ?? 'N/A' }}</td>
                    <td>{{ $sale->customer->seller->name ?? 'OFICINA' }}</td>
                    <td class="text-right font-bold">${{ number_format($sale->total_usd, 2) }}</td>
                    <td class="text-center">
                        @if($sale->status === 'paid')
                            <span class="text-success">PAGADO</span>
                        @elseif($sale->status === 'pending')
                            <span style="color: orange;">PENDIENTE</span>
                        @else
                            <span>{{ strtoupper($sale->status) }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted">No se encontraron transacciones en el periodo.</td>
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
