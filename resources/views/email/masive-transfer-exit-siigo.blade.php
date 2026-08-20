<!DOCTYPE html>
<html lang="es" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>Confirmación de salida de inventario</title>
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

                <!-- Banner de confirmación de salida -->
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
                                        Confirmación de salida de inventario
                                    </p>
                                    <p style="margin:0; font-size:13px; color:#166534; font-family:Segoe UI, Arial, sans-serif; line-height:1.4;">
                                        Se {{ count($traslados) > 1 ? 'generaron' : 'generó' }} {{ count($traslados) }} traslado(s) de salida desde tu bodega. Revisa el detalle a continuación.
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr><td style="height:16px; line-height:16px; font-size:0;">&nbsp;</td></tr>

                @foreach ($traslados as $index => $traslado)
                <!-- Tarjeta de traslado -->
                <tr>
                    <td style="background-color:#ffffff; border:1px solid #eef0f2; border-radius:12px; padding:20px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">

                            <!-- Header: numero + titulo + badge -->
                            <tr>
                                <td valign="top">
                                    <p style="margin:0 0 2px 0; font-size:11px; font-weight:bold; color:#9ca3af; letter-spacing:0.5px; font-family:Segoe UI, Arial, sans-serif; text-transform:uppercase;">
                                        Traslado #{{ $index + 1 }}
                                    </p>
                                    <p style="margin:0; font-size:16px; font-weight:bold; color:#1f2937; font-family:Segoe UI, Arial, sans-serif;">
                                        {{ $traslado['data']['document'] }}
                                    </p>
                                </td>
                                <td valign="top" align="right">
                                    <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                        <tr>
                                            <td style="background-color:#fee2e2; color:#b91c1c; font-size:11px; font-weight:bold; font-family:Segoe UI, Arial, sans-serif; padding:5px 10px; border-radius:999px; white-space:nowrap;">
                                                &#8593; Salida
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                            <tr><td colspan="2" style="height:12px; line-height:12px; font-size:0;">&nbsp;</td></tr>

                            <!-- Meta info -->
                            <tr>
                                <td colspan="2" style="border-top:1px solid #f1f3f5; border-bottom:1px solid #f1f3f5; padding:12px 0;">
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                        <tr>
                                            <td width="25%" valign="top">
                                                <p style="margin:0 0 2px 0; font-size:10px; font-weight:bold; color:#9ca3af; text-transform:uppercase; font-family:Segoe UI, Arial, sans-serif;">Bodega origen</p>
                                                <p style="margin:0; font-size:12px; color:#374151; font-family:Segoe UI, Arial, sans-serif;">{{ $traslado['data']['bodega_salida_data'] ? ($traslado['data']['bodega_salida'] . ' - ' . $traslado['data']['bodega_salida_data']['name']) : '-' }}</p>
                                            </td>
                                            <td width="25%" valign="top">
                                                <p style="margin:0 0 2px 0; font-size:10px; font-weight:bold; color:#9ca3af; text-transform:uppercase; font-family:Segoe UI, Arial, sans-serif;">Bodega destino</p>
                                                <p style="margin:0; font-size:12px; color:#374151; font-family:Segoe UI, Arial, sans-serif;">{{ $traslado['data']['bodega_ingreso_data'] ? ($traslado['data']['bodega_ingreso'] . ' - ' . $traslado['data']['bodega_ingreso_data']['name']) : '-' }}</p>
                                            </td>
                                            <td width="25%" valign="top">
                                                <p style="margin:0 0 2px 0; font-size:10px; font-weight:bold; color:#9ca3af; text-transform:uppercase; font-family:Segoe UI, Arial, sans-serif;">Usuario</p>
                                                <p style="margin:0; font-size:12px; color:#374151; font-family:Segoe UI, Arial, sans-serif;">{{ $traslado['data']['user'] }}</p>
                                            </td>
                                            <td width="25%" valign="top">
                                                <p style="margin:0 0 2px 0; font-size:10px; font-weight:bold; color:#9ca3af; text-transform:uppercase; font-family:Segoe UI, Arial, sans-serif;">Fecha traslado</p>
                                                <p style="margin:0; font-size:12px; color:#374151; font-family:Segoe UI, Arial, sans-serif;">{{ \Carbon\Carbon::parse($traslado['data']['date'])->format('d/m/Y') }}</p>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                            <tr><td colspan="2" style="height:14px; line-height:14px; font-size:0;">&nbsp;</td></tr>

                            <!-- Botones -->
                            <tr>
                                <td colspan="2">
                                    <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                        <tr>
                                            <td style="border-radius:8px; background-color:#eff6ff;">
                                                <a href="{{ $traslado['preview'] }}" target="_blank" style="display:inline-block; padding:9px 16px; font-size:13px; font-weight:600; color:#1d4ed8; text-decoration:none; font-family:Segoe UI, Arial, sans-serif;">
                                                    &#128065; Ver traslado
                                                </a>
                                            </td>
                                            <td width="10" style="font-size:0; line-height:0;">&nbsp;</td>
                                            <td style="border-radius:8px; background-color:#f9fafb; border:1px solid #e5e7eb;">
                                                <a href="{{ $traslado['preview_download'] }}" target="_blank" style="display:inline-block; padding:9px 16px; font-size:13px; font-weight:600; color:#374151; text-decoration:none; font-family:Segoe UI, Arial, sans-serif;">
                                                    &#8681; Descargar traslado
                                                </a>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                        </table>
                    </td>
                </tr>

                <tr><td style="height:16px; line-height:16px; font-size:0;">&nbsp;</td></tr>
                @endforeach

            </table>

        </td>
    </tr>
</table>

</body>
</html>
