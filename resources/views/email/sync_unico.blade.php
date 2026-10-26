<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, Helvetica, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 650px; margin: 20px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,0.1); }
        .header { padding: 20px; color: #fff; font-size: 18px; font-weight: bold; }
        .header.success { background: #2e7d32; }
        .header.error { background: #c62828; }
        .content { padding: 20px; color: #333; }
        .error-box { background: #fdecea; border: 1px solid #f5c2c0; border-radius: 6px; padding: 15px; margin-bottom: 10px; }
        .error-box .label { font-size: 12px; text-transform: uppercase; color: #c62828; font-weight: bold; margin-bottom: 6px; }
        .error-box .message { font-family: 'Courier New', monospace; font-size: 13px; color: #7a1f1a; white-space: pre-wrap; word-break: break-word; }
        .summary-cards { display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
        .card { flex: 1; min-width: 140px; background: #f8f9fa; border-radius: 6px; padding: 6px; text-align: center; border: 1px solid #e0e0e0; }
        .card .value { font-size: 22px; font-weight: bold; }
        .card .label { font-size: 12px; color: #777; text-transform: uppercase; }
        .card.green .value { color: #2e7d32; }
        .card.red .value { color: #c62828; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 13px; }
        th, td { padding: 8px 10px; border: 1px solid #e0e0e0; text-align: left; }
        th { background: #f0f0f0; }
        h3 { margin-top: 25px; margin-bottom: 10px; font-size: 15px; color: #333; }
        .footer { padding: 15px 20px; font-size: 12px; color: #999; border-top: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header {{ $success ? 'success' : 'error' }}">
            {{ $success ? '✔️ Sincronización Unico/Siigo completada' : '❌ Sincronización Unico/Siigo falló' }}
        </div>

        <div class="content">
            @if (!$success)
                <div class="error-box">
                    <div class="label">Mensaje de error</div>
                    <div class="message">{{ $details['error'] ?? 'Error desconocido, revisar logs del servidor.' }}</div>
                </div>
            @endif

            @if (!empty($details['total_invoices']) || !empty($details['unico_summary']))
                <div class="summary-cards">
                    <div class="card">
                        <div class="value">{{ $details['total_invoices'] ?? 0 }}</div>
                        <div class="label">Facturas totales</div>
                    </div>
                    <div class="card green">
                        <div class="value">{{ $details['unico_summary']['insertadas'] ?? 0 }}</div>
                        <div class="label">Insertadas</div>
                    </div>
                    <div class="card red">
                        <div class="value">{{ $details['unico_summary']['rechazadas'] ?? 0 }}</div>
                        <div class="label">Rechazadas</div>
                    </div>
                    <div class="card">
                        <div class="value">{{ $details['unico_summary']['enviadas'] ?? 0 }}</div>
                        <div class="label">Enviadas</div>
                    </div>
                </div>
            @endif

            @if (!empty($details['grouped']))
                <h3>Resumen por Mall</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Mall ID</th>
                            <th>Facturas</th>
                            <th>Subtotal</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($details['grouped'] as $group)
                            <tr>
                                <td>{{ $group['mall_id'] }}</td>
                                <td>{{ $group['invoices'] }}</td>
                                <td>${{ number_format($group['purchase_subtotal'], 2, ',', '.') }}</td>
                                <td>${{ number_format($group['purchase_total'], 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            @if (!empty($details['unico_summary']['detalle_rechazadas']))
                <h3>Detalle de rechazadas ({{ count($details['unico_summary']['detalle_rechazadas']) }})</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Mall ID</th>
                            <th>Local</th>
                            <th>N° Compra</th>
                            <th>SKU</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($details['unico_summary']['detalle_rechazadas'] as $item)
                            <tr>
                                <td>{{ $item['mall_id'] }}</td>
                                <td>{{ $item['place_local_code'] }}</td>
                                <td>{{ $item['purchase_number'] }}</td>
                                <td>{{ $item['sku'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="footer">
            Notificación automática generada el {{ now()->format('Y-m-d h:i:s a') }} · Revent Tecnología
        </div>
    </div>
</body>
</html>
