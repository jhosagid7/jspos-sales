<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Precios</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 4px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .header { text-align: center; margin-bottom: 20px; }
        .details { margin-bottom: 15px; }
        .text-right { text-align: right; }
        @page { margin: 100px 25px 60px 25px; }
        header { position: fixed; top: -60px; left: 0px; right: 0px; height: 50px; text-align: center; }
        footer { position: fixed; bottom: -40px; left: 0px; right: 0px; height: 30px; text-align: center; font-size: 10px; color: #555; }
        body { font-family: sans-serif; font-size: 10px; }
    </style>
</head>
<body>
    <footer>
        {{ $footerCode ?? '' }}
    </footer>
    <header>
        <h2 style="margin:0;">Lista de Precios</h2>
        <p>Fecha: {{ $date }}</p>
    </header>

    <div style="text-align: center; margin-bottom: 10px;">
        <strong>Vendedor:</strong> {{ $headerData['seller_name'] }}
        @if(!empty($headerData['seller_phone']))
         - <strong>Teléfono:</strong> {{ $headerData['seller_phone'] }}
        @endif
    </div>

    {{-- Conditions Block - Full Width --}}
    <div style="width: 100%; margin-bottom: 20px; font-size: 10px; line-height: 1.4; color: #000000;">
        <strong>LISTA DE PRECIOS PAGO EN BOLIVARES BCV.</strong><br>
        <br>
        <strong>Nota Informativa de Pagos:</strong><br>
        
        @if($headerData['usd_discount'] > 0)
        <strong>Descuento Base:</strong> Todas nuestras tarifas incluyen un <strong>{{ number_format($headerData['usd_discount'], 0) }}%</strong> de descuento al ser canceladas en divisas.<br>
        @endif

        @if(count($headerData['discount_rules']) > 0)
            @foreach($headerData['discount_rules'] as $rule)
                @if($rule->days_from == 0)
                     <strong>{{ number_format($rule->discount_percentage, 0) }}%</strong> de descuento pronto pago: Si realiza su pago dentro de los primeros <strong>{{ $rule->days_to }}</strong> días continuos a la entrega.<br>
                @else
                     <strong>{{ number_format($rule->discount_percentage, 0) }}%</strong> de descuento pronto pago: Si realiza su pago entre el día <strong>{{ $rule->days_from }}</strong> y el día <strong>{{ $rule->days_to ?? '+' }}</strong> tras la entrega.<br>
                @endif
            @endforeach
        @endif

        <br>
        <strong>Vencimiento:</strong> su factura vence <strong>{{ $headerData['credit_days'] }}</strong> dias despues de la entrega del producto.<br>
        <br>
        <strong>Recargo por Mora:</strong> Agradecemos cumplir con sus compromisos a tiempo.<br>
        Los pagos realizados después de los <strong>{{ $headerData['credit_days'] }}</strong> días de la fecha de entrega generarán un recargo por mora.<br>
        <br>
        <strong style="text-decoration: underline;">LISTA DE PRECIOS SUJETA A CAMBIO SIN PREVIO AVISO.</strong>
    </div>

    <table>
        <thead>
            <tr>
                @foreach($columns as $col)
                    <th>{{ $columnLabels[$col] ?? $col }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($groupedData as $category => $items)
            <tr style="background-color: #e9ecef;">
                <td colspan="{{ count($columns) }}" style="font-weight: bold; text-align: left; background-color: #ddd;">
                    {{ $category }}
                </td>
            </tr>
            @foreach($items as $row)
            <tr style="{{ isset($row['is_out_of_stock']) && $row['is_out_of_stock'] ? 'color: red;' : '' }}">
                @foreach($columns as $col)
                    <td class="{{ in_array($col, ['base_price', 'freight', 'commission', 'exchange_diff', 'net_price', 'tax_amount', 'final_price']) ? 'text-right' : '' }}">
                        @if(in_array($col, ['base_price', 'freight', 'commission', 'exchange_diff', 'net_price', 'tax_amount', 'final_price']))
                            @if(isset($row['is_out_of_stock']) && $row['is_out_of_stock'])
                                <strong style="color: red;">AGOTADO</strong>
                            @else
                                ${{ number_format($row[$col] ?? 0, 2) }}
                            @endif
                        @elseif($col == 'stock')
                            {{-- Check if it's the AGOTADO string or a number --}}
                            @if($row[$col] === 'AGOTADO')
                                <strong>AGOTADO</strong>
                            @else
                                {{ number_format($row[$col] ?? 0, 2) }}
                            @endif
                        @else
                            {{ $row[$col] ?? '' }}
                        @endif
                    </td>
                @endforeach
            </tr>
            @endforeach
            @endforeach
        </tbody>
    </table>
</body>
</html>
