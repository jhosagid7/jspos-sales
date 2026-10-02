<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Rentabilidad y Precios Mensuales</title>
    <style>
        @page { margin: 1cm; size: landscape; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 9px; color: #333; line-height: 1.4; }
        .header { margin-bottom: 15px; border-bottom: 2px solid #222; padding-bottom: 8px; }
        .logo { width: 120px; float: left; }
        .company-info { float: left; margin-left: 20px; width: 350px; }
        .report-info { float: right; text-align: right; width: 300px; }
        .clear { clear: both; }
        .report-title { font-size: 16px; font-weight: bold; color: #222; margin: 8px 0; border-bottom: 1px solid #ddd; padding-bottom: 4px; }
        
        .summary-box { width: 100%; border-collapse: collapse; margin-bottom: 15px; background-color: #f9f9f9; }
        .summary-box td { padding: 8px; border: 1px solid #ddd; }
        .summary-title { font-weight: bold; color: #555; font-size: 8px; text-transform: uppercase; }
        .summary-value { font-size: 13px; font-weight: bold; color: #000; }
        
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.data-table th { background-color: #2c3e50; color: #fff; padding: 6px; text-align: center; border: 1px solid #2c3e50; font-size: 8px; }
        table.data-table td { padding: 5px; border: 1px solid #ddd; vertical-align: middle; }
        table.data-table tr:nth-child(even) { background-color: #f8f9fa; }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .text-success { color: #27ae60; }
        .text-danger { color: #e74c3c; }
        .text-primary { color: #2980b9; }

        .price-badge { background-color: #eef2f7; border: 1px solid #cbd5e1; border-radius: 3px; padding: 2px 4px; font-size: 8px; display: inline-block; margin-bottom: 2px; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 8px; color: #777; border-top: 1px solid #ddd; padding-top: 4px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-info" style="margin-left: 0;">
            <h2 style="margin: 0; font-size: 16px;">{{ $config->business_name ?? 'JSPOS SALES' }}</h2>
            <div>RIF / NIT: {{ $config->taxpayer_id ?? '' }}</div>
            <div>{{ $config->address ?? '' }}</div>
            <div>Teléfono: {{ $config->phone ?? '' }}</div>
        </div>
        <div class="report-info">
            <div class="report-title" style="margin-top: 0;">RENTABILIDAD MENSUAL POR PRODUCTO</div>
            <div><strong>Producto:</strong> {{ $product->sku ? $product->sku . ' - ' : '' }}{{ $product->name }}</div>
            <div><strong>Período:</strong> {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}</div>
            <div><strong>Almacén:</strong> {{ $warehouseName }}</div>
            <div><strong>Emisión:</strong> {{ date('d/m/Y H:i:s') }}</div>
        </div>
        <div class="clear"></div>
    </div>

    <!-- RESUMEN EN TARJETAS -->
    <table class="summary-box">
        <tr>
            <td width="20%">
                <div class="summary-title">Total Unidades Vendidas</div>
                <div class="summary-value text-primary">{{ number_format($totals['total_qty'], 2) }}</div>
            </td>
            <td width="20%">
                <div class="summary-title">Total Venta Facturada</div>
                <div class="summary-value">${{ number_format($totals['total_sold'], 2) }}</div>
            </td>
            <td width="20%">
                <div class="summary-title">Total Costo Mercancía</div>
                <div class="summary-value">${{ number_format($totals['total_cost'], 2) }}</div>
            </td>
            <td width="20%">
                <div class="summary-title">Ganancia Neta</div>
                <div class="summary-value {{ $totals['total_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                    ${{ number_format($totals['total_profit'], 2) }}
                </div>
            </td>
            <td width="20%">
                <div class="summary-title">Margen Promedio</div>
                <div class="summary-value text-primary">{{ number_format($totals['margin_percent'], 2) }}%</div>
            </td>
        </tr>
    </table>

    <!-- TABLA MENSUAL -->
    <table class="data-table">
        <thead>
            <tr>
                <th width="14%">Mes / Período</th>
                <th width="20%">Precio(s) de Costo</th>
                <th width="24%">Precio(s) de Venta</th>
                <th width="8%">Cant. Vendida</th>
                <th width="11%">Total Costo ($)</th>
                <th width="11%">Total Venta ($)</th>
                <th width="12%">Ganancia ($)</th>
                <th width="10%">Margen (%)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($monthlyData as $row)
            <tr>
                <td class="font-bold text-left">{{ $row['period'] }}</td>
                <td class="text-left">
                    @if($row['has_cost_changes'])
                        <div><strong>Varió en el mes:</strong></div>
                        @foreach($row['cost_breakdown'] as $cb)
                            <div style="font-size: 8px;">
                                • ${{ number_format($cb['cost'], 2) }} ({{ number_format($cb['qty'], 0) }} unds - {{ $cb['source'] }})
                            </div>
                        @endforeach
                        <div style="font-weight: bold; color: #2980b9;">Prom: ${{ number_format($row['cost_unit'], 2) }}</div>
                    @else
                        ${{ number_format($row['cost_unit'], 2) }}
                    @endif
                </td>
                <td class="text-left">
                    @if(empty($row['price_breakdown']))
                        <span style="color: #999; font-style: italic;">Sin ventas</span>
                    @elseif($row['has_price_changes'])
                        <div><strong>Múltiples precios:</strong></div>
                        @foreach($row['price_breakdown'] as $pb)
                            <div class="price-badge">
                                <b>${{ number_format($pb['price'], 2) }}</b> ({{ number_format($pb['qty'], 2) }} unds &rarr; ${{ number_format($pb['subtotal'], 2) }})
                            </div>
                        @endforeach
                    @else
                        <b>${{ number_format($row['price_breakdown'][0]['price'], 2) }}</b>
                    @endif
                </td>
                <td class="text-center font-bold">{{ number_format($row['sold_qty'], 2) }}</td>
                <td class="text-right">${{ number_format($row['cost_amount'], 2) }}</td>
                <td class="text-right font-bold">${{ number_format($row['sold_amount'], 2) }}</td>
                <td class="text-right font-bold {{ $row['profit_amount'] >= 0 ? 'text-success' : 'text-danger' }}">
                    ${{ number_format($row['profit_amount'], 2) }}
                </td>
                <td class="text-center font-bold">
                    {{ $row['sold_amount'] > 0 ? number_format($row['margin_percent'], 2) . '%' : '0.00%' }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center" style="padding: 15px; color: #777;">
                    No se registraron movimientos en el período seleccionado.
                </td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="background-color: #eaeded; font-weight: bold;">
                <td class="text-left font-bold" colspan="3">SUMATORIA TOTAL:</td>
                <td class="text-center text-primary font-bold">{{ number_format($totals['total_qty'], 2) }}</td>
                <td class="text-right">${{ number_format($totals['total_cost'], 2) }}</td>
                <td class="text-right">${{ number_format($totals['total_sold'], 2) }}</td>
                <td class="text-right {{ $totals['total_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                    ${{ number_format($totals['total_profit'], 2) }}
                </td>
                <td class="text-center font-bold">{{ number_format($totals['margin_percent'], 2) }}%</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Documento generado automáticamente por Sistema JSPOS Sales &copy; {{ date('Y') }} - Página 1
    </div>
</body>
</html>
