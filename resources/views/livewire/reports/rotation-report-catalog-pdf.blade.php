<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Catálogo de Ofertas y Venta Cruzada</title>
    <style>
        body {
            font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
            font-size: 11px;
        }
        .header-container {
            border-bottom: 2px solid #1a237e;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .logo-cell {
            vertical-align: middle;
        }
        .business-title {
            font-size: 16px;
            font-weight: bold;
            color: #1a237e;
            margin: 0;
        }
        .report-title {
            font-size: 14px;
            font-weight: bold;
            color: #444;
            margin: 5px 0 0 0;
            text-transform: uppercase;
        }
        .meta-cell {
            text-align: right;
            font-size: 9px;
            color: #555;
            line-height: 1.3;
        }
        .customer-card {
            background-color: #f5f5f5;
            border-left: 4px solid #1a237e;
            padding: 10px;
            margin-bottom: 20px;
        }
        .customer-card h3 {
            margin: 0 0 5px 0;
            color: #1a237e;
            font-size: 12px;
        }
        .customer-card p {
            margin: 0;
            color: #555;
            font-size: 10px;
            line-height: 1.3;
        }
        .intro-text {
            font-size: 10.5px;
            line-height: 1.5;
            margin-bottom: 20px;
            color: #444;
        }
        .catalog-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .catalog-table th {
            background-color: #1a237e;
            color: white;
            font-weight: bold;
            text-align: left;
            padding: 8px 6px;
            font-size: 10px;
            text-transform: uppercase;
        }
        .catalog-table td {
            padding: 8px 6px;
            border-bottom: 1px solid #e0e0e0;
            vertical-align: middle;
        }
        .catalog-table tr:nth-child(even) {
            background-color: #fcfcfc;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .price-text {
            font-weight: bold;
            color: #2e7d32;
        }
        .order-box {
            width: 60px;
            height: 16px;
            border: 1px solid #999;
            background-color: #fff;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #888;
            border-top: 1px solid #e0e0e0;
            padding-top: 5px;
        }
        .page-number:before {
            content: "Página " counter(page);
        }
    </style>
</head>
<body>

    <div class="header-container">
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    <h1 class="business-title">{{ $config->business_name ?? 'MI NEGOCIO' }}</h1>
                    <div class="report-title">Catálogo de Productos Sugeridos</div>
                </td>
                <td class="meta-cell">
                    RIF: <strong>{{ $config->taxpayer_id ?? '' }}</strong><br>
                    Fecha de Emisión: <strong>{{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</strong><br>
                    Etiqueta Filtro: <strong>{{ $tagName }}</strong>
                </td>
            </tr>
        </table>
    </div>

    <div class="customer-card">
        <h3>Propuesta de Venta Cruzada Preparada para:</h3>
        <p>
            <strong>Cliente:</strong> {{ $customer->name }} (ID: {{ $customer->id }})<br>
            <strong>Documento/RIF:</strong> {{ $customer->taxpayer_id ?? 'N/A' }} | <strong>Teléfono:</strong> {{ $customer->phone ?? 'N/A' }}<br>
            <strong>Dirección:</strong> {{ $customer->address ?? 'N/A' }}
        </p>
    </div>

    <div class="intro-text">
        Estimado cliente, en base a su historial de compras en nuestro sistema, hemos preparado esta selección de productos que actualmente **no forman parte de sus pedidos habituales**, pero que complementan perfectamente su portafolio comercial y se encuentran disponibles para entrega inmediata en nuestro almacén. 
    </div>

    <table class="catalog-table">
        <thead>
            <tr>
                <th width="15%">Código/SKU</th>
                <th width="45%">Descripción del Producto</th>
                <th width="15%" class="text-center">Categoría</th>
                <th width="13%" class="text-right">Precio Unit. (USD)</th>
                <th width="12%" class="text-center">Pedido (Cant)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($products as $product)
                <tr>
                    <td>{{ $product->sku }}</td>
                    <td><strong>{{ $product->name }}</strong></td>
                    <td class="text-center">{{ $product->category_name ?? 'N/A' }}</td>
                    <td class="text-right price-text">${{ number_format($product->price_usd ?? $product->price, 2) }}</td>
                    <td class="text-center">
                        <div class="order-box"></div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        {{ $config->business_name ?? '' }} | Teléfono: {{ $config->phone ?? '' }} | Email: {{ $config->email ?? '' }}<br>
        <span class="page-number"></span>
    </div>

</body>
</html>
