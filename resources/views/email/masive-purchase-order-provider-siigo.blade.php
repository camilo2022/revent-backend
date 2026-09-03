<!-- resources/views/email/masive-purchase-order-siigo.blade.php -->
<!DOCTYPE html>
<html lang="es" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>Órdenes de compra - REVENT CALZADO S.A.S.</title>
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

            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px; width:100%;">

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
                            Orden de producción
                        </p>
                    </td>
                </tr>

                <tr><td style="height:16px; line-height:16px; font-size:0;">&nbsp;</td></tr>

                <!-- Cuerpo de la carta -->
                <tr>
                    <td style="background-color:#ffffff; border:1px solid #eef0f2; border-radius:12px; padding:24px; font-family:Segoe UI, Arial, sans-serif;">

                        <p style="margin:0 0 16px 0; font-size:14px; color:#374151; line-height:1.6;">
                            Estimados <strong>{{ $proveedor ?? 'señores' }}</strong>,
                        </p>

                        <p style="margin:0 0 16px 0; font-size:14px; color:#374151; line-height:1.6;">
                            Reciban un cordial saludo de parte de <strong>REVENT CALZADO S.A.S.</strong> Por medio del
                            presente correo les remitimos las siguientes órdenes de compra generadas para dar inicio
                            al proceso de producción correspondiente. Les agradecemos revisar el detalle de cada una
                            y confirmarnos su recepción a la mayor brevedad posible.
                        </p>

                        @if(!empty($producto_imagen))
                        <!-- Foto del producto a producir -->
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 16px 0;">
                            <tr>
                                <td align="center">
                                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="width:160px;">
                                        <tr>
                                            <td style="border:1px solid #eef0f2; border-radius:12px; overflow:hidden; background-color:#f9fafb;">
                                                <img src="{{ $producto_imagen }}" alt="{{ $producto_nombre ?? 'Producto a producir' }}" width="160" style="display:block; width:160px; height:auto;">
                                            </td>
                                        </tr>
                                        @if(!empty($producto_nombre))
                                        <tr>
                                            <td align="center" style="padding-top:8px;">
                                                <p style="margin:0; font-size:12px; font-weight:600; color:#6b7280;">
                                                    Referencia: {{ $producto_nombre }}
                                                </p>
                                            </td>
                                        </tr>
                                        @endif
                                    </table>
                                </td>
                            </tr>
                        </table>
                        @endif

                        <p style="margin:0; font-size:14px; color:#374151; line-height:1.6;">
                            Pueden acceder al documento completo de cada orden haciendo clic sobre su número.
                        </p>

                    </td>
                </tr>

                <tr><td style="height:16px; line-height:16px; font-size:0;">&nbsp;</td></tr>

                <!-- Listado compacto de órdenes -->
                <tr>
                    <td style="background-color:#ffffff; border:1px solid #eef0f2; border-radius:12px; padding:8px 20px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">

                            @foreach ($ordenes_compra as $index => $orden)
                            <tr>
                                <td style="padding:11px 0; {{ !$loop->last ? 'border-bottom:1px solid #f1f3f5;' : '' }}">
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                        <tr>
                                            <!-- # -->
                                            <td width="26" valign="middle" style="font-family:Segoe UI, Arial, sans-serif; font-size:11px; color:#9ca3af; font-weight:bold;">
                                                {{ $index + 1 }}.
                                            </td>
                                            <!-- Documento (link) + bodega -->
                                            <td valign="middle" style="font-family:Segoe UI, Arial, sans-serif;">
                                                <a href="{{ $orden['url'] }}" target="_blank" style="font-size:13px; font-weight:bold; color:#1d4ed8; text-decoration:none;">
                                                    {{ $orden['documento'] ?? 'Sin número' }}
                                                </a>
                                                <div style="font-size:11px; color:#9ca3af; margin-top:1px;">
                                                    {{ $orden['bodega']['id'] }} - {{ $orden['bodega']['name'] }}
                                                </div>
                                            </td>
                                            <!-- Badge tipo -->
                                            <td width="80" align="right" valign="middle">
                                                <span style="display:inline-block; background-color:{{ $orden['tipo'] === 'REMISION' ? '#e0e7ff' : '#dcfce7' }}; color:{{ $orden['tipo'] === 'REMISION' ? '#3730a3' : '#166534' }}; font-size:10px; font-weight:bold; font-family:Segoe UI, Arial, sans-serif; padding:4px 8px; border-radius:999px; white-space:nowrap;">
                                                    {{ $orden['tipo'] === 'REMISION' ? 'Remisión' : 'IVA' }}
                                                </span>
                                            </td>
                                            <!-- Ver -->
                                            <td width="20" align="right" valign="middle">
                                                <a href="{{ $orden['url'] }}" target="_blank" style="font-size:14px; color:#1d4ed8; text-decoration:none;">&#8250;</a>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            @endforeach

                        </table>
                    </td>
                </tr>

                <tr><td style="height:16px; line-height:16px; font-size:0;">&nbsp;</td></tr>

                <!-- Firma -->
                <tr>
                    <td style="padding:12px 4px 4px 4px; font-family:Segoe UI, Arial, sans-serif;">
                        <p style="margin:0 0 4px 0; font-size:14px; color:#374151; line-height:1.6;">
                            Quedamos atentos a cualquier duda o novedad.
                        </p>
                        <p style="margin:16px 0 0 0; font-size:13px; color:#6b7280; line-height:1.5;">
                            Cordialmente,<br>
                            <strong style="color:#374151;">REVENT CALZADO S.A.S.</strong>
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
