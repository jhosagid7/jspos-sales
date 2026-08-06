<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Etiquetas Grandes con Código QR</title>
    <style>
        @page {
            margin: 0.3cm;
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
            width: 33.33%;
            padding: 2px;
            vertical-align: top;
        }
        .label-box {
            width: 6.6cm;
            height: 5.0cm;
            border: 2px solid black;
            margin: 0 auto;
            padding: 4px;
            box-sizing: border-box;
            position: relative;
            overflow: hidden;
            background-color: #ffffff;
        }
        .product-name {
            font-size: 17px;
            font-weight: bold;
            text-align: center;
            height: 2.1cm;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1.1;
            margin-bottom: 4px;
            margin-top: 1px;
            width: 100%;
            word-break: break-word;
            text-transform: uppercase;
        }
        .bottom-section {
            width: 100%;
            position: absolute;
            bottom: 4px;
            left: 4px;
            right: 4px;
        }
        .info-column {
            float: left;
            width: 62%;
            font-size: 12px;
            line-height: 1.5;
        }
        .info-line {
            margin-bottom: 6px;
            white-space: nowrap;
        }
        .qr-column {
            float: right;
            width: 35%;
            text-align: center;
        }
        .qr-img {
            width: 1.5cm;
            height: 1.5cm;
        }
        .qr-text {
            font-size: 8px;
            font-weight: bold;
            margin-top: 1px;
        }
        .clear {
            clear: both;
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
        $cols = 3;
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
                                    {{ Str::limit($item['name'], 70) }}
                                </div>

                                <div class="bottom-section">
                                    <div class="info-column">
                                        <div class="info-line">
                                            <strong>Operador:</strong> _________
                                        </div>
                                        <div class="info-line">
                                            <strong>Fecha:</strong> <strong>____/{{ date('m/Y') }}</strong>
                                        </div>
                                    </div>

                                    <div class="qr-column">
                                        @if(!empty($item['barcode']))
                                            <img src="data:image/png;base64,{{ DNS2D::getBarcodePNG($item['barcode'], 'QRCODE') }}" alt="QR Code" class="qr-img" />
                                            <div class="qr-text">{{ $item['barcode'] }}</div>
                                        @endif
                                    </div>
                                    <div class="clear"></div>
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
