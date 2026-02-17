<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estado de Cuenta - {{ $customer->name }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .info-section { margin-bottom: 20px; }
        .info-table { width: 100%; margin-bottom: 10px; }
        .info-table td { padding: 4px; }
        .invoices-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .invoices-table th, .invoices-table td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        .invoices-table th { background-color: #f2f2f2; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .overdue { background-color: #f8d7da; color: #721c24; font-weight: bold; }
        .total-row { background-color: #f8f9fa; font-weight: bold; font-size: 14px; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #666; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>
<body>

    <div class="header">
        <h2>ESTADO DE CUENTA</h2>
        <p style="margin: 5px 0;">Facturas Pendientes de Pago</p>
    </div>

    <div class="info-section">
        <table class="info-table">
            <tr>
                <td width="50%">
                    <strong>Cliente:</strong> {{ $customer->name }}<br>
                    <strong>CC/NIT:</strong> {{ $customer->taxpayer_id ?? 'N/A' }}<br>
                    <strong>Teléfono:</strong> {{ $customer->phone ?? 'N/A' }}
                </td>
                <td width="50%">
                    @if($customer->seller)
                        <strong>Vendedor Asignado:</strong> {{ $customer->seller->name }}<br>
                    @endif
                    <strong>Días de Crédito:</strong> {{ $customer->credit_days ?? 0 }} días<br>
                    <strong>Límite de Crédito:</strong> ${{ number_format($customer->credit_limit ?? 0, 2) }}
                </td>
            </tr>
        </table>
    </div>

    @if(count($invoices) > 0)
        <table class="invoices-table">
            <thead>
                <tr>
                    <th>#Factura</th>
                    <th class="text-center">Emisión</th>
                    <th class="text-center">Vencimiento</th>
                    <th class="text-right">Monto Original</th>
                    <th class="text-right">Abonos</th>
                    <th class="text-right">Saldo Pendiente</th>
                    <th class="text-center">Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoices as $inv)
                    <tr class="{{ $inv['is_overdue'] ? 'overdue' : '' }}">
                        <td><strong>{{ $inv['invoice_number'] }}</strong></td>
                        <td class="text-center">{{ $inv['created_at'] }}</td>
                        <td class="text-center">{{ $inv['due_date'] }}</td>
                        <td class="text-right">${{ number_format($inv['total'], 2) }}</td>
                        <td class="text-right">${{ number_format($inv['paid'], 2) }}</td>
                        <td class="text-right"><strong>${{ number_format($inv['pending'], 2) }}</strong></td>
                        <td class="text-center">
                            @if($inv['is_overdue'])
                                VENCIDA
                            @else
                                PENDIENTE
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="5" class="text-right">TOTAL DEUDA:</td>
                    <td class="text-right" style="color: #dc3545;">${{ number_format($totalDebt, 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    @else
        <p class="text-center" style="margin: 40px 0; color: #666;">No hay facturas pendientes de pago.</p>
    @endif

    <div class="footer">
        <p>Documento generado el {{ $generatedAt }}</p>
        <p>Este documento es un resumen de las facturas pendientes de pago del cliente.</p>
    </div>

</body>
</html>
