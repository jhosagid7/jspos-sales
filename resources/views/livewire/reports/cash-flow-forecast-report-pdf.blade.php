<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Proyección de Flujo de Caja y Eficiencia de Cobranza</title>
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
        .text-warning { color: #fd7e14; }
        .text-primary { color: #1b55e2; }
        .badge-success { color: green; font-weight: bold; }
        .badge-warning { color: #fd7e14; font-weight: bold; }
        .badge-danger { color: red; font-weight: bold; }
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

    <div class="report-title">Proyección de Flujo de Caja y Eficiencia de Cobranza</div>
    
    <div class="filter-info">
        <strong>Parámetros de Consulta:</strong><br>
        • Período de Análisis: {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}<br>
        • Cobertura de Cartera: Ventas a Crédito Activas con Saldos Pendientes
        @if(isset($selectedBucket) && $selectedBucket !== 'all')
            <br>• Filtrado por Antigüedad: 
            <strong>
                @if($selectedBucket === 'vencido_critico') Vencido Crítico (>15d)
                @elseif($selectedBucket === 'vencido_8_15') Vencido Medio (8-15d)
                @elseif($selectedBucket === 'vencido_1_7') Vencido Leve (1-7d)
                @elseif($selectedBucket === 'corriente_1_7') Por Vencer (1-7d)
                @elseif($selectedBucket === 'corriente_8_14') Por Vencer (8-14d)
                @elseif($selectedBucket === 'corriente_largo') Por Vencer (>14d)
                @endif
            </strong>
        @endif
    </div>

    <!-- KPIs de Resumen -->
    <div class="section-title">Indicadores Consolidados de Crédito y Cobro</div>
    <table class="kpi-table">
        <thead>
            <tr>
                <th>Deuda Total Pendiente</th>
                <th>Cartera Corriente (Al Día)</th>
                <th>Cartera Vencida (En Mora)</th>
                <th>Cobrado en Período</th>
                <th>Eficiencia de Cobranza (CEI %)</th>
                <th>Atraso Promedio (DSO)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="font-bold">${{ number_format($metrics['totalDebt'], 2) }}</td>
                <td class="font-bold text-success">${{ number_format($metrics['currentDebt'], 2) }}</td>
                <td class="font-bold text-danger">${{ number_format($metrics['overdueDebt'], 2) }}</td>
                <td class="font-bold text-primary">${{ number_format($metrics['totalCollected'], 2) }}</td>
                <td class="font-bold">
                    <span class="@if($metrics['cei'] < 70) text-danger @elseif($metrics['cei'] < 85) text-warning @else text-success @endif">
                        {{ number_format($metrics['cei'], 2) }}%
                    </span>
                </td>
                <td class="font-bold text-danger">{{ number_format($metrics['dso'], 1) }} días</td>
            </tr>
        </tbody>
    </table>

    <!-- Ageing Buckets Matrix -->
    <div class="section-title">Distribución Temporal de la Cartera (Ageing Buckets)</div>
    <table class="kpi-table">
        <thead>
            <tr>
                <th style="background-color: #c0392b;">Vencido Crítico (>15d)</th>
                <th style="background-color: #e67e22;">Vencido Medio (8-15d)</th>
                <th style="background-color: #f1c40f; color: #333;">Vencido Leve (1-7d)</th>
                <th style="background-color: #2ecc71;">Por Vencer Corto (1-7d)</th>
                <th style="background-color: #27ae60;">Por Vencer Mediano (8-14d)</th>
                <th style="background-color: #16a085;">Por Vencer Largo (>14d)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="font-bold text-danger">${{ number_format($metrics['buckets']['vencido_critico'], 2) }}</td>
                <td class="font-bold text-warning">${{ number_format($metrics['buckets']['vencido_8_15'], 2) }}</td>
                <td class="font-bold text-warning">${{ number_format($metrics['buckets']['vencido_1_7'], 2) }}</td>
                <td class="font-bold text-success">${{ number_format($metrics['buckets']['corriente_1_7'], 2) }}</td>
                <td class="font-bold text-success">${{ number_format($metrics['buckets']['corriente_8_14'], 2) }}</td>
                <td class="font-bold text-success">${{ number_format($metrics['buckets']['corriente_largo'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Tabla Detallada -->
    <div class="section-title">Detalle de Facturas con Saldos Pendientes</div>
    <table class="table">
        <thead>
            <tr>
                <th style="width: 80px;">Factura</th>
                <th>Cliente</th>
                <th>Vendedor</th>
                <th style="width: 80px;">F. Emisión</th>
                <th style="width: 60px;">Crédito</th>
                <th style="width: 80px;">Vencimiento</th>
                <th style="width: 70px;">Mora/Resto</th>
                <th style="width: 80px;">Total Factura</th>
                <th style="width: 80px;">Deuda Pendiente</th>
                <th>Rango Edad</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sales as $sale)
                <tr>
                    <td class="text-center font-bold">
                        @if($sale['invoice_number'])
                            F-{{ str_pad($sale['invoice_number'], 6, '0', STR_PAD_LEFT) }}
                        @else
                            #{{ $sale['sale_id'] }}
                        @endif
                    </td>
                    <td>{{ $sale['customer_name'] }}</td>
                    <td>{{ $sale['seller_name'] }}</td>
                    <td class="text-center">{{ \Carbon\Carbon::parse($sale['created_at'])->format('d/m/Y') }}</td>
                    <td class="text-center">{{ $sale['credit_days'] }} días</td>
                    <td class="text-center font-bold">{{ \Carbon\Carbon::parse($sale['due_date'])->format('d/m/Y') }}</td>
                    <td class="text-center font-bold @if($sale['days_diff'] > 0) text-danger @else text-success @endif">
                        @if($sale['days_diff'] > 0)
                            +{{ $sale['days_diff'] }} días
                        @else
                            {{ $sale['days_diff'] }} días
                        @endif
                    </td>
                    <td class="text-right">${{ number_format($sale['total_usd'], 2) }}</td>
                    <td class="text-right font-bold">${{ number_format($sale['debt_usd'], 2) }}</td>
                    <td class="text-center">
                        @if($sale['status'] === 'vencido')
                            <span class="@if($sale['bucket'] === 'vencido_critico') text-danger @else text-warning @endif">
                                {{ $sale['status_text'] }}
                            </span>
                        @else
                            <span class="text-success">
                                {{ $sale['status_text'] }}
                            </span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center text-muted">No hay facturas a crédito activas pendientes con los filtros aplicados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Firmas -->
    <table class="footer-signatures" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="width: 33%; text-align: center; vertical-align: bottom;">
                <div class="signature-line"></div>
                <div style="font-size: 7.5pt; font-weight: bold;">ANALISTA DE CRÉDITO Y COBRANZA</div>
            </td>
            <td style="width: 33%; text-align: center; vertical-align: bottom;">
                <div class="signature-line"></div>
                <div style="font-size: 7.5pt; font-weight: bold;">GERENCIA DE FINANZAS</div>
            </td>
            <td style="width: 34%; text-align: center; vertical-align: bottom;">
                <div class="signature-line"></div>
                <div style="font-size: 7.5pt; font-weight: bold;">DIRECCIÓN GENERAL</div>
            </td>
        </tr>
    </table>

</body>
</html>
