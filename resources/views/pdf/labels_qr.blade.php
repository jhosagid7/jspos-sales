<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Etiquetas Grandes con Código QR</title>
    <style>
        @page {
            margin: 0.1cm;
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
            table-layout: fixed;
        }
        .row {
            display: table-row;
        }
        .label-cell {
            display: table-cell;
            width: 33.33%;
            padding: 0.5px;
            vertical-align: top;
        }
        .label-box {
            width: 6.6cm;
            height: 4.05cm;
            border: 3px solid #000000;
            margin: 0 auto;
            padding: 2px 3px;
            box-sizing: border-box;
            position: relative;
            overflow: hidden;
            background-color: #ffffff;
        }
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 125px;
            height: 125px;
            margin-top: -62px;
            margin-left: -62px;
            opacity: 0.15;
            z-index: 1;
            object-fit: contain;
        }
        .product-name {
            font-size: 17px;
            font-weight: 900;
            text-align: center;
            height: 1.7cm;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1.1;
            margin-bottom: 2px;
            margin-top: 1px;
            width: 100%;
            word-break: break-word;
            text-transform: uppercase;
            position: relative;
            z-index: 2;
        }
        .bottom-section {
            width: 100%;
            position: absolute;
            bottom: 2px;
            left: 3px;
            right: 3px;
            z-index: 2;
        }
        .info-column {
            float: left;
            width: 62%;
            font-size: 11px;
            line-height: 1.4;
            font-weight: bold;
        }
        .info-line {
            margin-bottom: 4px;
            white-space: nowrap;
        }
        .qr-column {
            float: right;
            width: 35%;
            text-align: center;
        }
        .qr-img {
            width: 1.3cm;
            height: 1.3cm;
            background-color: #ffffff;
            padding: 1px;
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
                                @if(!empty($logoBase64))
                                    <img src="{{ $logoBase64 }}" class="watermark" alt="Watermark Logo" />
                                @endif

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
