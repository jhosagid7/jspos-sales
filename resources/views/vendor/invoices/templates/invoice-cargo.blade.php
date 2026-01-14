<!DOCTYPE html>
<html lang="en">
    <head>
        <title>{{ $invoice->name }}</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

        <style type="text/css" media="screen">
            html {
                font-family: sans-serif;
                line-height: 1.15;
                margin: 0;
            }

            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
                font-weight: 400;
                line-height: 1.5;
                color: #212529;
                text-align: left;
                background-color: #fff;
                font-size: 10px;
                margin: 36pt;
            }

            h4 {
                margin-top: 0;
                margin-bottom: 0.5rem;
            }

            p {
                margin-top: 0;
                margin-bottom: 1rem;
            }

            strong {
                font-weight: bold;
            }

            img {
                vertical-align: middle;
                border-style: none;
            }

            table {
                border-collapse: collapse;
            }

            th {
                text-align: inherit;
            }

            h4, .h4 {
                margin-bottom: 0.5rem;
                font-weight: 500;
                line-height: 1.2;
            }

            h4, .h4 {
                font-size: 1.5rem;
            }

            .table {
                width: 100%;
                margin-bottom: 1rem;
                color: #212529;
            }

            .table th,
            .table td {
                padding: 0.10rem;
                vertical-align: top;
            }

            .table.table-items td {
                border-top: 1px solid #dee2e6;
            }

            .table thead th {
                vertical-align: bottom;
                border-bottom: 2px solid #dee2e6;
            }

            .text-right {
                text-align: right !important;
            }

            .text-center {
                text-align: center !important;
            }

            .text-uppercase {
                text-transform: uppercase !important;
            }
            
            .party-header {
                font-size: 1.5rem;
                font-weight: 400;
            }
            
            .border-0 {
                border: none !important;
            }
            
            .cool-gray {
                color: #6B7280;
            }

            .clase_table {
                border-collapse: separate;
                border-spacing: 7;
                border: 1px solid #6B7280;
                border-radius: 15px;
                padding: 10px;
                margin: 0;
            }

            .invoice-title{
                color:#0380b2;
                font-size:2em;
                line-height:1em;
                font-weight:normal;
                margin:20px 0
            }
            
            .remission-title{
                color:#0380b2;
                font-size:1.5em;
                line-height:1.4em;
                font-weight:normal;
                margin:0;
            }
            
            .title-data{
                font-size:14px;
                text-transform: uppercase !important;
            }
            
            .box-disclaimer{
                border: gray 1px solid;
                font-size:14px;
                text-transform: uppercase !important;
                font-weight: bold;
                background: #ADD8E6;
                padding: 10px;
                margin: 10px 0;
            }
        </style>
    </head>

    <body>
        {{-- Header --}}
        <table style="margin-bottom:0px;" class="table mt-1">
            <tbody>
                <tr>
                    <td class="pl-0 border-0" width="15%">
                       @if($invoice->logo)
                            <img src="{{ $invoice->getLogo() }}" alt="logo" height="50">
                        @endif
                    </td>
                    <td class="pl-0 border-0" width="70%">
                        <h4 class="text-center text-uppercase invoice-title">
                            <strong>{{ $invoice->name }}</strong>
                        </h4>
                        <h5 class="text-center text-uppercase" style="color: #0380b2; margin: 0;">
                            AJUSTE DE INVENTARIO
                        </h5>
                    </td>
                    <td class="pl-0 border-0 text-right" width="15%">
                        <p>
                            <strong class="remission-title">{{ $invoice->getSerialNumber() }}</strong>
                            <br><b>Folio</b>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>

        {{-- Info --}}
        <table class="table clase_table">
            <tbody>
                <tr>
                    <td class="px-0" width="50%">
                        <strong class="title-data">EMPRESA</strong><br>
                        {{ $invoice->seller->name }}<br>
                        @if($invoice->seller->address) {{ $invoice->seller->address }}<br> @endif
                        @if($invoice->seller->phone) Tel: {{ $invoice->seller->phone }}<br> @endif
                    </td>
                    <td class="px-0" width="50%">
                        <strong class="title-data">DETALLES DEL CARGO</strong><br>
                        Fecha: <strong>{{ $invoice->getDate() }}</strong><br>
                        @foreach($invoice->buyer->custom_fields as $key => $value)
                            {{ ucfirst($key) }}: <b>{{ $value }}</b><br>
                        @endforeach
                    </td>
                </tr>
            </tbody>
        </table>

        {{-- Items --}}
        <table class="table table-items mt-3">
            <thead>
                <tr>
                    <th scope="col" class="border-0">#</th>
                    <th scope="col" class="border-0">SKU</th>
                    <th scope="col" class="border-0">Producto</th>
                    <th scope="col" class="text-center border-0">Cantidad</th>
                    <th scope="col" class="text-right border-0">Costo</th>
                    <th scope="col" class="text-right border-0">Total</th>
                </tr>
            </thead>
            <tbody>
                @php $count = 0; $totalCost = 0; @endphp
                @foreach($invoice->items as $item)
                @php 
                    $count++; 
                    $totalCost += $item->price_per_unit * $item->quantity;
                @endphp
                <tr>
                    <td class="pl-0">{{ $count }}</td>
                    <td class="pl-0">{{ $item->reference }}</td>
                    <td class="pl-0">{{ $item->title }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">{{ $invoice->formatCurrency($item->price_per_unit) }}</td>
                    <td class="text-right">{{ $invoice->formatCurrency($item->price_per_unit * $item->quantity) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" class="text-right border-0"><strong>Total Costo:</strong></td>
                    <td class="text-right border-0"><strong>{{ $invoice->formatCurrency($totalCost) }}</strong></td>
                </tr>
            </tfoot>
        </table>

        @if($invoice->notes)
            <div class="box-disclaimer">
                <b>Comentarios:</b> {!! $invoice->notes !!}
            </div>
        @endif

        <table class="table mt-5">
            <thead>
                <th>
                    <td width="40%" class="border-0 text-center"><hr style="border: 1px solid #000;"></td>
                    <td width="20%" class="border-0"></td>
                    <td width="40%" class="border-0 text-center"><hr style="border: 1px solid #000;"></td>
                </th>
            </thead>
            <tbody>
                <tr>
                    <td class="text-center border-0">AUTORIZADO POR</td>
                    <td class="border-0"></td>
                    <td class="text-center border-0">RESPONSABLE</td>
                </tr>
            </tbody>
        </table>
        
        <script type="text/php">
            if (isset($pdf) && $PAGE_COUNT > 1) {
                $text = "Página {PAGE_NUM} / {PAGE_COUNT}";
                $size = 10;
                $font = $fontMetrics->getFont("Verdana");
                $width = $fontMetrics->get_text_width($text, $font, $size) / 2;
                $x = ($pdf->get_width() - $width);
                $y = $pdf->get_height() - 35;
                $pdf->page_text($x, $y, $text, $font, $size);
            }
        </script>
    </body>
</html>
