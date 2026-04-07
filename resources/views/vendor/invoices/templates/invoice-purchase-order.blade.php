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

            .mt-5 {
                margin-top: 3rem !important;
            }
            .mt-1 {
                margin-top: 1rem !important;
            }

            .pr-0,
            .px-0 {
                padding-right: 0 !important;
            }

            .pl-0,
            .px-0 {
                padding-left: 0 !important;
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
            * {
                font-family: "DejaVu Sans";
            }
            body, h1, h2, h3, h4, h5, h6, table, th, tr, td, p, div {
                line-height: 1.1;
            }
            .party-header {
                font-size: 1.5rem;
                font-weight: 400;
            }
            .total-amount {
                font-size: 12px;
                font-weight: 700;
            }
            .border-0 {
                border: none !important;
            }
            .cool-gray {
                color: #6B7280;
            }

            .clase_table {
                border-collapse: separate;
                border: 1px solid black;
                border-color:#6B7280;
                border-radius: 10px;
                padding: 5px;
                margin: 0;
            }

            header {
                padding: 10px 0;
                margin-bottom: 30px
            }

            .invoice-title {
                color: #0380b2;
                font-size: 2em;
                line-height: 1em;
                font-weight: normal;
                margin: 20px 0
            }

            table.item-table {
                width: 100%;
                border-collapse: collapse;
                border-spacing: 0;
                margin-bottom: 20px;
                font-size: 12px
            }

            table.item-table th {
                padding: 10px 15px;
                border-bottom: 1px solid #606060;
                white-space: nowrap;
                text-align: left
            }

            table.item-table td {
                padding: 10px 15px
            }

            footer {
                color: #878686;
                width: 100%;
                border-top: 2px solid #878686;
                padding: 8px 0
            }

            .cool-remission {
                color: #0380b2;
            }

            .box-disclaimer {
                border: gray 1px solid;
                font-size: 12px;
                text-transform: uppercase !important;
                font-weight: bold;
                background: #f8f9fa;
                padding: 10px;
                margin-top: 20px;
                border-radius: 10px;
            }

            .empty-col {
                width: 100px;
                border: 1px solid #ced4da;
                background-color: #fff;
            }
        </style>
    </head>

    <body>
        {{-- Header --}}
        <table class="table mt-1">
            <tbody>
                <tr>
                    <td class="pl-0 border-0" width="25%" style="vertical-align: middle;">
                        @if($invoice->logo)
                            <img src="{{ $invoice->getLogo() }}" alt="logo" height="80">
                        @endif
                    </td>
                    <td class="border-0 text-center" width="50%" style="vertical-align: middle;">
                        <h4 class="text-uppercase" style="color: #0380b2; font-weight: bold; font-size: 20px; margin: 0;">
                            {{ $invoice->seller->name }}
                        </h4>
                        <div style="font-size: 10px;">
                            {{ $invoice->seller->address }}<br>
                            TEL: {{ $invoice->seller->phone }}<br>
                            {{ $invoice->seller->custom_fields['CC/NIT'] ?? '' }}
                        </div>
                    </td>
                    <td class="border-0 text-right" width="25%" style="vertical-align: middle;">
                        <h4 class="text-uppercase" style="color: #0380b2; font-size: 20px; font-weight: bold; margin: 0;">
                            OC-{{ $invoice->getSerialNumber() }}
                        </h4>
                        <span style="font-size: 10px; font-weight: bold;">ORDEN DE COMPRA</span>
                    </td>
                </tr>
            </tbody>
        </table>

        {{-- Supplier Info Box --}}
        <div style="border: 1px solid #6B7280; border-radius: 10px; padding: 10px; margin-bottom: 20px;">
            <table class="table border-0" style="margin: 0;">
                <tbody>
                    <tr>
                        <td class="border-0 pl-0" width="60%" style="vertical-align: top;">
                            <strong style="color: #0380b2; text-transform: uppercase;">PROVEEDOR:</strong><br>
                            @if($invoice->buyer->name)
                                <strong class="text-uppercase" style="font-size: 14px;">{{ $invoice->buyer->name }}</strong><br>
                            @endif

                            @if($invoice->buyer->custom_fields['CC/NIT'] ?? false)
                                RIF: {{ $invoice->buyer->custom_fields['CC/NIT'] }}<br>
                            @endif

                            @if($invoice->buyer->address)
                                Dirección: {{ $invoice->buyer->address }}<br>
                            @endif

                            @if($invoice->buyer->phone)
                                Teléfono: {{ $invoice->buyer->phone }}<br>
                            @endif
                        </td>

                        <td class="border-0 text-right pr-0" width="40%" style="vertical-align: top;">
                            Fecha de Emisión: <strong>{{ $invoice->getDate() }}</strong><br>
                            Operador: <strong>{{ $invoice->seller->custom_fields['operador'] ?? 'N/A' }}</strong><br>
                            
                            <div style="margin-top: 10px;">
                                TOTAL ESTIMADO: <strong class="cool-remission" style="font-size: 16px;">{{ $invoice->formatCurrency($invoice->total_amount) }}</strong>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Table --}}
        <table class="table table-items">
            <thead>
                <tr>
                    <th scope="col" class="pl-0 border-0" style="width: 30px;">#</th>
                    <th scope="col" class="pl-0 border-0">REFERENCIA</th>
                    <th scope="col" class="pl-0 border-0">DESCRIPCIÓN</th>
                    <th scope="col" class="text-center border-0">CANT</th>
                    <th scope="col" class="text-right border-0">COSTO UNT.</th>
                    <th scope="col" class="text-center border-0" style="width: 100px; color: #0380b2;">NUEVO COSTO</th>
                    <th scope="col" class="pr-0 text-right border-0">SUBTOTAL</th>
                </tr>
            </thead>
            <tbody>
                @php $count = 0; @endphp
                @foreach($invoice->items as $item)
                @php $count++; @endphp
                <tr>
                    <td class="pl-0"><p class="cool-gray">{{ $count }}</p></td>
                    <td class="pl-0">{{ $item->reference }}</td>
                    <td class="pl-0">{{ $item->title }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">{{ $invoice->formatCurrency($item->price_per_unit) }}</td>
                    <td class="text-center">
                        <div style="border: 1px solid #ced4da; height: 15px; margin: 2px 10px;"></div>
                    </td>
                    <td class="pr-0 text-right">
                        {{ $invoice->formatCurrency($item->sub_total_price) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" class="border-0"></td>
                    <td class="pl-0 text-right" style="font-weight: bold;">TOTAL:</td>
                    <td class="pr-0 text-right total-amount" style="color: #0380b2;">
                        {{ $invoice->formatCurrency($invoice->total_amount) }}
                    </td>
                </tr>
            </tfoot>
        </table>

        @if($invoice->notes)
            <div class="box-disclaimer">
                <strong>NOTAS:</strong><br>
                {!! $invoice->notes !!}
            </div>
        @endif

        {{-- Footer area for cloning --}}
        <table width="100%" style="border: 1px solid #6B7280; border-top: 1px solid #6B7280; margin-top: 10px; background: #ADD8E6; border-radius: 15px; color: #000; border-collapse: collapse;">
            <tr>
                <td width="85%" style="padding: 10px; font-size: 14px; text-transform: uppercase; font-weight: bold;">
                    ESTE DOCUMENTO ES UNA ORDEN DE COMPRA. PUEDE SER CLONADA ESCANEANDO EL CÓDIGO QR.
                </td>
                <td width="15%" style="text-align: center; background: #fff; border-left: 1px solid #6B7280; vertical-align: middle; line-height: 0; padding: 5px;">
                    @if($invoice->seller->custom_fields['cloning_qr'] ?? false)
                        {!! $invoice->seller->custom_fields['cloning_qr'] !!}
                        <br><small style="font-size: 7px; margin-top: 2px;">CLONAR</small>
                    @endif
                </td>
            </tr>
        </table>

        <div style="margin-top: 80px;">
            <table width="100%">
                <tr>
                    <td width="33%" style="text-align: center;">
                        <div style="border-top: 1px solid #333; width: 80%; margin: 0 auto;"></div>
                        ELABORADO POR
                    </td>
                    <td width="33%" style="text-align: center;">
                        <div style="border-top: 1px solid #333; width: 80%; margin: 0 auto;"></div>
                        AUTORIZADO POR
                    </td>
                    <td width="33%" style="text-align: center;">
                        <div style="border-top: 1px solid #333; width: 80%; margin: 0 auto;"></div>
                        RECIBIDO (PROVEEDOR)
                    </td>
                </tr>
            </table>
        </div>

    </body>
</html>
