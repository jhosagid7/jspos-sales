<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Cierre de Turno de Soplados</title>
    <style type="text/css">
        html {
            font-family: sans-serif;
            line-height: 1.15;
            margin: 0;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 10px;
            color: #212529;
            margin: 36pt;
            background-color: #fff;
        }
        h4, .h4 {
            margin-top: 0;
            margin-bottom: 0.5rem;
            font-weight: 500;
            line-height: 1.2;
            font-size: 1.5rem;
        }
        .invoice-title {
            color: #2e7d32;
            font-weight: bold;
            font-size: 20px;
            margin: 0;
        }
        .report-title {
            color: #2e7d32;
            font-size: 16px;
            font-weight: bold;
            margin: 0;
        }
        .box-details {
            border: 1px solid #6c757d;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 20px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        .table th, .table td {
            padding: 0.6rem;
            vertical-align: top;
            border-top: 1px solid #dee2e6;
        }
        .table thead th {
            vertical-align: bottom;
            border-bottom: 2px solid #dee2e6;
            background-color: #f8f9fa;
            text-align: left;
            font-weight: bold;
            color: #495057;
        }
        .text-right {
            text-align: right !important;
        }
        .text-center {
            text-align: center !important;
        }
        .text-uppercase {
            text-transform: uppercase !important;
        }
        .bg-light {
            background-color: #f8f9fa;
        }
        .section-header {
            font-size: 11px;
            font-weight: bold;
            color: #2e7d32;
            border-bottom: 1.5px solid #2e7d32;
            padding-bottom: 3px;
            margin-top: 20px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .badge {
            display: inline-block;
            padding: 0.25em 0.4em;
            font-size: 75%;
            font-weight: 700;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.25rem;
        }
        .badge-success {
            color: #fff;
            background-color: #28a745;
        }
        .badge-secondary {
            color: #fff;
            background-color: #6c757d;
        }
        .kpi-container {
            width: 100%;
            margin-bottom: 20px;
        }
        .kpi-card {
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 8px;
            text-align: center;
            background-color: #f8f9fa;
        }
        .kpi-value {
            font-size: 14px;
            font-weight: bold;
            margin-top: 4px;
        }
    </style>
</head>
<body>
    {{-- Header --}}
    <table style="width: 100%; margin-bottom: 10px;">
        <tbody>
            <tr>
                <td style="width: 25%; vertical-align: middle;">
                    @if($config && $config->logo)
                        <img src="{{ public_path('storage/' . $config->logo) }}" alt="logo" height="50">
                    @endif
                </td>
                <td class="text-center" style="width: 50%; vertical-align: middle;">
                    <h4 class="text-uppercase invoice-title">
                        {{ $config->business_name ?? 'Fábrica Soplados' }}
                    </h4>
                </td>
                <td class="text-right" style="width: 25%; vertical-align: middle;">
                    <h4 class="text-uppercase report-title">REPORTE DE TURNO</h4>
                    <span style="font-size: 10px; font-weight: bold;">ID Turno: #{{ $shift->id }}</span>
                </td>
            </tr>
        </tbody>
    </table>

    {{-- Info Box --}}
    <div class="box-details">
        <table style="width: 100%;">
            <tbody>
                <tr>
                    <td style="width: 60%; vertical-align: top;">
                        <strong style="font-size: 12px; color: #2e7d32;" class="text-uppercase">{{ $config->business_name ?? '' }}</strong><br>
                        NIT: {{ $config->taxpayer_id ?? '' }}<br>
                        Dirección: {{ $config->address ?? '' }}<br>
                        Teléfono: {{ $config->phone ?? '' }}
                    </td>
                    <td class="text-right" style="width: 40%; vertical-align: top;">
                        Fecha Impresión: <strong>{{ \Carbon\Carbon::now()->format('d/m/Y h:i A') }}</strong><br>
                        Fecha de Turno: <strong>{{ $shift->start_time ? $shift->start_time->format('d/m/Y') : '' }}</strong><br>
                        Tipo de Turno: <strong>{{ $shift->type }}</strong><br>
                        Planta/Almacén: <strong>{{ $shift->warehouse->name ?? 'N/A' }}</strong><br>
                        Estado: <span class="badge {{ $shift->status == 'open' ? 'badge-success' : 'badge-secondary' }}">{{ $shift->status == 'open' ? 'ABIERTO' : 'CERRADO' }}</span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Datos de Operación --}}
    <div class="section-header">Datos de Operación</div>
    <table class="table" style="margin-bottom: 20px; border: 1px solid #dee2e6;">
        <tbody>
            <tr>
                <td style="width: 25%; background-color: #f8f9fa;"><strong>Apertura:</strong></td>
                <td style="width: 25%;">{{ $shift->start_time ? $shift->start_time->format('d-m-Y h:i A') : 'N/A' }}</td>
                <td style="width: 25%; background-color: #f8f9fa;"><strong>Cierre:</strong></td>
                <td style="width: 25%;">{{ $shift->end_time ? $shift->end_time->format('d-m-Y h:i A') : 'N/A' }}</td>
            </tr>
            <tr>
                <td style="background-color: #f8f9fa;"><strong>Operadores Activos:</strong></td>
                <td colspan="3">{{ $operatorsList }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Resumen de Rendimiento (KPIs) --}}
    <div class="section-header">Totales y Rendimiento</div>
    <table style="width: 100%; margin-bottom: 20px;" cellspacing="10">
        <tbody>
            <tr>
                <td style="width: 25%;">
                    <div class="kpi-card">
                        <div style="color: #555;">Prod. Buena</div>
                        <div class="kpi-value" style="color: #2e7d32;">{{ number_format($goodQuantity, 0) }} unds</div>
                    </div>
                </td>
                <td style="width: 25%;">
                    <div class="kpi-card">
                        <div style="color: #555;">Dañado (Merma)</div>
                        <div class="kpi-value" style="color: #c62828;">{{ number_format($damagedQuantity, 0) }} unds</div>
                    </div>
                </td>
                <td style="width: 25%;">
                    <div class="kpi-card">
                        <div style="color: #555;">Total Procesado</div>
                        <div class="kpi-value" style="color: #1565c0;">{{ number_format($totalProduced, 0) }} unds</div>
                    </div>
                </td>
                <td style="width: 25%;">
                    <div class="kpi-card">
                        <div style="color: #555;">Yield (Rendimiento)</div>
                        <div class="kpi-value" style="color: #2e7d32;">{{ number_format($efficiency, 2) }}%</div>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>

    <table style="width: 100%;">
        <tbody>
            <tr>
                {{-- Envases Soplados --}}
                <td style="width: 48%; padding-right: 2%; vertical-align: top;">
                    <div class="section-header">Detalle de Envases Producidos</div>
                    @if(count($productionOutputs) > 0)
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Producto (Calidad)</th>
                                    <th class="text-right">Cantidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($productionOutputs as $label => $qty)
                                    <tr>
                                        <td>{{ $label }}</td>
                                        <td class="text-right"><strong>{{ number_format($qty, 0) }}</strong> unds</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="text-muted">Ningún envase producido registrado en este turno.</p>
                    @endif
                </td>

                {{-- Materia Prima Consumida --}}
                <td style="width: 48%; padding-left: 2%; vertical-align: top;">
                    <div class="section-header">Materia Prima Consumida</div>
                    @if(count($materialsConsumed) > 0)
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Materia Prima</th>
                                    <th class="text-right">Cantidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($materialsConsumed as $label => $qty)
                                    <tr>
                                        <td>{{ $label }}</td>
                                        <td class="text-right"><strong>{{ number_format($qty, 2) }}</strong> Kg</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="text-muted">Ningún material consumido registrado en este turno.</p>
                    @endif
                </td>
            </tr>
        </tbody>
    </table>

    {{-- Observaciones --}}
    <div class="section-header" style="margin-top: 30px;">Observaciones / Notas</div>
    <div style="border: 1px solid #dee2e6; border-radius: 4px; padding: 10px; min-height: 50px; background-color: #fcfcfc;">
        {{ $shift->notes ?? 'Sin observaciones registradas para este turno.' }}
    </div>
</body>
</html>
