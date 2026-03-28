<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Movimientos de Inventario (Kardex)</title>
    <style>
        @page { margin: 1cm; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10px; color: #333; line-height: 1.4; }
        .header { margin-bottom: 20px; border-bottom: 2px solid #222; padding-bottom: 10px; }
        .logo { width: 120px; float: left; }
        .company-info { float: left; margin-left: 20px; width: 300px; }
        .report-info { float: right; text-align: right; width: 250px; }
        .clear { clear: both; }
        .report-title { font-size: 18px; font-weight: bold; color: #222; margin: 10px 0; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
        
        .summary-box { width: 100%; border-collapse: collapse; margin-bottom: 20px; background-color: #f9f9f9; }
        .summary-box td { padding: 10px; border: 1px solid #ddd; }
        .summary-title { font-weight: bold; color: #555; font-size: 9px; text-transform: uppercase; }
        .summary-value { font-size: 14px; font-weight: bold; color: #000; }
        
        table.movements { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.movements th { background-color: #2c3e50; color: #fff; padding: 8px; text-align: left; border: 1px solid #2c3e50; font-size: 8px; }
        table.movements td { padding: 6px; border: 1px solid #ddd; vertical-align: middle; }
        table.movements tr:nth-child(even) { background-color: #f2f2f2; }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .text-success { color: #27ae60; }
        .text-danger { color: #e74c3c; }
        .text-primary { color: #2980b9; }

        .tag { padding: 2px 4px; border-radius: 3px; font-size: 7px; text-transform: uppercase; font-weight: bold; color: #fff; }
        .tag-venta { background-color: #f39c12; }
        .tag-compra { background-color: #27ae60; }
        .tag-cargo { background-color: #3498db; }
        .tag-descargo { background-color: #e74c3c; }
        .tag-nc { background-color: #8e44ad; }
        
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 8px; color: #777; border-top: 1px solid #ddd; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">
            @if($config->logo && file_exists(public_path('storage/'.$config->logo)))
                <img src="{{ public_path('storage/'.$config->logo) }}" style="max-height: 50px;">
            @else
                <h2 style="margin: 0;">{{ $config->name }}</h2>
            @endif
        </div>
        <div class="company-info">
            <div class="font-bold" style="font-size: 11px;">{{ strtoupper($config->name) }}</div>
            <div>NIT/RUC: {{ $config->tax_id }} | Tel: {{ $config->phone }}</div>
            <div>{{ $config->address }}</div>
        </div>
        <div class="report-info">
            <div class="report-title">KARDEX DE PRODUCTO</div>
            <div>Fecha: {{ now()->format('d/m/Y H:i') }}</div>
            <div>Depósito: <strong>{{ $warehouse_name }}</strong></div>
        </div>
        <div class="clear"></div>
    </div>

    <div style="margin-bottom: 10px; background: #eee; padding: 8px; border-radius: 5px;">
        <table width="100%">
            <tr>
                <td width="70%">
                    <div style="font-size: 12px;">PRODUCTO: <strong>{{ $product->sku }} - {{ strtoupper($product->name) }}</strong></div>
                    <div style="color: #555;">Categoría: {{ $product->category->name ?? 'N/A' }} | Proveedor: {{ $product->supplier->name ?? 'N/A' }}</div>
                </td>
                <td width="30%" class="text-right">
                    <div style="color: #666;">Período:</div>
                    <div class="font-bold">{{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="summary-box">
        <tr>
            <td width="25%">
                <div class="summary-title">Stock Inicial</div>
                <div class="summary-value">{{ number_format($initialStock, 2) }}</div>
            </td>
            <td width="25%">
                <div class="summary-title text-success">Entradas (+)</div>
                <div class="summary-value text-success">{{ number_format($totalIn, 2) }}</div>
            </td>
            <td width="25%">
                <div class="summary-title text-danger">Salidas (-)</div>
                <div class="summary-value text-danger">{{ number_format($totalOut, 2) }}</div>
            </td>
            <td width="25%" style="background-color: #eefbff; border: 2px solid #2980b9;">
                <div class="summary-title text-primary">Existencia Final</div>
                <div class="summary-value text-primary">{{ number_format($finalStock, 2) }}</div>
            </td>
        </tr>
    </table>

    <table class="movements">
        <thead>
            <tr>
                <th width="12%" class="text-center">FECHA</th>
                <th width="10%">TIPO</th>
                <th width="8%" class="text-center">REF #</th>
                <th width="15%">DEPÓSITO</th>
                <th width="20%">DETALLE</th>
                <th width="10%">OPERADOR</th>
                <th width="8%" class="text-center">ENTRADA</th>
                <th width="8%" class="text-center">SALIDA</th>
                <th width="9%" class="text-center">SALDO</th>
            </tr>
        </thead>
        <tbody>
            @php $currentBalance = $initialStock; @endphp
            @forelse($movements as $m)
                @php 
                    $currentBalance += ($m->quantity_in - $m->quantity_out);
                    $typeClass = 'tag-cargo';
                    if($m->type == 'Venta') $typeClass = 'tag-venta';
                    elseif($m->type == 'Compra') $typeClass = 'tag-compra';
                    elseif($m->type == 'Descargo (Salida)') $typeClass = 'tag-descargo';
                    elseif($m->type == 'Devolución (NC)') $typeClass = 'tag-nc';
                @endphp
                <tr>
                    <td class="text-center">{{ \Carbon\Carbon::parse($m->movement_date)->format('d/m/Y H:i') }}</td>
                    <td><span class="tag {{ $typeClass }}">{{ $m->type }}</span></td>
                    <td class="text-center">{{ $m->reference }}</td>
                    <td class="font-bold text-primary">{{ $m->warehouse_name ?? '-' }}</td>
                    <td>{{ $m->detail ?? '-' }}</td>
                    <td>{{ $m->operator }}</td>
                    <td class="text-right text-success">
                        {{ $m->quantity_in > 0 ? number_format($m->quantity_in, 2) : '' }}
                    </td>
                    <td class="text-right text-danger">
                        {{ $m->quantity_out > 0 ? number_format($m->quantity_out, 2) : '' }}
                    </td>
                    <td class="text-right font-bold {{ $currentBalance < 0 ? 'text-danger' : 'text-primary' }}">
                        {{ number_format($currentBalance, 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">No se encontraron movimientos registrados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        {{ $config->name }} - Reporte generado por {{ $user->name }} en {{ now()->format('d/m/Y H:i:s') }}
    </div>
</body>
</html>
