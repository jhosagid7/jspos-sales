<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Consolidado Semanal de Soplados</title>
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
            color: #1b5e20;
            font-weight: bold;
            font-size: 20px;
            margin: 0;
        }
        .report-title {
            color: #1b5e20;
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
            border-bottom: 1px solid #dee2e6;
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
            color: #1b5e20;
            border-bottom: 1.5px solid #1b5e20;
            padding-bottom: 3px;
            margin-top: 20px;
            margin-bottom: 10px;
            text-transform: uppercase;
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
        .text-danger {
            color: #c62828 !important;
        }
        .text-success {
            color: #2e7d32 !important;
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
                    <h4 class="text-uppercase report-title">REPORTE SEMANAL CONSOLIDADO</h4>
                    <span style="font-size: 9px; font-weight: bold; color: #555;">Planta Soplados</span>
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
                        <strong style="font-size: 12px; color: #1b5e20;" class="text-uppercase">{{ $config->business_name ?? '' }}</strong><br>
                        NIT: {{ $config->taxpayer_id ?? '' }}<br>
                        Dirección: {{ $config->address ?? '' }}<br>
                        Teléfono: {{ $config->phone ?? '' }}
                    </td>
                    <td class="text-right" style="width: 40%; vertical-align: top;">
                        Fecha Generación: <strong>{{ \Carbon\Carbon::now()->format('d/m/Y h:i A') }}</strong><br>
                        Período: <strong>{{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }}</strong> al <strong>{{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}</strong><br>
                        Turnos Procesados: <strong>{{ $shifts->count() }}</strong>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- KPIs --}}
    <div class="section-header">Rendimiento Consolidado de la Semana</div>
    <table style="width: 100%; margin-bottom: 20px;" cellspacing="10">
        <tbody>
            <tr>
                <td style="width: 25%;">
                    <div class="kpi-card">
                        <div style="color: #555;">Prod. Buena Semanal</div>
                        <div class="kpi-value" style="color: #2e7d32;">{{ number_format($totalGood, 0) }} unds</div>
                    </div>
                </td>
                <td style="width: 25%;">
                    <div class="kpi-card">
                        <div style="color: #555;">Merma (Defectuoso)</div>
                        <div class="kpi-value" style="color: #c62828;">{{ number_format($totalDamaged, 0) }} unds</div>
                    </div>
                </td>
                <td style="width: 25%;">
                    <div class="kpi-card">
                        <div style="color: #555;">Total Procesado</div>
                        <div class="kpi-value" style="color: #1565c0;">{{ number_format($totalWeekProduced, 0) }} unds</div>
                    </div>
                </td>
                <td style="width: 25%;">
                    <div class="kpi-card">
                        <div style="color: #555;">Rendimiento (Yield)</div>
                        <div class="kpi-value" style="color: #2e7d32;">{{ number_format($weekEfficiency, 2) }}%</div>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>

    {{-- Detalle de Producción y Consumos --}}
    <table style="width: 100%; margin-bottom: 20px;">
        <tbody>
            <tr>
                {{-- Producción --}}
                <td style="width: 48%; padding-right: 2%; vertical-align: top;">
                    <div class="section-header">Total Producido por Envase</div>
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
                        <p class="text-muted">Sin registros de producción en esta semana.</p>
                    @endif
                </td>

                {{-- Consumo --}}
                <td style="width: 48%; padding-left: 2%; vertical-align: top;">
                    <div class="section-header">Total Consumido de Materias Primas</div>
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
                        <p class="text-muted">Sin registros de consumos en esta semana.</p>
                    @endif
                </td>
            </tr>
        </tbody>
    </table>

    {{-- Rendimiento de Producción por Turno --}}
    <div class="section-header">Rendimiento de Producción por Turno</div>
    @if(count($shiftsData) > 0)
        <table class="table" style="font-size: 8px; margin-bottom: 20px;">
            <thead>
                <tr>
                    <th style="width: 12%;">Fecha</th>
                    <th style="width: 10%;">Turno</th>
                    <th style="width: 25%;">Operadores / Supervisor</th>
                    <th style="width: 23%;">Envase Producido</th>
                    <th class="text-right" style="width: 10%;">Prod. Buena</th>
                    <th class="text-right" style="width: 10%;">Meta Turno</th>
                    <th class="text-right" style="width: 10%;">Cumplimiento</th>
                    <th class="text-center" style="width: 10%;">Estatus</th>
                </tr>
            </thead>
            <tbody>
                @foreach($shiftsData as $sd)
                    @php $rowspan = count($sd['outputs']); @endphp
                    @if($rowspan > 0)
                        @php $first = true; @endphp
                        @foreach($sd['outputs'] as $pId => $out)
                            <tr>
                                @if($first)
                                    <td rowspan="{{ $rowspan }}" style="vertical-align: middle;"><strong>{{ $sd['date'] }}</strong></td>
                                    <td rowspan="{{ $rowspan }}" style="vertical-align: middle;">{{ $sd['type'] }}</td>
                                    <td rowspan="{{ $rowspan }}" style="vertical-align: middle; font-size: 8px; color: #555;">{{ $sd['users'] }}</td>
                                    @php $first = false; @endphp
                                @endif
                                <td>{{ $out['name'] }}</td>
                                <td class="text-right"><strong>{{ number_format($out['quantity'], 0) }}</strong> unds</td>
                                <td class="text-right">{{ $out['min'] > 0 ? number_format($out['min'], 0) . ' - ' . number_format($out['max'], 0) : 'Sin Meta' }}</td>
                                <td class="text-right">
                                    @if($out['min'] > 0)
                                        {{ number_format($out['compliance_pct'], 1) }}%
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($out['min'] > 0)
                                        <span class="{{ $out['status'] === 'Cumplido' ? 'text-success' : 'text-danger' }}" style="font-weight: bold;">
                                            {{ $out['status'] }}
                                        </span>
                                    @else
                                        <span style="color: #666;">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td><strong>{{ $sd['date'] }}</strong></td>
                            <td>{{ $sd['type'] }}</td>
                            <td style="font-size: 8px; color: #555;">{{ $sd['users'] }}</td>
                            <td colspan="5" class="text-center text-muted">Sin producción registrada en este turno.</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    @else
        <p class="text-muted" style="margin-bottom: 20px;">No se registraron turnos cerrados en este período.</p>
    @endif

    {{-- Ingresos de Insumos / Materias Primas --}}
    <div class="section-header">Ingresos de Insumos / Materias Primas en la Semana</div>
    @if(count($weeklyEntries) > 0)
        <table class="table" style="font-size: 8px; margin-bottom: 20px;">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Insumo / Materia Prima</th>
                    <th class="text-right">Cantidad</th>
                    <th>Depósito Destino</th>
                    <th>Origen / Método</th>
                </tr>
            </thead>
            <tbody>
                @foreach($weeklyEntries as $entry)
                    <tr>
                         <td>{{ $entry['date'] }}</td>
                         <td><strong>{{ $entry['product'] }}</strong></td>
                         <td class="text-right"><strong>{{ number_format($entry['quantity'], 2) }}</strong></td>
                         <td>{{ $entry['destination'] }}</td>
                         <td>{{ $entry['source'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="text-muted" style="margin-bottom: 20px;">No se registraron ingresos de insumos en este período.</p>
    @endif

    {{-- Existencias y Ubicación de Insumos --}}
    <div class="section-header">Existencias y Ubicación Actual de Insumos</div>
    @if(count($rawMaterialStocks) > 0)
        <table class="table" style="font-size: 8px; margin-bottom: 20px;">
            <thead>
                <tr>
                    <th>Materia Prima / Insumo</th>
                    <th class="text-right">Stock en Zona (ID 4)</th>
                    <th class="text-right">Stock en Planta Soplados (ID 3)</th>
                    <th class="text-right" style="background-color: #f1f3f5;">Stock Consolidado Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rawMaterialStocks as $stock)
                    <tr>
                         <td><strong>{{ $stock['name'] }}</strong></td>
                         <td class="text-right">{{ number_format($stock['zona_stock'], 2) }} {{ $stock['unit'] }}</td>
                         <td class="text-right">{{ number_format($stock['soplados_stock'], 2) }} {{ $stock['unit'] }}</td>
                         <td class="text-right" style="background-color: #fafafa;"><strong>{{ number_format($stock['total_stock'], 2) }}</strong> {{ $stock['unit'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="text-muted" style="margin-bottom: 20px;">No hay materias primas de soplado configuradas en el sistema.</p>
    @endif

    {{-- Último Inventario realizado --}}
    <div class="section-header">Último Inventario Realizado en Soplados</div>
    @if($lastInventory)
        <table style="width: 100%; margin-bottom: 10px; border: 1px solid #dee2e6; padding: 8px; background-color: #fafafa;">
            <tbody>
                <tr>
                    <td style="width: 33%;">Fecha Inventario: <strong>{{ $lastInventory->created_at->format('d/m/Y h:i A') }}</strong></td>
                    <td style="width: 33%;">Supervisor: <strong>{{ $lastInventory->supervisor->name ?? 'N/A' }}</strong></td>
                    <td style="width: 33%;">Estado: <strong style="text-transform: uppercase;">{{ $lastInventory->status }}</strong></td>
                </tr>
                @if($lastInventory->notes)
                    <tr>
                        <td colspan="3">Observaciones: <em>{{ $lastInventory->notes }}</em></td>
                    </tr>
                @endif
            </tbody>
        </table>

        @if($lastInventory->details->count() > 0)
            <table class="table" style="font-size: 9px;">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th class="text-center" colspan="3">1ra Calidad</th>
                        <th class="text-center" colspan="3">2da Calidad</th>
                        <th class="text-center" colspan="3">Merma / Desecho</th>
                    </tr>
                    <tr>
                        <th></th>
                        <th class="text-center" style="color: #666; font-size: 8px;">Sist.</th>
                        <th class="text-center" style="color: #666; font-size: 8px;">Físico</th>
                        <th class="text-center" style="color: #666; font-size: 8px;">Dif.</th>
                        <th class="text-center" style="color: #666; font-size: 8px;">Sist.</th>
                        <th class="text-center" style="color: #666; font-size: 8px;">Físico</th>
                        <th class="text-center" style="color: #666; font-size: 8px;">Dif.</th>
                        <th class="text-center" style="color: #666; font-size: 8px;">Sist.</th>
                        <th class="text-center" style="color: #666; font-size: 8px;">Físico</th>
                        <th class="text-center" style="color: #666; font-size: 8px;">Dif.</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lastInventory->details as $detail)
                        <tr>
                            <td><strong>{{ $detail->product->name ?? 'Producto' }}</strong></td>
                            
                            {{-- 1ra --}}
                            <td class="text-center">{{ number_format($detail->system_stock_primera, 0) }}</td>
                            <td class="text-center">{{ number_format($detail->counted_primera, 0) }}</td>
                            <td class="text-center {{ $detail->difference_primera < 0 ? 'text-danger' : ($detail->difference_primera > 0 ? 'text-success' : '') }}">
                                {{ $detail->difference_primera > 0 ? '+' : '' }}{{ number_format($detail->difference_primera, 0) }}
                            </td>
                            
                            {{-- 2da --}}
                            <td class="text-center">{{ number_format($detail->system_stock_segunda, 0) }}</td>
                            <td class="text-center">{{ number_format($detail->counted_segunda, 0) }}</td>
                            <td class="text-center {{ $detail->difference_segunda < 0 ? 'text-danger' : ($detail->difference_segunda > 0 ? 'text-success' : '') }}">
                                {{ $detail->difference_segunda > 0 ? '+' : '' }}{{ number_format($detail->difference_segunda, 0) }}
                            </td>
                            
                            {{-- Merma --}}
                            <td class="text-center">{{ number_format($detail->system_stock_merma, 0) }}</td>
                            <td class="text-center">{{ number_format($detail->counted_merma, 0) }}</td>
                            <td class="text-center {{ $detail->difference_merma < 0 ? 'text-danger' : ($detail->difference_merma > 0 ? 'text-success' : '') }}">
                                {{ $detail->difference_merma > 0 ? '+' : '' }}{{ number_format($detail->difference_merma, 0) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-muted">Este inventario no contiene registros detallados.</p>
        @endif
    @else
        <p class="text-muted">No se registran inventarios físicos en la base de datos.</p>
    @endif
</body>
</html>
