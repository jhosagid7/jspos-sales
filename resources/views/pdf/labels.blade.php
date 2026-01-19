<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Etiquetas de Productos</title>
    <style>
        @page {
            margin: 0.2cm; /* Reduced margin to fit more rows */
            size: letter;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            display: table;
            border-collapse: collapse;
        }
        .row {
            display: table-row;
        }
        .label-cell {
            display: table-cell;
            width: 25%;
            padding: 0 2px; /* Remove vertical padding */
            vertical-align: top;
            padding-bottom: 2px; /* Minimal bottom spacing */
        }
        .label-box {
            width: 5cm;
            height: 3.5cm; /* Restored height */
            border: 3px solid black;
            margin: 0 auto;
            padding: 2px;
            box-sizing: border-box;
            position: relative;
            overflow: hidden;
        }
        .product-name {
            font-size: 13px; /* Restored font size */
            font-weight: bold;
            text-align: center;
            height: 1.4cm; /* Restored height */
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1.0;
            margin-bottom: 2px;
            margin-top: 2px;
            width: 100%;
            padding: 0 1px;
            word-break: break-word;
        }
        .info-row {
            font-size: 8px; /* Restored font size */
            margin: 0;
            width: 100%;
            padding-left: 2px;
        }
        .info-line {
            margin-bottom: 1px;
            white-space: nowrap;
        }
        .barcode-container {
            position: absolute;
            bottom: 1px;
            left: 0;
            width: 100%;
            text-align: center;
        }
        .barcode-img {
            height: 0.8cm; /* Restored barcode size */
            max-width: 90%;
        }
        .barcode-text {
            font-size: 7px;
            margin-top: -2px;
        }
    </style>
</head>
<body>
    @php
        $allLabels = [];
        foreach($products as $product) {
            for($i = 0; $i < $product['qty']; $i++) {
                $allLabels[] = $product;
            }
        }
        $totalLabels = count($allLabels);
        $cols = 4; /* Changed to 4 columns */
        $rows = ceil($totalLabels / $cols);
    @endphp

    <div class="container">
        @for($r = 0; $r < $rows; $r++)
            <div class="row">
                @for($c = 0; $c < $cols; $c++)
                    @php $index = $r * $cols + $c; @endphp
                    <div class="label-cell">
                        @if(isset($allLabels[$index]))
                            @php $item = $allLabels[$index]; @endphp
                            <div class="label-box">
                                <div class="product-name">
                                    {{ Str::limit($item['name'], 50) }}
                                </div>
                                
                                <div class="info-row">
                                    <div class="info-line">
                                        <strong>Operador:</strong> ____________________
                                    </div>
                                    <div class="info-line">
                                        <strong>Fecha:</strong> ____/____/________
                                    </div>
                                </div>

                                <div class="barcode-container">
                                    <img src="data:image/png;base64,{{ DNS1D::getBarcodePNG($item['barcode'], 'C128') }}" alt="barcode" class="barcode-img" />
                                    <div class="barcode-text">{{ $item['barcode'] }}</div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endfor
            </div>
        @endfor
    </div>
</body>
</html>
