<!-- resources/views/email/masive-purchase-order-siigo.blade.php -->
<!DOCTYPE html>
<html lang="es" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>Resultado de órdenes de compra</title>
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

                @if (isset($errors) && count($errors) > 0)

                    {{-- ================= BLOQUE DE ERRORES ================= --}}

                    <!-- Banner de error -->
                    <tr>
                        <td style="background-color:#fef2f2; border:1px solid #fecaca; border-radius:12px; padding:18px 20px; font-family:Segoe UI, Arial, sans-serif;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td width="40" valign="top" style="padding-right:12px;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td width="36" height="36" align="center" valign="middle" style="background-color:#dc2626; border-radius:50%; font-family:Arial, sans-serif; color:#ffffff; font-size:16px; font-weight:bold;">
                                                    &#10005;
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td valign="middle">
                                        <p style="margin:0 0 4px 0; font-size:15px; font-weight:bold; color:#7f1d1d; font-family:Segoe UI, Arial, sans-serif;">
                                            {{ count($errors) }} error(es) de validación
                                        </p>
                                        <p style="margin:0; font-size:13px; color:#991b1b; font-family:Segoe UI, Arial, sans-serif; line-height:1.4;">
                                            No fue posible generar la(s) orden(es) de compra. Corrige los siguientes errores y vuelve a intentarlo.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr><td style="height:16px; line-height:16px; font-size:0;">&nbsp;</td></tr>

                    @foreach ($errors as $error)
                    <!-- Tarjeta de error -->
                    <tr>
                        <td style="background-color:#ffffff; border:1px solid #fee2e2; border-radius:12px; padding:18px 20px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">

                                <!-- Header: icono + fila -->
                                <tr>
                                    <td valign="middle">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td width="26" valign="middle" style="padding-right:10px;">
                                                    <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                                        <tr>
                                                            <td width="26" height="26" align="center" valign="middle" style="background-color:#fee2e2; border-radius:50%; font-family:Arial, sans-serif; color:#dc2626; font-size:13px; font-weight:bold;">
                                                                &#10005;
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                <td valign="middle">
                                                    <p style="margin:0; font-size:14px; font-weight:bold; color:#7f1d1d; font-family:Segoe UI, Arial, sans-serif;">
                                                        Fila: {{ $error['Row'] ?? '-' }}
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                @if (isset($error['ProductCode']) || isset($error['WarehouseCode']))
                                <tr><td style="height:10px; line-height:10px; font-size:0;">&nbsp;</td></tr>
                                <tr>
                                    <td>
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                @if (isset($error['ProductCode']))
                                                <td valign="top" style="padding-right:16px; padding-bottom:6px;">
                                                    <p style="margin:0 0 2px 0; font-size:10px; font-weight:bold; color:#b91c1c; text-transform:uppercase; font-family:Segoe UI, Arial, sans-serif;">Producto</p>
                                                    <p style="margin:0; font-size:12px; color:#374151; font-family:Segoe UI, Arial, sans-serif;">{{ $error['ProductCode'] }}</p>
                                                </td>
                                                @endif
                                                @if (isset($error['WarehouseCode']))
                                                <td valign="top" style="padding-bottom:6px;">
                                                    <p style="margin:0 0 2px 0; font-size:10px; font-weight:bold; color:#b91c1c; text-transform:uppercase; font-family:Segoe UI, Arial, sans-serif;">Bodega</p>
                                                    <p style="margin:0; font-size:12px; color:#374151; font-family:Segoe UI, Arial, sans-serif;">{{ $error['WarehouseCode'] }}</p>
                                                </td>
                                                @endif
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                @endif

                                <tr><td style="height:10px; line-height:10px; font-size:0;">&nbsp;</td></tr>

                                <!-- Mensaje de error -->
                                <tr>
                                    <td style="background-color:#fef2f2; border:1px solid #fee2e2; border-radius:8px; padding:10px 14px;">
                                        <p style="margin:0; font-size:13px; color:#991b1b; line-height:1.4; font-family:Segoe UI, Arial, sans-serif;">
                                            {{ $error['Error'] ?? '' }}
                                        </p>
                                    </td>
                                </tr>

                            </table>
                        </td>
                    </tr>

                    <tr><td style="height:16px; line-height:16px; font-size:0;">&nbsp;</td></tr>
                    @endforeach

                    {{-- =============== FIN BLOQUE DE ERRORES =============== --}}

                @endif

                @if (!empty($ordenes_compra))

                    {{-- ================= BLOQUE DE ÉXITO ================= --}}

                    <!-- Banner de éxito -->
                    <tr>
                        <td style="background-color:#f0fdf4; border:1px solid #bbf7d0; border-radius:12px; padding:18px 20px; font-family:Segoe UI, Arial, sans-serif;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td width="40" valign="top" style="padding-right:12px;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td width="36" height="36" align="center" valign="middle" style="background-color:#16a34a; border-radius:50%; font-family:Arial, sans-serif; color:#ffffff; font-size:16px; font-weight:bold;">
                                                    &#10003;
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td valign="middle">
                                        <p style="margin:0 0 4px 0; font-size:15px; font-weight:bold; color:#14532d; font-family:Segoe UI, Arial, sans-serif;">
                                            {{ count($ordenes_compra) }} orden(es) de compra generada(s) correctamente
                                        </p>
                                        <p style="margin:0; font-size:13px; color:#166534; font-family:Segoe UI, Arial, sans-serif; line-height:1.4;">
                                            Puedes ver el detalle de cada orden en Siigo desde el botón "Ver orden".
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr><td style="height:16px; line-height:16px; font-size:0;">&nbsp;</td></tr>

                    @foreach ($ordenes_compra as $index => $orden)
                    <!-- Tarjeta de orden de compra -->
                    <tr>
                        <td style="background-color:#ffffff; border:1px solid #eef0f2; border-radius:16px; padding:20px 22px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">

                                {{-- ================= HEADER ================= --}}
                                <tr>
                                    <td>
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td valign="top">
                                                    <p style="margin:0 0 2px 0; font-size:11px; font-weight:bold; color:#9ca3af; text-transform:uppercase; letter-spacing:0.03em; font-family:Segoe UI, Arial, sans-serif;">
                                                        Orden de compra #{{ $index + 1 }}
                                                    </p>
                                                    <p style="margin:0; font-size:16px; font-weight:bold; color:#111827; font-family:Segoe UI, Arial, sans-serif;">
                                                        {{ $orden['documento'] ?? 'Sin número' }}
                                                    </p>
                                                </td>
                                                <td align="right" valign="top">
                                                    @if (($orden['tipo'] ?? null) === 'REMISION')
                                                        <span style="display:inline-block; padding:4px 11px; border-radius:999px; font-size:11px; font-weight:bold; font-family:Segoe UI, Arial, sans-serif; background-color:#e0e7ff; color:#3730a3; white-space:nowrap;">
                                                            REMISIÓN
                                                        </span>
                                                    @else
                                                        <span style="display:inline-block; padding:4px 11px; border-radius:999px; font-size:11px; font-weight:bold; font-family:Segoe UI, Arial, sans-serif; background-color:#dcfce7; color:#166534; white-space:nowrap;">
                                                            IVA
                                                        </span>
                                                    @endif
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <tr><td style="height:14px; line-height:14px; font-size:0;">&nbsp;</td></tr>

                                {{-- ================= DETALLES ================= --}}
                                <tr>
                                    <td style="border-top:1px solid #f1f3f5; padding-top:14px;">
                                        <p style="margin:0 0 2px 0; font-size:11px; font-weight:bold; color:#9ca3af; text-transform:uppercase; letter-spacing:0.03em; font-family:Segoe UI, Arial, sans-serif;">
                                            Bodega
                                        </p>
                                        <p style="margin:0; font-size:13px; font-weight:500; color:#374151; font-family:Segoe UI, Arial, sans-serif;">
                                            {{ $orden['bodega']['id'] ?? '-' }} - {{ $orden['bodega']['name'] ?? 'Sin bodega' }}
                                        </p>
                                    </td>
                                </tr>

                                @if (!empty($orden['url']))
                                <tr><td style="height:16px; line-height:16px; font-size:0;">&nbsp;</td></tr>

                                {{-- ================= FOOTER ================= --}}
                                <tr>
                                    <td>
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="border-radius:8px; background-color:#eff6ff;">
                                                    <a href="{{ $orden['url'] }}" target="_blank" style="display:inline-block; padding:9px 16px; font-size:13px; font-weight:600; color:#1d4ed8; text-decoration:none; font-family:Segoe UI, Arial, sans-serif;">
                                                        &#128065; Ver orden de compra
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                @endif

                            </table>
                        </td>
                    </tr>

                    <tr><td style="height:16px; line-height:16px; font-size:0;">&nbsp;</td></tr>
                    @endforeach

                    {{-- =============== FIN BLOQUE DE ÉXITO =============== --}}

                @endif

            </table>

        </td>
    </tr>
</table>

</body>
</html>
