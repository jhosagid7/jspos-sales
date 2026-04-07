<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        /* DomPDF Essentials */
        @page {
            margin: 1.5cm 1cm 1.5cm 1cm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            color: #2D3748;
            background-color: #FFFFFF;
            font-size: 10pt;
            line-height: 1.4;
        }

        /* Cover Page (Modern minimal) */
        #page-cover {
            margin: -1.5cm -1cm 0 -1cm; /* Fill the page bleed */
            background-color: #1A202C;
            color: #FFFFFF;
            padding-top: 200px;
            padding-bottom: 200px;
            text-align: center;
            page-break-after: always;
        }
        #page-cover img {
            max-width: 220px;
            margin-bottom: 30px;
        }
        #page-cover h1 {
            font-size: 36pt;
            margin: 15px 0;
            text-transform: uppercase;
            letter-spacing: 5px;
            font-weight: bold;
        }
        #page-cover h2 {
            font-size: 14pt;
            color: #A0AEC0;
            font-weight: normal;
            letter-spacing: 2px;
        }

        /* Section Header */
        .section-header {
            background-color: #F7FAFC;
            padding: 20px 30px;
            border-bottom: 3px solid #1A202C;
            margin-bottom: 20px;
        }
        .section-header h2 {
            font-size: 22pt;
            margin: 0;
            color: #1A202C;
            text-transform: uppercase;
            font-weight: 800;
        }
        .section-header p {
            margin: 5px 0 0;
            color: #718096;
            font-size: 10pt;
        }

        /* Product Table Grid */
        .product-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 30px;
        }
        .product-cell {
            width: 33.33%;
            padding: 10px;
            vertical-align: top;
        }
        .product-card {
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            padding: 10px;
            background-color: #FFFFFF;
            min-height: 240px; /* Compact height for high density */
        }
        .product-image-container {
            width: 100%;
            height: 110px; /* Reduced for 4 rows per page */
            margin-bottom: 10px;
            background-color: #F8FAFC;
            text-align: center;
            display: block;
            border-radius: 6px;
            overflow: hidden;
        }
        .product-image {
            max-width: 100%;
            max-height: 100%;
            display: inline-block;
        }
        .product-name {
            font-size: 10pt; /* Slightly smaller for density */
            font-weight: bold;
            color: #1A202C;
            height: 40px; /* Limit name height */
            overflow: hidden;
            margin-bottom: 5px;
        }
        .product-sku {
            font-size: 7.5pt;
            color: #718096;
            margin-bottom: 8px;
        }
        .product-price {
            font-size: 14pt;
            font-weight: 800;
            color: #2B6CB0;
        }

        /* Global Footer */
        footer {
            position: fixed;
            bottom: -0.5cm;
            left: 0;
            right: 0;
            height: 1cm;
            font-size: 8pt;
            color: #A0AEC0;
            text-align: center;
            border-top: 1px solid #EDF2F7;
            padding-top: 10px;
        }
        .pagenum:before {
             content: counter(page);
        }
    </style>
</head>
<body>

    <!-- PORTADA -->
    <div id="page-cover">
        @if($logo)
            <img src="{{ $logo }}" alt="Logo">
        @endif
        <h1 class="uppercase">{{ $config->business_name }}</h1>
        <h2>CATÁLOGO DE PRODUCTOS - {{ $date }}</h2>
    </div>

    <!-- CONTENIDO POR CATEGORÍAS -->
    @php $firstCategory = true; @endphp
    @foreach($categories as $category)
        @if($category->products->count() > 0)
            
            @php
                // First 9 products for the page with the title (3 rows of 3)
                $firstNine = $category->products->take(9);
                // The rest for subsequent pages (12 per page - 4 rows of 3)
                $remaining = $category->products->slice(9);
            @endphp

            {{-- First Page of Category (WITH TITLE - MAX 9) --}}
            <div style="{{ !$firstCategory ? 'page-break-before: always;' : '' }}">
                <div class="section-header">
                    <h2>{{ $category->name }}</h2>
                    <p>{{ $category->products->count() }} Productos disponibles</p>
                </div>

                <table class="product-table">
                    @foreach($firstNine->chunk(3) as $chunk)
                        <tr>
                            @foreach($chunk as $product)
                                <td class="product-cell">
                                    <div class="product-card">
                                        <div class="product-image-container">
                                            @if($product->image_base64)
                                                <img src="{{ $product->image_base64 }}" class="product-image" alt="Producto">
                                            @endif
                                        </div>
                                        <div class="product-name">{{ $product->name }}</div>
                                        <div class="product-sku">SKU: {{ $product->sku ?: 'No disponible' }} | {{ $product->presentation ?: 'Unidad' }}</div>
                                        <div class="product-price">
                                            @if($config->catalogue_show_prices)
                                                <div style="margin-bottom: 2px;">
                                                    <span style="font-size: 10pt; font-weight: normal">$</span>{{ number_format($product->price, 2) }}
                                                </div>
                                            @endif
                                            
                                            @if($config->catalogue_show_base_prices)
                                                <div style="font-size: 10pt; color: #4A5568; font-weight: normal; margin-top: 2px;">
                                                    <span style="font-size: 8pt; color: #A0AEC0;">REF:</span> ${{ number_format($product->cost, 2) }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            @endforeach
                            @for($i = $chunk->count(); $i < 3; $i++)
                                <td class="product-cell"></td>
                            @endfor
                        </tr>
                    @endforeach
                </table>
            </div>

            {{-- Subsequent Pages (WITHOUT TITLE - MAX 12) --}}
            @foreach($remaining->chunk(12) as $twelveChunk)
                <div style="page-break-before: always;">
                    <table class="product-table">
                        @foreach($twelveChunk->chunk(3) as $rowChunk)
                            <tr>
                                @foreach($rowChunk as $product)
                                    <td class="product-cell">
                                        <div class="product-card">
                                            <div class="product-image-container">
                                                @if($product->image_base64)
                                                    <img src="{{ $product->image_base64 }}" class="product-image" alt="Producto">
                                                @endif
                                            </div>
                                            <div class="product-name">{{ $product->name }}</div>
                                            <div class="product-sku">SKU: {{ $product->sku ?: 'No disponible' }} | {{ $product->presentation ?: 'Unidad' }}</div>
                                            <div class="product-price">
                                                @if($config->catalogue_show_prices)
                                                    <div style="margin-bottom: 2px;">
                                                        <span style="font-size: 10pt; font-weight: normal">$</span>{{ number_format($product->price, 2) }}
                                                    </div>
                                                @endif
                                                
                                                @if($config->catalogue_show_base_prices)
                                                    <div style="font-size: 10pt; color: #4A5568; font-weight: normal; margin-top: 2px;">
                                                        <span style="font-size: 8pt; color: #A0AEC0;">REF:</span> ${{ number_format($product->cost, 2) }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                @endforeach
                                @for($i = $rowChunk->count(); $i < 3; $i++)
                                    <td class="product-cell"></td>
                                @endfor
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endforeach

            @php $firstCategory = false; @endphp
        @endif
    @endforeach

    <footer>
        <table style="width: 100%;">
            <tr>
                <td style="width: 80%; text-align: left;">{{ $config->business_name }} | {{ $config->phone }} | {{ $config->address }} | {{ $config->website }}</td>
                <td style="width: 20%; text-align: right;">Página <span class="pagenum"></span></td>
            </tr>
        </table>
    </footer>

</body>
</html>
