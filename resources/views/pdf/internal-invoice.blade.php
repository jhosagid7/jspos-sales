<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante Contable Interno</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .info-table { width: 100%; margin-bottom: 20px; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th, .items-table td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        .items-table th { background-color: #f2f2f2; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .totals-table { width: 100%; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #666; }
        .watermark { 
            position: fixed; top: 30%; left: 30%; transform: rotate(-45deg); 
            font-size: 60px; color: rgba(200, 200, 200, 0.4); 
            border: 5px solid rgba(200, 200, 200, 0.4); padding: 20px; 
        }
    </style>
</head>
<body>

    <div class="watermark">USO INTERNO</div>

    <div class="header">
        <h2>{{ strtoupper($company->business_name) }}</h2>
        <h3>COMPROBANTE CONTABLE INTERNO</h3>
        <p>NO ENTREGAR AL CLIENTE</p>
    </div>

    <table class="info-table">
        <tr>
            <td width="50%">
                <strong>Folio:</strong> {{ $sale->invoice_number }}<br>
                <strong>Fecha:</strong> {{ $sale->created_at->format('d/m/Y h:i A') }}<br>
                <strong>Vendedor:</strong> {{ $sale->customer->seller->name ?? 'N/A' }}
            </td>
            <td width="50%">
                <strong>Cliente:</strong> {{ $sale->customer->name }}<br>
                <strong>CC/NIT:</strong> {{ $sale->customer->taxpayer_id }}<br>
                <strong>Teléfono:</strong> {{ $sale->customer->phone }}
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th>Cant</th>
                <th>Descripción</th>
                <th class="text-right">Precio Real (Base)</th>
                <th class="text-right">Total Base</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td class="text-center">{{ $item['quantity'] }}</td>
                <td>{{ $item['name'] }}</td>
                <td class="text-right">{{ $currencySymbol }}{{ number_format($item['base_price'], 2) }}</td>
                <td class="text-right">{{ $currencySymbol }}{{ number_format($item['total_base'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td width="60%"></td>
            <td width="40%">
                <table width="100%">
                    <tr>
                        <td class="text-right"><strong>Subtotal Base (Real):</strong></td>
                        <td class="text-right"><strong>{{ $currencySymbol }}{{ number_format($subtotalBase, 2) }}</strong></td>
                    </tr>
                    
                    @if($commPercent > 0)
                    <tr>
                        <td class="text-right">Comisión ({{ number_format($commPercent, 2) }}%):</td>
                        <td class="text-right">{{ $currencySymbol }}{{ number_format($commAmount, 2) }}</td>
                    </tr>
                    @endif
                    
                    @if($freightPercent > 0)
                    <tr>
                        <td class="text-right">Flete ({{ number_format($freightPercent, 2) }}%):</td>
                        <td class="text-right">{{ $currencySymbol }}{{ number_format($freightAmount, 2) }}</td>
                    </tr>
                    @endif
                    
                    @if($diffPercent > 0)
                    <tr>
                        <td class="text-right">Dif. Cambiaria ({{ number_format($diffPercent, 2) }}%):</td>
                        <td class="text-right">{{ $currencySymbol }}{{ number_format($diffAmount, 2) }}</td>
                    </tr>
                    @endif
                    
                    <tr>
                        <td colspan="2"><hr></td>
                    </tr>
                    <tr>
                        <td class="text-right"><strong>TOTAL FACTURADO:</strong></td>
                        <td class="text-right"><strong>{{ $currencySymbol }}{{ number_format($sale->total, 2) }}</strong></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="footer">
        <p>Este documento es para control administrativo y contable.<br>Refleja los costos reales y la estructura de precios sin los recargos comerciales visibles al cliente.</p>
    </div>

</body>
</html>
