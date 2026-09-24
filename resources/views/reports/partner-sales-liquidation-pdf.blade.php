<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ventas y Liquidación por Socio</title>
    <style>
        @page {
            margin: 10mm 8mm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #222;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }
        .header-table td {
            vertical-align: middle;
        }
        .title {
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
            color: #111;
        }
        .subtitle {
            font-size: 10px;
            color: #555;
        }
        .kpi-container {
            width: 100%;
            margin-bottom: 12px;
        }
        .kpi-box {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            text-align: center;
            border-radius: 4px;
        }
        .kpi-title {
            font-size: 8px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: bold;
        }
        .kpi-value {
            font-size: 13px;
            font-weight: bold;
            margin-top: 2px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        table.data-table th {
            background-color: #1e293b;
            color: #ffffff;
            font-weight: bold;
            font-size: 9.5px;
            padding: 6px 6px;
            text-align: left;
            border: 1px solid #1e293b;
        }
        table.data-table td {
            padding: 5px 6px;
            border: 1px solid #e2e8f0;
            font-size: 9.5px;
            vertical-align: middle;
        }
        table.data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .nowrap {
            white-space: nowrap;
        }
        .badge {
            display: inline-block;
            padding: 2px 5px;
            font-size: 8.5px;
            font-weight: bold;
            border-radius: 3px;
            background-color: #e2e8f0;
            color: #334155;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: right;
            font-size: 8.5px;
            color: #888;
            border-top: 1px solid #ddd;
            padding-top: 3px;
        }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td style="width: 70%;">
                <div class="title">{{ $config->business_name ?? 'JSPOS SALES' }}</div>
                <div class="subtitle">Liquidación y Ventas por Socio / Depósito de Origen</div>
                <div class="subtitle">Período: <strong>{{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }}</strong> al <strong>{{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}</strong></div>
                @if($originWarehouse)
                    <div class="subtitle">Socio / Depósito: <strong>{{ $originWarehouse->name }}</strong></div>
                @endif
            </td>
            <td style="width: 30%; text-align: right;">
                <div class="subtitle">Fecha de Emisión: {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</div>
                <div class="subtitle">Modo: 
                    @if($viewMode === 'summary') Resumen Agrupado de Ventas
                    @elseif($viewMode === 'detailed') Detalle de Facturas
                    @elseif($viewMode === 'origin_stock') Stock en Depósito del Socio
                    @elseif($viewMode === 'consignment_stock') Stock Consignado en Tienda
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <table class="kpi-container" style="border-spacing: 5px; border-collapse: separate;">
        <tr>
            <td style="width: 25%;">
                <div class="kpi-box">
                    <div class="kpi-title">Stock en Depósito Socio</div>
                    <div class="kpi-value" style="color: #0284c7;">{{ number_format($kpis['origin_physical_stock'], 2) }}</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="kpi-box">
                    <div class="kpi-title">Stock Remanente Tienda</div>
                    <div class="kpi-value" style="color: #e67e22;">{{ number_format($kpis['remaining_in_store'], 2) }}</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="kpi-box">
                    <div class="kpi-title">Unidades Vendidas</div>
                    <div class="kpi-value" style="color: #2980b9;">{{ number_format($kpis['total_qty_sold'], 2) }}</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="kpi-box">
                    <div class="kpi-title">Total Facturado ($)</div>
                    <div class="kpi-value" style="color: #111827;">${{ number_format($kpis['total_amount_sold'], 2) }}</div>
                </div>
            </td>
        </tr>
    </table>
    <table class="kpi-container" style="border-spacing: 5px; border-collapse: separate; margin-top: -6px;">
        <tr>
            <td style="width: 33.33%;">
                <div class="kpi-box" style="border-left: 3px solid #64748b;">
                    <div class="kpi-title">Costo Total Mercancía ($)</div>
                    <div class="kpi-value" style="color: #475569;">${{ number_format($kpis['total_cost_sold'] ?? 0, 2) }}</div>
                </div>
            </td>
            <td style="width: 33.33%;">
                <div class="kpi-box" style="border-left: 3px solid #16a34a;">
                    <div class="kpi-title">Ganancia Neta / Utilidad ($)</div>
                    <div class="kpi-value" style="color: {{ ($kpis['total_profit'] ?? 0) >= 0 ? '#16a34a' : '#dc2626' }};">${{ number_format($kpis['total_profit'] ?? 0, 2) }}</div>
                </div>
            </td>
            <td style="width: 33.33%;">
                <div class="kpi-box" style="border-left: 3px solid #0284c7;">
                    <div class="kpi-title">Margen de Ganancia Promedio</div>
                    <div class="kpi-value" style="color: {{ ($kpis['margin_percentage'] ?? 0) >= 0 ? '#0284c7' : '#dc2626' }};">{{ number_format($kpis['margin_percentage'] ?? 0, 2) }}%</div>
                </div>
            </td>
        </tr>
    </table>

    @if($viewMode === 'summary')
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 16%;">Socio / Origen</th>
                    <th style="width: 10%;" class="nowrap">Código / Barra</th>
                    <th style="width: 22%;">Producto</th>
                    <th class="text-right nowrap" style="width: 7%;">Unidades</th>
                    <th class="text-right nowrap" style="width: 7%;">P. Prom ($)</th>
                    <th class="text-right nowrap" style="width: 9%;">Venta ($)</th>
                    <th class="text-right nowrap" style="width: 7%;">C. Prom ($)</th>
                    <th class="text-right nowrap" style="width: 8%;">Costo Total ($)</th>
                    <th class="text-right nowrap" style="width: 8%;">Ganancia ($)</th>
                    <th class="text-right nowrap" style="width: 6%;">Margen</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $row)
                    <tr>
                        <td><strong>{{ $row->origin_warehouse_name ?? 'Depósito Principal' }}</strong></td>
                        <td class="nowrap">{{ $row->product_barcode ?? 'S/C' }}</td>
                        <td>{{ $row->product_name }}</td>
                        <td class="text-right nowrap"><strong>{{ number_format($row->total_quantity, 2) }}</strong></td>
                        <td class="text-right nowrap">${{ number_format($row->avg_unit_price, 2) }}</td>
                        <td class="text-right nowrap" style="color: #111827;"><strong>${{ number_format($row->total_sales_amount, 2) }}</strong></td>
                        <td class="text-right nowrap" style="color: #64748b;">${{ number_format($row->avg_unit_cost, 2) }}</td>
                        <td class="text-right nowrap" style="color: #475569;">${{ number_format($row->total_cost_amount, 2) }}</td>
                        <td class="text-right nowrap" style="color: {{ $row->total_profit >= 0 ? '#16a34a' : '#dc2626' }};"><strong>${{ number_format($row->total_profit, 2) }}</strong></td>
                        <td class="text-right nowrap" style="color: {{ $row->margin_percentage >= 0 ? '#0284c7' : '#dc2626' }};"><strong>{{ number_format($row->margin_percentage, 1) }}%</strong></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center">No se encontraron registros de ventas en este período.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr style="background-color: #f1f5f9; font-weight: bold;">
                    <td colspan="3" class="text-right">TOTALES:</td>
                    <td class="text-right nowrap">{{ number_format($kpis['total_qty_sold'], 2) }}</td>
                    <td></td>
                    <td class="text-right nowrap" style="color: #111827;">${{ number_format($kpis['total_amount_sold'], 2) }}</td>
                    <td></td>
                    <td class="text-right nowrap" style="color: #475569;">${{ number_format($kpis['total_cost_sold'], 2) }}</td>
                    <td class="text-right nowrap" style="color: {{ ($kpis['total_profit'] ?? 0) >= 0 ? '#16a34a' : '#dc2626' }};">${{ number_format($kpis['total_profit'], 2) }}</td>
                    <td class="text-right nowrap" style="color: {{ ($kpis['margin_percentage'] ?? 0) >= 0 ? '#0284c7' : '#dc2626' }};">{{ number_format($kpis['margin_percentage'], 1) }}%</td>
                </tr>
            </tfoot>
        </table>
    @elseif($viewMode === 'detailed')
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 9%;" class="text-center nowrap">Fecha</th>
                    <th style="width: 7%;" class="text-center nowrap">Factura</th>
                    <th style="width: 12%;">Cliente</th>
                    <th style="width: 12%;">Socio / Origen</th>
                    <th style="width: 18%;">Producto</th>
                    <th class="text-right nowrap" style="width: 5%;">Cant.</th>
                    <th class="text-right nowrap" style="width: 6%;">P. Unit ($)</th>
                    <th class="text-right nowrap" style="width: 7%;">Total ($)</th>
                    <th class="text-right nowrap" style="width: 6%;">Costo U. ($)</th>
                    <th class="text-right nowrap" style="width: 7%;">Costo T. ($)</th>
                    <th class="text-right nowrap" style="width: 6%;">Ganancia ($)</th>
                    <th class="text-right nowrap" style="width: 5%;">Margen</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $row)
                    @php
                        $uCost = $row->unit_cost ?? ($row->product->cost ?? 0);
                        $tCost = $row->total_cost ?? ($row->quantity * $uCost);
                        $profit = $row->profit ?? ($row->total_price - $tCost);
                        $margin = $row->total_price > 0 ? ($profit / $row->total_price) * 100 : 0;
                    @endphp
                    <tr>
                        <td class="text-center nowrap">{{ \Carbon\Carbon::parse($row->created_at)->format('d/m/Y H:i') }}</td>
                        <td class="text-center nowrap">#{{ $row->sale->invoice_number ?? $row->sale_id }}</td>
                        <td>{{ $row->sale->customer->name ?? 'Cliente Mostrador' }}</td>
                        <td>{{ $row->originWarehouse->name ?? 'Directo Tienda' }}</td>
                        <td>{{ $row->product->name ?? 'Producto' }}</td>
                        <td class="text-right nowrap">{{ number_format($row->quantity, 2) }}</td>
                        <td class="text-right nowrap">${{ number_format($row->unit_price, 2) }}</td>
                        <td class="text-right nowrap" style="color: #111827;">${{ number_format($row->total_price, 2) }}</td>
                        <td class="text-right nowrap" style="color: #64748b;">${{ number_format($uCost, 2) }}</td>
                        <td class="text-right nowrap" style="color: #475569;">${{ number_format($tCost, 2) }}</td>
                        <td class="text-right nowrap" style="color: {{ $profit >= 0 ? '#16a34a' : '#dc2626' }};"><strong>${{ number_format($profit, 2) }}</strong></td>
                        <td class="text-right nowrap" style="color: {{ $margin >= 0 ? '#0284c7' : '#dc2626' }};"><strong>{{ number_format($margin, 1) }}%</strong></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="text-center">No se encontraron registros detallados para este período.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr style="background-color: #f1f5f9; font-weight: bold;">
                    <td colspan="5" class="text-right">TOTALES:</td>
                    <td class="text-right nowrap">{{ number_format($kpis['total_qty_sold'], 2) }}</td>
                    <td></td>
                    <td class="text-right nowrap" style="color: #111827;">${{ number_format($kpis['total_amount_sold'], 2) }}</td>
                    <td></td>
                    <td class="text-right nowrap" style="color: #475569;">${{ number_format($kpis['total_cost_sold'], 2) }}</td>
                    <td class="text-right nowrap" style="color: {{ ($kpis['total_profit'] ?? 0) >= 0 ? '#16a34a' : '#dc2626' }};">${{ number_format($kpis['total_profit'], 2) }}</td>
                    <td class="text-right nowrap" style="color: {{ ($kpis['margin_percentage'] ?? 0) >= 0 ? '#0284c7' : '#dc2626' }};">{{ number_format($kpis['margin_percentage'], 1) }}%</td>
                </tr>
            </tfoot>
        </table>
    @elseif($viewMode === 'origin_stock')
        @php
            $sumQty = 0;
            $sumVal = 0;
        @endphp
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 16%;">Socio / Depósito Origen</th>
                    <th style="width: 15%;">Responsable</th>
                    <th style="width: 12%;" class="nowrap">Código / SKU</th>
                    <th style="width: 11%;">Categoría</th>
                    <th style="width: 26%;">Producto</th>
                    <th class="text-right nowrap" style="width: 6%;">Costo ($)</th>
                    <th class="text-right nowrap" style="width: 6%;">Stock Disp.</th>
                    <th class="text-right nowrap" style="width: 8%;">Total Valor ($)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $row)
                    @php
                        $val = ($row->stock_qty ?? 0) * ($row->product->cost ?? 0);
                        $sumQty += ($row->stock_qty ?? 0);
                        $sumVal += $val;
                    @endphp
                    <tr>
                        <td><strong>{{ $row->warehouse->name ?? 'N/A' }}</strong></td>
                        <td>{{ $row->warehouse->partner_name ?? '-' }}</td>
                        <td class="nowrap">{{ $row->product->sku ?? 'S/C' }}</td>
                        <td>{{ $row->product->category->name ?? 'General' }}</td>
                        <td>{{ $row->product->name ?? 'Producto' }}</td>
                        <td class="text-right nowrap">${{ number_format($row->product->cost ?? 0, 2) }}</td>
                        <td class="text-right nowrap"><strong>{{ number_format($row->stock_qty, 2) }}</strong></td>
                        <td class="text-right nowrap" style="color: #27ae60;"><strong>${{ number_format($val, 2) }}</strong></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center">No se encontraron existencias en los depósitos de los socios.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr style="background-color: #f1f5f9; font-weight: bold;">
                    <td colspan="6" class="text-right">TOTALES:</td>
                    <td class="text-right nowrap">{{ number_format($sumQty, 2) }}</td>
                    <td class="text-right nowrap" style="color: #27ae60;">${{ number_format($sumVal, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    @elseif($viewMode === 'consignment_stock')
        @php
            $sumInit = 0;
            $sumConsumed = 0;
            $sumRem = 0;
            $sumValRem = 0;
        @endphp
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 11%;">Socio / Origen</th>
                    <th style="width: 11%;">Tienda Destino</th>
                    <th style="width: 6%;" class="text-center nowrap">N° Trasp.</th>
                    <th style="width: 8%;" class="text-center nowrap">Fecha</th>
                    <th style="width: 11%;" class="nowrap">SKU</th>
                    <th style="width: 25%;">Producto</th>
                    <th class="text-right nowrap" style="width: 7%;">Traspasado</th>
                    <th class="text-right nowrap" style="width: 7%;">Vendido</th>
                    <th class="text-right nowrap" style="width: 7%;">Remanente</th>
                    <th class="text-right nowrap" style="width: 7%;">Costo ($)</th>
                    <th class="text-right nowrap" style="width: 8%;">Total Rem. ($)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $row)
                    @php
                        $unitCost = (float) ($row->unit_cost ?: ($row->cost_price ?: ($row->product->cost ?? 0)));
                        $valRem = (float) ($row->remaining_quantity ?? 0) * $unitCost;
                        $sumInit += ($row->initial_quantity ?? 0);
                        $sumConsumed += ($row->consumed_quantity ?? 0);
                        $sumRem += ($row->remaining_quantity ?? 0);
                        $sumValRem += $valRem;
                    @endphp
                    <tr>
                        <td><strong>{{ $row->originWarehouse->name ?? 'Origen' }}</strong></td>
                        <td>{{ $row->destinationWarehouse->name ?? 'Tienda' }}</td>
                        <td class="text-center nowrap">#{{ $row->transfer_id }}</td>
                        <td class="text-center nowrap">{{ \Carbon\Carbon::parse($row->created_at)->format('d/m/Y') }}</td>
                        <td class="nowrap">{{ $row->product->sku ?? 'S/C' }}</td>
                        <td>{{ $row->product->name ?? 'Producto' }}</td>
                        <td class="text-right nowrap">{{ number_format($row->initial_quantity, 2) }}</td>
                        <td class="text-right nowrap text-muted">{{ number_format($row->consumed_quantity, 2) }}</td>
                        <td class="text-right nowrap"><strong>{{ number_format($row->remaining_quantity, 2) }}</strong></td>
                        <td class="text-right nowrap">${{ number_format($unitCost, 2) }}</td>
                        <td class="text-right nowrap" style="color: #27ae60;"><strong>${{ number_format($valRem, 2) }}</strong></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center">No hay stock consignado pendiente de venta en tiendas.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr style="background-color: #f1f5f9; font-weight: bold;">
                    <td colspan="6" class="text-right">TOTALES:</td>
                    <td class="text-right nowrap">{{ number_format($sumInit, 2) }}</td>
                    <td class="text-right nowrap">{{ number_format($sumConsumed, 2) }}</td>
                    <td class="text-right nowrap">{{ number_format($sumRem, 2) }}</td>
                    <td></td>
                    <td class="text-right nowrap" style="color: #27ae60;">${{ number_format($sumValRem, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    @endif

    <div class="footer">
        Página generada por Sistema JSPOS Sales
    </div>

</body>
</html>