<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Desempeño de Vendedores</title>
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
        .text-primary { color: #1a237e; }
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

    <div class="report-title">Reporte de Desempeño y Cartera de Vendedores</div>
    
    <div class="filter-info">
        <strong>Parámetros de Consulta:</strong><br>
        • Rango de Fechas: {{ $dateFromStr ? \Carbon\Carbon::parse($dateFromStr)->format('d/m/Y') : 'Inicio' }} al {{ $dateToStr ? \Carbon\Carbon::parse($dateToStr)->format('d/m/Y') : 'Fin' }}<br>
        • Agrupación temporal (Gráfico): {{ strtoupper($periodType) }} | Métrica: {{ strtoupper($metric) }}<br>
        • Estatus Facturas: 
        @if($invoiceStatus === 'pending') Solo Pendientes
        @elseif($invoiceStatus === 'paid') Solo Pagadas
        @else Todas @endif
    </div>

    <!-- KPIs de Resumen -->
    <div class="section-title">Indicadores Consolidados del Periodo</div>
    <table class="kpi-table">
        <thead>
            <tr>
                <th>Ventas Totales Brutas</th>
                <th>Margen Neto Real</th>
                <th>Costo de Comisiones</th>
                <th>% Margen Neto</th>
                <th>Deuda Pendiente</th>
                <th>Deuda Vencida</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="font-bold">${{ number_format($kpis['total_sales'], 2) }}</td>
                <td class="font-bold text-success">${{ number_format($kpis['net_sales'], 2) }}</td>
                <td class="font-bold text-warning">${{ number_format($kpis['total_commission'], 2) }}</td>
                <td class="font-bold text-primary">{{ number_format($kpis['margin_percent'], 1) }}%</td>
                <td class="font-bold text-danger">${{ number_format($kpis['total_debt'], 2) }}</td>
                <td class="font-bold text-danger" style="background-color: #fdf2f2;">${{ number_format($kpis['total_overdue'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Tabla Comparativa de Desempeño -->
    <div class="section-title">Análisis Comparativo por Vendedor</div>
    <table class="table">
        <thead>
            <tr>
                <th>Vendedor</th>
                <th>Ventas Brutas USD</th>
                <th>Facturas</th>
                <th>Comisiones USD</th>
                <th>Venta Neta USD</th>
                <th>% Margen</th>
                <th>Clts Activos</th>
                <th>Deuda Pendiente</th>
                <th>Deuda Vencida</th>
                <th>Atraso Promedio</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sellers as $sellerData)
                <tr>
                    <td class="font-bold" style="background-color: #f8f9fa;">{{ $sellerData['name'] }}</td>
                    <td class="text-center font-bold">${{ number_format($sellerData['gross_sales'], 2) }}</td>
                    <td class="text-center">{{ number_format($sellerData['invoices_count'], 0) }}</td>
                    <td class="text-center">${{ number_format($sellerData['commissions'], 2) }}</td>
                    <td class="text-center text-success font-bold">${{ number_format($sellerData['net_sales'], 2) }}</td>
                    <td class="text-center font-bold text-primary">{{ number_format($sellerData['margin_percent'], 1) }}%</td>
                    <td class="text-center">{{ $sellerData['active_customers'] }}</td>
                    <td class="text-center">${{ number_format($sellerData['pending_debt'], 2) }}</td>
                    <td class="text-center text-danger font-bold">${{ number_format($sellerData['overdue_debt'], 2) }}</td>
                    <td class="text-center">
                        @if($sellerData['avg_days_overdue'] > 0)
                            <span class="text-danger font-bold">{{ number_format($sellerData['avg_days_overdue'], 1) }} días</span>
                        @else
                            <span class="text-success">Al día</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center text-muted">No se registraron transacciones para los vendedores en este período.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($invoiceLimit !== 'none')
    <!-- Registro Detallado de Facturas -->
    <div class="section-title">Historial Detallado de Ventas @if($invoiceLimit === '100') (Últimas 100 facturas) @elseif($invoiceLimit === 'all') (Todas las facturas) @endif</div>
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
    @endif

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
