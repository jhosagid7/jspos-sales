<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #333333; line-height: 1.6; background-color: #f8f9fa; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); border: 1px solid #e9ecef; }
        .header { border-bottom: 2px solid #0d6efd; padding-bottom: 15px; margin-bottom: 20px; }
        .header h2 { color: #0d6efd; margin: 0; font-size: 22px; font-weight: 600; }
        .details-table { width: 100%; border-collapse: collapse; margin-top: 15px; margin-bottom: 25px; }
        .details-table th, .details-table td { padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6; }
        .details-table th { background-color: #f8f9fa; color: #495057; font-weight: 600; width: 35%; }
        .details-table td { color: #212529; }
        .otp-box { background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); border: 1px solid #c3e6cb; padding: 20px; border-radius: 6px; text-align: center; margin: 25px 0; }
        .otp-title { font-size: 14px; color: #155724; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; }
        .otp-code { font-size: 32px; font-weight: 800; color: #155724; letter-spacing: 6px; font-family: 'Courier New', Courier, monospace; margin: 0; }
        .justification { background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; border-radius: 4px; color: #664d03; margin-bottom: 25px; font-style: italic; }
        .btn-container { text-align: center; margin-top: 30px; margin-bottom: 15px; }
        .btn { display: inline-block; padding: 12px 28px; background-color: #0d6efd; color: #ffffff !important; text-decoration: none; border-radius: 5px; font-weight: 600; font-size: 15px; box-shadow: 0 4px 6px rgba(13, 110, 253, 0.15); transition: background-color 0.2s; }
        .footer { margin-top: 35px; border-top: 1px solid #e9ecef; padding-top: 15px; font-size: 12px; color: #868e96; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Solicitud de Tasa Especial de Cambio</h2>
        </div>
        
        <p>Hola, el operador <strong>{{ $requester->name }}</strong> ha solicitado la aprobación de una tasa de cambio personalizada en el sistema.</p>
        
        <div class="justification">
            <strong>Justificación obligatoria:</strong><br>
            "{{ $approval->reason }}"
        </div>

        <table class="details-table">
            <tr>
                <th>Venta (Folio)</th>
                <td>#{{ $approval->sale_id ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Tasa Especial Propuesta</th>
                <td style="font-weight: bold; color: #dc3545; font-size: 16px;">
                    {{ number_format($approval->custom_rate, 2) }} Bs.
                </td>
            </tr>
            <tr>
                <th>Solicitado Por</th>
                <td>{{ $requester->name }} ({{ $requester->email }})</td>
            </tr>
            <tr>
                <th>Fecha de Solicitud</th>
                <td>{{ now()->format('d-m-Y H:i A') }}</td>
            </tr>
        </table>

        {{-- Highlighted OTP security code block --}}
        <div class="otp-box">
            <div class="otp-title"><i class="fa fa-key"></i> Código de Autorización Rápida (OTP)</div>
            <div class="otp-code">{{ $approval->token }}</div>
            <p style="margin: 8px 0 0 0; font-size: 12px; color: #155724;">
                Dictar este código de 6 dígitos al cajero para autorizar de forma inmediata sin ingresar a la PC.
            </p>
        </div>

        <div class="btn-container">
            <a href="{{ route('consultation.approvals') }}" class="btn">
                Ir al Tablero Web de Aprobación
            </a>
        </div>

        <div class="footer">
            Este es un correo automático de control del sistema de ventas {{ config('app.name') }}. Por favor no responda a este mensaje.
        </div>
    </div>
</body>
</html>
