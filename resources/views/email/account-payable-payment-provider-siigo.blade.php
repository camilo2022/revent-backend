<!-- resources/views/email/comprobante-pago-siigo.blade.php -->
<!DOCTYPE html>
<html lang="es" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>Comprobante de pago - REVENT CALZADO S.A.S.</title>
<!--[if mso]>
<noscript>
<xml>
<o:OfficeDocumentSettings>
<o:PixelsPerInch>96</o:PixelsPerInch>
</o:OfficeDocumentSettings>
</xml>
</noscript>
<![endif]-->
</head>
<body style="margin:0; padding:0; background-color:#f3f4f6; -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%;">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f3f4f6;">
    <tr>
        <td align="center" style="padding:24px 12px;">

            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:640px; width:100%;">

                <!-- Logo -->
                <tr>
                    <td align="center" style="padding:4px 4px 20px 4px;">
                        <img src="https://revent.com.co/cdn/shop/files/Logo_Revent-Negro.png?v=1744326854&width=250" alt="REVENT CALZADO S.A.S." width="150" style="display:block; width:150px; max-width:150px; height:auto;">
                    </td>
                </tr>

                <!-- Banner de título -->
                <tr>
                    <td style="background-color:#111827; border-radius:12px; padding:26px 24px; font-family:Segoe UI, Arial, sans-serif;">
                        <p style="margin:0 0 4px 0; font-size:11px; font-weight:bold; color:#9ca3af; letter-spacing:0.5px; text-transform:uppercase;">
                            REVENT CALZADO S.A.S.
                        </p>
                        <p style="margin:0; font-size:19px; font-weight:bold; color:#ffffff; line-height:1.3;">
                            Comprobante de pago {{ $voucher['DocName'] ?? '' }}
                        </p>
                    </td>
                </tr>

                <tr><td style="height:16px; line-height:16px; font-size:0;">&nbsp;</td></tr>

                <!-- Cuerpo de la carta -->
                <tr>
                    <td style="background-color:#ffffff; border:1px solid #eef0f2; border-radius:12px; padding:24px; font-family:Segoe UI, Arial, sans-serif;">

                        <p style="margin:0 0 16px 0; font-size:14px; color:#374151; line-height:1.6;">
                            Estimado
                            <strong>
                                {{ data_get($provider, 'BasicData.FullName', 'señores') }}
                                @if(data_get($provider, 'BasicData.CompanyName'))
                                    ({{ data_get($provider, 'BasicData.CompanyName') }})
                                @endif
                            </strong>,
                        </p>

                        <p style="margin:0 0 16px 0; font-size:14px; color:#374151; line-height:1.6;">
                            Reciba un cordial saludo de parte de <strong>REVENT CALZADO S.A.S.</strong>
                        </p>

                        <p style="margin:0 0 16px 0; font-size:14px; color:#374151; line-height:1.6;">
                            A continuación, le remitimos detalle de soporte de pago
                            @if(!empty($voucher_id))
                                <a href="https://siigonube.siigo.com/#/paymentsv2/1016/view/{{ $voucher_id }}" target="_blank" style="color:#1d4ed8; font-weight:bold; text-decoration:none;">
                                    {{ $voucher['DocName'] ?? '' }}
                                </a>
                            @else
                                <strong>{{ $voucher['DocName'] ?? '' }}</strong>
                            @endif según anexos.
                        </p>

                        @if(!empty($url))
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto 16px;">
                            <tr>
                                <td align="center" style="padding:4px;">
                                    <img src="{{ $url }}" alt="Comprobante de pago {{ $voucher['DocName'] ?? '' }}" width="280"
                                        style="display:block; width:280px; max-width:100%; height:auto; object-fit:contain; border:1px solid #eef0f2; border-radius:8px;"
                                    >
                                </td>
                            </tr>
                        </table>
                        @endif

                        <p style="margin:0; font-size:14px; color:#374151; line-height:1.6;">
                            Agradeciendo confirmar su recepción y verificación a la mayor brevedad posible y
                            quedando atentos a su comentario y/u observación.
                        </p>

                    </td>
                </tr>

                <tr><td style="height:16px; line-height:16px; font-size:0;">&nbsp;</td></tr>

                <!-- Detalle de recibos aplicados al pago -->
                <tr>
                    <td style="background-color:#ffffff; border:1px solid #eef0f2; border-radius:12px; padding:16px 20px 8px 20px; font-family:Segoe UI, Arial, sans-serif;">

                        @if(!empty($observaciones))
                        <p style="margin:0 0 14px 0; font-size:13px; font-weight:bold; color:#111827; text-align:center; letter-spacing:0.3px;">
                            *** {{ strtoupper($observaciones) }} ***
                        </p>
                        @endif

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">

                            @foreach ($recibos as $recibo)
                            @php
                                // OJO: aqui asumo que la "fecha recibo" es $recibo['DueDate'].
                                // Si el campo real es otro, solo cambia esta linea.
                                $fechareciboRaw = $recibo['DueDate'] ?? null;
                                $fechareciboCorta = $fechareciboRaw ? \Carbon\Carbon::parse($fechareciboRaw)->format('d/m/y') : '-';

                                $ordenCompra = $recibo['PurchaseEntry']['docName'] ?? null;
                                $ordenCompra = !empty($ordenCompra) ? $ordenCompra : '-';

                                $pares = $recibo['PurchaseEntryDetail']['Quantity'] ?? 0;
                                $bodega = $recibo['PurchaseEntryDetail']['WarehouseCodes'] ?? '';
                                $reciboDoc = $recibo['DocName'] ?? '-';
                            @endphp
                            <tr>
                                <td style="padding:11px 0; border-bottom:1px solid #f1f3f5;">
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                        <tr>
                                            <td valign="middle" style="font-family:Segoe UI, Arial, sans-serif;">
                                                <span style="font-size:13px; font-weight:bold; color:#111827;">
                                                    {{ $recibo['DueName'] ?? '' }} - {{ $bodega }} - ({{ $reciboDoc }})
                                                </span>
                                                <div style="font-size:11px; color:#9ca3af; margin-top:2px;">
                                                    {{ $fechareciboCorta }} &middot; O.C.: {{ $ordenCompra }} &middot; Pares: {{ $pares }}
                                                </div>
                                            </td>
                                            <td align="right" valign="middle" style="white-space:nowrap; padding-left:12px;">
                                                <span style="display:inline-block; background-color:#dcfce7; color:#166534; font-size:12px; font-weight:bold; font-family:Segoe UI, Arial, sans-serif; padding:5px 10px; border-radius:999px; white-space:nowrap;">
                                                    ${{ number_format($recibo['Saldo'] ?? 0, 0, ',', '.') }}
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            @endforeach

                            <!-- Fila resumen del pago -->
                            <tr>
                                <td style="padding:11px 0;">
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                        <tr>
                                            <td valign="middle" style="font-family:Segoe UI, Arial, sans-serif; font-size:12px; color:#6b7280; font-weight:bold;">
                                                {{ $voucher['CreatedByDate'] ?? '' }}
                                            </td>
                                            <td align="right" valign="middle" style="font-family:Segoe UI, Arial, sans-serif; font-size:13px; color:#111827; font-weight:bold; white-space:nowrap; padding-left:12px;">
                                                ${{ number_format(collect($recibos)->sum('Saldo'), 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                        </table>

                        <p style="margin:4px 0 8px 0; font-size:12px; color:#166534; text-align:left;">
                            Recibos aplicados al pago del día {{ $voucher['CreatedByDate'] ?? '' }}
                        </p>

                    </td>
                </tr>

                <tr><td style="height:16px; line-height:16px; font-size:0;">&nbsp;</td></tr>

                <!-- Firma -->
                <tr>
                    <td style="padding:12px 4px 4px 4px; font-family:Segoe UI, Arial, sans-serif;">
                        <p style="margin:0 0 4px 0; font-size:14px; color:#374151; line-height:1.6;">
                            Atentamente,
                        </p>
                        <p style="margin:16px 0 0 0; font-size:13px; color:#6b7280; line-height:1.5;">
                            <strong style="color:#374151;">{{ $firma['nombre'] ?? 'Ninoska Fontalvo' }}</strong><br>
                            {{ $firma['cargo'] ?? 'Auxiliar administrativo' }}<br>
                            {{ $firma['departamento'] ?? 'Departamento de Cartera' }}<br>
                            {{ $firma['empresa'] ?? 'Revent Calzado SAS' }}<br>
                            Celular: {{ $firma['celular'] ?? '3222792893' }}
                        </p>
                    </td>
                </tr>

                <tr><td style="height:8px; line-height:8px; font-size:0;">&nbsp;</td></tr>

                <!-- Pie de página -->
                <tr>
                    <td align="center" style="padding:16px 4px 4px 4px; font-family:Segoe UI, Arial, sans-serif;">
                        <img src="https://revent.com.co/cdn/shop/files/Logo_Revent-Negro.png?v=1744326854&width=250" alt="REVENT CALZADO S.A.S." width="90" style="display:block; width:90px; max-width:90px; height:auto; opacity:0.6; margin:0 auto 6px auto;">
                        <p style="margin:0; font-size:11px; color:#9ca3af;">
                            REVENT CALZADO S.A.S. · revent.com.co
                        </p>
                    </td>
                </tr>

            </table>

        </td>
    </tr>
</table>

</body>
</html>
