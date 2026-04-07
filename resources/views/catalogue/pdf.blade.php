<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        /* Modern Reset for DomPDF */
        @page {
            margin: 0px;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            color: #2D3748;
            background-color: #FFFFFF;
            font-size: 11pt;
            line-height: 1.5;
        }

        /* Helper Classes */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }

        /* Cover Page Styling (Premium Magazine Look) */
        .page-cover {
            background-color: #1A202C; /* Deep dark background */
            color: #FFFFFF;
            height: 100%;
            width: 100%;
            display: block;
            text-align: center;
            padding-top: 250px;
            page-break-after: always;
        }
        .page-cover img {
            max-width: 180px;
            margin-bottom: 40px;
            border-radius: 12px;
        }
        .page-cover h1 {
            font-size: 42pt;
            letter-spacing: 4px;
            margin: 10px 0;
            font-weight: 300;
        }
        .page-cover h2 {
            font-size: 16pt;
            letter-spacing: 2px;
            color: #A0AEC0;
            margin-top: 5px;
            font-weight: 400;
        }
        .cover-footer {
            position: absolute;
            bottom: 60px;
            width: 100%;
            font-size: 10pt;
            color: #718096;
        }

        /* Section Header Styling */
        .section-header {
            background-color: #EDF2F7;
            padding: 35px 50px;
            margin-top: 0;
            page-break-before: always;
            border-bottom: 4px solid #1A202C;
        }
        .section-header h2 {
            font-size: 24pt;
            margin: 0;
            color: #1A202C;
            letter-spacing: 1px;
            font-weight: 700;
        }
        .section-header p {
            margin: 5px 0 0;
            color: #4A5568;
            font-size: 10pt;
        }

        /* Product Grid (DomPDF reliable table-based layout) */
        .product-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            padding: 20px 30px;
        }
        .product-cell {
            width: 33.33%;
            padding: 15px;
            text-align: center;
            vertical-align: top;
            border-bottom: 1px solid #F7FAFC;
        }
        .product-card {
            background-color: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            padding: 15px;
            height: 380px; /* Fixed height for clean grid rows */
        }
        .product-image-container {
            height: 180px;
            width: 100%;
            display: block;
            margin-bottom: 15px;
            overflow: hidden;
            background-color: #F8FAFC;
            border-radius: 8px;
        }
        .product-image {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
        }
        .product-info {
            text-align: left;
        }
        .product-name {
            font-size: 13pt;
            font-weight: 700;
            color: #1A202C;
            height: 48px; /* Two lines height limit */
            overflow: hidden;
            margin-bottom: 4px;
        }
        .product-meta {
            font-size: 9pt;
            color: #718096;
            margin-bottom: 12px;
        }
        .product-price {
            font-size: 18pt;
            font-weight: 800;
            color: #3182CE; /* Vibrant Blue Accent */
        }
        .price-currency {
            font-size: 11pt;
            font-weight: 400;
            margin-right: 2px;
        }

        /* Footer */
        .footer {
            position: fixed;
            bottom: 15px;
            left: 0;
            right: 0;
            height: 30px;
            border-top: 1px solid #E2E8F0;
            padding: 10px 50px;
            color: #A0AEC0;
            font-size: 8pt;
        }
        .pagenum:before {
            content: counter(page);
        }
    </style>
</head>
<body>

    <!-- PORTADA PREMIUM -->
    <div class="page-cover">
        <img src="{{ $config->logo ? public_path('storage/'.$config->logo) : public_path('assets/images/logo/logo-icon.png') }}" alt="Business Logo">
        <h1 class="uppercase">{{ $config->business_name }}</h1>
        <h2>CATÁLOGO DE PRODUCTOS - {{ $date }}</h2>
        
        <div class="cover-footer">
            {{ $config->website }}
        </div>
    </div>

    <!-- CONTENIDO POR CATEGORÍAS -->
    @foreach($categories as $category)
        @if($category->products->count() > 0)
            <!-- SEPARADOR DE SECCIÓN -->
            <div class="section-header">
                <h2 class="uppercase">{{ $category->name }}</h2>
                <p>{{ $category->products->count() }} Productos disponibles</p>
            </div>

            <!-- CUADRÍCULA DE PRODUCTOS -->
            <table class="product-table">
                @foreach($category->products->chunk(3) as $chunk)
                    <tr>
                        @foreach($chunk as $product)
                            <td class="product-cell">
                                <div class="product-card">
                                    <div class="product-image-container">
                                        @php
                                            $imagePath = public_path('noimage.jpg');
                                            if ($product->images->count() > 0) {
                                                $fileName = $product->images->last()->file;
                                                if (file_exists(public_path('storage/products/' . $fileName))) {
                                                    $imagePath = public_path('storage/products/' . $fileName);
                                                }
                                            }
                                        @endphp
                                        <img src="{{ $imagePath }}" class="product-image" alt="Producto">
                                    </div>
                                    <div class="product-info">
                                        <div class="product-name">{{ $product->name }}</div>
                                        <div class="product-meta">SKU: {{ $product->sku ?: 'N/A' }} | {{ $product->presentation ?: 'Unidad' }}</div>
                                        <div class="product-price">
                                            <span class="price-currency">$</span>{{ number_format($product->price, 2) }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                        @endforeach
                        {{-- Células vacías para completar la fila si es necesario --}}
                        @for($i = $chunk->count(); $i < 3; $i++)
                            <td class="product-cell"></td>
                        @endfor
                    </tr>
                @endforeach
            </table>
        @endif
    @endforeach

    <!-- PIE DE PÁGINA (Se repite en todas las hojas excepto portada) -->
    <div class="footer">
        <table style="width: 100%;">
            <tr>
                <td style="width: 70%;">{{ $config->business_name }} | {{ $config->phone }} | {{ $config->address }}</td>
                <td style="width: 30%;" class="text-right">Página <span class="pagenum"></span></td>
            </tr>
        </table>
    </div>

</body>
</html>
