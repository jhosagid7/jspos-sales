<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Eficiencia y Precisión de Operadores</title>
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
        .text-warning { color: orange; }
        .text-primary { color: #1a237e; }
        
        .badge-quality {
            font-weight: bold;
            padding: 2px 4px;
            border-radius: 3px;
            color: white;
        }
        .bg-success { background-color: #28a745; }
        .bg-warning { background-color: #ffc107; color: #212529 !important; }
        .bg-danger { background-color: #dc3545; }
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

    <div class="report-title">Reporte de Eficiencia y Precisión de Operadores</div>
    
    <div class="filter-info">
        <strong>Parámetros de Consulta:</strong><br>
        • Rango de Fechas: {{ $dateFromStr ? \Carbon\Carbon::parse($dateFromStr)->format('d/m/Y') : 'Inicio' }} al {{ $dateToStr ? \Carbon\Carbon::parse($dateToStr)->format('d/m/Y') : 'Fin' }}<br>
        • Métrica (Gráfico): {{ strtoupper($metric) }}
    </div>

    <!-- KPIs de Resumen -->
    <div class="section-title">Indicadores Consolidados del Periodo</div>
    <table class="kpi-table">
        <thead>
            <tr>
                <th>Facturas Procesadas</th>
                <th>Monto Facturado USD</th>
                <th>Score Precisión Prom.</th>
                <th>Facturas con Incidencias</th>
                <th>Modificadas</th>
                <th>Anuladas</th>
                <th>Devueltas</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="font-bold">{{ number_format($kpis['total_sales'], 0) }}</td>
                <td class="font-bold text-success">${{ number_format($kpis['total_amount'], 2) }}</td>
                <td class="font-bold text-primary">{{ number_format($kpis['avg_precision_score'], 2) }}%</td>
                <td class="font-bold text-danger">{{ number_format($kpis['total_errors'], 0) }}</td>
                <td class="font-bold">{{ number_format($kpis['total_modified'], 0) }}</td>
                <td class="font-bold text-danger">{{ number_format($kpis['total_voided'], 0) }}</td>
                <td class="font-bold text-warning">{{ number_format($kpis['total_returned'], 0) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Tabla Comparativa de Desempeño -->
    <div class="section-title">Análisis de Desempeño por Operador</div>
    <table class="table">
        <thead>
            <tr>
                <th>Operador</th>
                <th>Facturas Emitidas</th>
                <th>Monto Ventas USD</th>
                <th>Modificadas</th>
                <th>Anuladas</th>
                <th>Devueltas</th>
                <th>Incidencias</th>
                <th>Score Precisión</th>
                <th>Días Activos</th>
                <th>Eficiencia Diaria</th>
            </tr>
        </thead>
        <tbody>
            @forelse($operators as $op)
                <tr>
                    <td class="font-bold" style="background-color: #f8f9fa;">{{ $op['name'] }}</td>
                    <td class="text-center font-bold">{{ number_format($op['total_sales'], 0) }}</td>
                    <td class="text-center">${{ number_format($op['total_amount'], 2) }}</td>
                    <td class="text-center">{{ number_format($op['modified_count'], 0) }}</td>
                    <td class="text-center text-danger">{{ number_format($op['voided_count'], 0) }}</td>
                    <td class="text-center text-warning">{{ number_format($op['returned_count'], 0) }}</td>
                    <td class="text-center">{{ number_format($op['errors_count'], 0) }}</td>
                    <td class="text-center">
                        <span class="badge-quality bg-{{ $op['precision_score'] >= 95 ? 'success' : ($op['precision_score'] >= 85 ? 'warning' : 'danger') }}">
                            {{ number_format($op['precision_score'], 2) }}%
                        </span>
                    </td>
                    <td class="text-center">{{ $op['active_days'] }}</td>
                    <td class="text-center text-primary font-bold">{{ number_format($op['efficiency'], 1) }} fact/día</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center text-muted">No se registraron transacciones para los operadores en este período.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Registro Detallado de Facturas -->
    <div class="section-title">Historial Detallado de Ventas @if($detailedSales->count() >= 100) (Últimas 100 facturas) @endif</div>
    <table class="table">
        <thead>
            <tr>
                <th style="width: 8%;">Folio/Nro</th>
                <th style="width: 12%;">Fecha/Hora</th>
                <th style="width: 20%;">Cliente</th>
                <th style="width: 15%;">Operador (Biller)</th>
                <th style="width: 10%; text-align: right;">Total USD</th>
                <th style="width: 10%;">Estatus</th>
                <th style="width: 8%;">Modificada</th>
                <th style="width: 8%;">Devuelta</th>
                <th style="width: 9%;">Auditoría</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($detailedSales as $sale)
                @php
                    $isVoided = in_array($sale->status, ['voided', 'cancelled', 'anulated']) || !is_null($sale->deletion_approved_at);
                    $hasReturn = $sale->returns->where('status', 'approved')->count() > 0;
                    $isModified = $sale->history->count() > 0;
                @endphp
                <tr style="{{ $isVoided ? 'background-color: #fdf2f2; text-decoration: line-through; color: #a94442;' : '' }}">
                    <td class="text-center font-bold">{{ $sale->invoice_number ?: $sale->id }}</td>
                    <td class="text-center">{{ \Carbon\Carbon::parse($sale->created_at)->format('d/m/Y H:i') }}</td>
                    <td>{{ $sale->customer->name ?? 'N/A' }}</td>
                    <td>{{ $sale->user->name ?? 'N/A' }}</td>
                    <td class="text-right font-bold">${{ number_format($sale->total_usd, 2) }}</td>
                    <td class="text-center font-bold">
                        @if($isVoided)
                            <span class="text-danger">ANULADA</span>
                        @elseif($sale->status === 'paid')
                            <span class="text-success">PAGADA</span>
                        @elseif($sale->status === 'pending')
                            <span class="text-warning">PENDIENTE</span>
                        @else
                            <span>{{ strtoupper($sale->status) }}</span>
                        @endif
                    </td>
                    <td class="text-center font-bold">
                        @if($isModified)
                            <span class="text-danger">SÍ ({{ $sale->history->count() }})</span>
                        @else
                            <span class="text-muted">NO</span>
                        @endif
                    </td>
                    <td class="text-center font-bold">
                        @if($hasReturn)
                            <span class="text-warning">SÍ</span>
                        @else
                            <span class="text-muted">NO</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($sale->is_audited)
                            <span class="text-success">AUDITADA</span>
                        @else
                            <span class="text-muted">PENDIENTE</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center text-muted">No se encontraron transacciones en el periodo.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Firmas -->
    <table class="footer-signatures" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="width: 33%; text-align: center; vertical-align: bottom;">
                <div class="signature-line"></div>
                <div style="font-size: 7.5pt; font-weight: bold;">SUPERVISOR DE FACTURACIÓN</div>
            </td>
            <td style="width: 33%; text-align: center; vertical-align: bottom;">
                <div class="signature-line"></div>
                <div style="font-size: 7.5pt; font-weight: bold;">GERENCIA DE OPERACIONES</div>
            </td>
            <td style="width: 34%; text-align: center; vertical-align: bottom;">
                <div class="signature-line"></div>
                <div style="font-size: 7.5pt; font-weight: bold;">DIRECCIÓN GENERAL</div>
            </td>
        </tr>
    </table>

</body>
</html>
