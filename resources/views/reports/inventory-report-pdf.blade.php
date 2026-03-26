<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Inventario / Stock</title>
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
        }
        .header td {
            vertical-align: top;
        }
        .business-info h2 {
            margin: 0;
            font-size: 11pt;
        }
        .report-info {
            text-align: right;
            font-size: 8pt;
        }
        .report-title {
            text-align: center;
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 15px;
            text-decoration: underline;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table th {
            background-color: #f2f2f2;
            border: 1px solid #999;
            padding: 2px 4px;
            text-align: left;
            font-size: 7.5pt;
            text-transform: uppercase;
        }
        .table td {
            padding: 2px 4px;
            border-bottom: 1px solid #ddd;
            border-left: 0.5pt solid #eee;
            border-right: 0.5pt solid #eee;
            font-size: 7.2pt;
            vertical-align: middle;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        
        .driver-header {
            background-color: #e9e9e9;
            font-weight: bold;
            padding: 4px;
            border: 1px solid #999;
            margin-top: 10px;
        }

        .summary-box {
            width: 40%;
            margin-left: auto;
            border-top: 1.5pt solid #000;
            margin-top: 10px;
            padding-top: 5px;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-label {
            font-weight: bold;
            font-size: 8pt;
        }
        .summary-value {
            text-align: right;
            font-size: 8pt;
            font-weight: bold;
        }

        .footer-signatures {
            width: 100%;
            margin-top: 50px;
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
                Fecha : {{ \Carbon\Carbon::now()->format('d/m/Y') }}<br>
                Hora : {{ \Carbon\Carbon::now()->format('h:i:s a') }}<br>
                Generado por : {{ strtoupper($user->name ?? 'N/A') }}
            </td>
        </tr>
    </table>

    <div class="report-title">REPORTE DE INVENTARIO Y STOCK</div>
    
    <div style="margin-bottom: 10px; font-size: 8pt;">
        @if($supplier_name != 'Todos') Proveedor : {{ $supplier_name }}<br> @endif
        @if($category_name != 'Todas') Categoría : {{ $category_name }}<br> @endif
        Moneda : Dólares (USD)
    </div>

    <table class="table">
        <thead>
            <tr>
                @if($columns['sku']) <th style="width: 60px;">SKU</th> @endif
                @if($columns['name']) <th>NOMBRE PRODUCTO</th> @endif
                @if($columns['category']) <th style="width: 80px;">CATEGORÍA</th> @endif
                @if($columns['supplier']) <th style="width: 80px;">PROVEEDOR</th> @endif
                @if($columns['stock']) <th style="width: 50px;" class="text-center">STOCK</th> @endif
                @if($columns['physical_inventory']) <th style="width: 60px;" class="text-center">FISICO</th> @endif
                @if($columns['cost']) <th style="width: 60px;" class="text-right">COSTO</th> @endif
                @if($columns['price']) <th style="width: 60px;" class="text-right">PRECIO</th> @endif
                @if($columns['utility_percent']) <th style="width: 50px;" class="text-center">UT. %</th> @endif
                @if($columns['valuation_cost']) <th style="width: 70px;" class="text-right">VAL. COSTO</th> @endif
                @if($columns['valuation_price']) <th style="width: 70px;" class="text-right">VAL. VENTA</th> @endif
            </tr>
        </thead>
        <tbody>
            @foreach ($products as $product)
                <tr>
                    @if($columns['sku']) <td>{{ $product->sku }}</td> @endif
                    @if($columns['name']) <td>{{ strtoupper($product->name) }}</td> @endif
                    @if($columns['category']) <td>{{ strtoupper($product->category->name ?? 'N/A') }}</td> @endif
                    @if($columns['supplier']) <td>{{ strtoupper($product->supplier->name ?? 'N/A') }}</td> @endif
                    @if($columns['stock']) <td class="text-center font-bold">{{ $product->stock_qty }}</td> @endif
                    @if($columns['physical_inventory']) 
                        <td class="text-center">
                            <div style="border: 0.5pt solid #999; height: 12px; width: 40px; margin: 0 auto;"></div>
                        </td> 
                    @endif
                    @if($columns['cost']) <td class="text-right">{{ number_format($product->cost, 2) }}</td> @endif
                    @if($columns['price']) <td class="text-right">{{ number_format($product->price, 2) }}</td> @endif
                    @if($columns['utility_percent']) 
                        <td class="text-center">
                            {{ $product->cost > 0 ? number_format((($product->price - $product->cost) / $product->cost) * 100, 2) . '%' : '0%' }}
                        </td> 
                    @endif
                    @if($columns['valuation_cost']) <td class="text-right">{{ number_format($product->stock_qty * $product->cost, 2) }}</td> @endif
                    @if($columns['valuation_price']) <td class="text-right font-bold">{{ number_format($product->stock_qty * $product->price, 2) }}</td> @endif
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary-box">
        <table class="summary-table">
            @if($columns['valuation_cost'])
            <tr>
                <td class="summary-label">TOTAL VALUACIÓN (COSTO):</td>
                <td class="summary-value">${{ number_format($totals['cost'], 2) }}</td>
            </tr>
            @endif
            @if($columns['valuation_price'])
            <tr>
                <td class="summary-label">TOTAL VALUACIÓN (VENTA):</td>
                <td class="summary-value">${{ number_format($totals['price'], 2) }}</td>
            </tr>
            @endif
            <tr>
                <td class="summary-label">TOTAL ITEMS EN STOCK:</td>
                <td class="summary-value">{{ number_format($totals['items'], 0) }}</td>
            </tr>
        </table>
    </div>

    <table class="footer-signatures" style="width: 100%; border-collapse: collapse;">
        <tr>
            @if($signatures['elaborado'])
                <td style="width: 25%; text-align: center; vertical-align: bottom;">
                    <div class="signature-line"></div>
                    <div style="font-size: 7.5pt; font-weight: bold;">ELABORADO POR</div>
                </td>
            @endif
            @if($signatures['autorizado'])
                <td style="width: 25%; text-align: center; vertical-align: bottom;">
                    <div class="signature-line"></div>
                    <div style="font-size: 7.5pt; font-weight: bold;">AUTORIZADO POR</div>
                </td>
            @endif
            @if($signatures['gerente'])
                <td style="width: 25%; text-align: center; vertical-align: bottom;">
                    <div class="signature-line"></div>
                    <div style="font-size: 7.5pt; font-weight: bold;">GERENCIA</div>
                </td>
            @endif
            @if($signatures['auditoria'])
                <td style="width: 25%; text-align: center; vertical-align: bottom;">
                    <div class="signature-line"></div>
                    <div style="font-size: 7.5pt; font-weight: bold;">AUDITORÍA / ALMACÉN</div>
                </td>
            @endif
        </tr>
    </table>

</body>
</html>
