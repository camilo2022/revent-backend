<!DOCTYPE html>
<html lang="es" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>Resultado de traslados masivos</title>
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
                                            No fue posible procesar el cargue. Corrige los siguientes errores y vuelve a intentarlo.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr><td style="height:16px; line-height:16px; font-size:0;">&nbsp;</td></tr>

                    @foreach ($errors as $index => $error)
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

                                @if (isset($error['ProductCode']) || isset($error['Description']) || isset($error['WarehouseCode']) || isset($error['AvailableQuantity']) || isset($error['RequiredQuantity']))
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
                                                @if (isset($error['Description']))
                                                <td valign="top" style="padding-right:16px; padding-bottom:6px;">
                                                    <p style="margin:0 0 2px 0; font-size:10px; font-weight:bold; color:#b91c1c; text-transform:uppercase; font-family:Segoe UI, Arial, sans-serif;">Descripción</p>
                                                    <p style="margin:0; font-size:12px; color:#374151; font-family:Segoe UI, Arial, sans-serif;">{{ $error['Description'] }}</p>
                                                </td>
                                                @endif
                                                @if (isset($error['WarehouseCode']))
                                                <td valign="top" style="padding-right:16px; padding-bottom:6px;">
                                                    <p style="margin:0 0 2px 0; font-size:10px; font-weight:bold; color:#b91c1c; text-transform:uppercase; font-family:Segoe UI, Arial, sans-serif;">Bodega</p>
                                                    <p style="margin:0; font-size:12px; color:#374151; font-family:Segoe UI, Arial, sans-serif;">{{ $error['WarehouseCode'] }}</p>
                                                </td>
                                                @endif
                                                @if (isset($error['AvailableQuantity']))
                                                <td valign="top" style="padding-right:16px; padding-bottom:6px;">
                                                    <p style="margin:0 0 2px 0; font-size:10px; font-weight:bold; color:#b91c1c; text-transform:uppercase; font-family:Segoe UI, Arial, sans-serif;">Disponible</p>
                                                    <p style="margin:0; font-size:12px; color:#374151; font-family:Segoe UI, Arial, sans-serif;">{{ $error['AvailableQuantity'] ?? 0 }}</p>
                                                </td>
                                                @endif
                                                @if (isset($error['RequiredQuantity']))
                                                <td valign="top" style="padding-bottom:6px;">
                                                    <p style="margin:0 0 2px 0; font-size:10px; font-weight:bold; color:#b91c1c; text-transform:uppercase; font-family:Segoe UI, Arial, sans-serif;">Requerido</p>
                                                    <p style="margin:0; font-size:12px; color:#374151; font-family:Segoe UI, Arial, sans-serif;">{{ $error['RequiredQuantity'] ?? 0 }}</p>
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

                @if (isset($traslados) && count($traslados) > 0)

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
                                            {{ count($traslados) }} traslado(s) generado(s) correctamente
                                        </p>
                                        <p style="margin:0; font-size:13px; color:#166534; font-family:Segoe UI, Arial, sans-serif; line-height:1.4;">
                                            Los traslados en tránsito requieren descargar el archivo de aprobación, cargarlo de nuevo y confirmarlo.
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
                                        @if (!is_null($traslado['approved']))
                                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                                <tr>
                                                    <td style="background-color:#fef3c7; color:#b45309; font-size:11px; font-weight:bold; font-family:Segoe UI, Arial, sans-serif; padding:5px 10px; border-radius:999px; white-space:nowrap;">
                                                        En tránsito
                                                    </td>
                                                </tr>
                                            </table>
                                        @else
                                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                                <tr>
                                                    <td style="background-color:#dcfce7; color:#15803d; font-size:11px; font-weight:bold; font-family:Segoe UI, Arial, sans-serif; padding:5px 10px; border-radius:999px; white-space:nowrap;">
                                                        Directo
                                                    </td>
                                                </tr>
                                            </table>
                                        @endif
                                    </td>
                                </tr>

                                <tr><td colspan="2" style="height:12px; line-height:12px; font-size:0;">&nbsp;</td></tr>

                                <!-- Meta info -->
                                <tr>
                                    <td colspan="2" style="border-top:1px solid #f1f3f5; border-bottom:1px solid #f1f3f5; padding:12px 0;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td width="25%" valign="top">
                                                    <p style="margin:0 0 2px 0; font-size:10px; font-weight:bold; color:#9ca3af; text-transform:uppercase; font-family:Segoe UI, Arial, sans-serif;">Usuario</p>
                                                    <p style="margin:0; font-size:12px; color:#374151; font-family:Segoe UI, Arial, sans-serif;">{{ $traslado['data']['user'] }}</p>
                                                </td>
                                                <td width="25%" valign="top">
                                                    <p style="margin:0 0 2px 0; font-size:10px; font-weight:bold; color:#9ca3af; text-transform:uppercase; font-family:Segoe UI, Arial, sans-serif;">Creado</p>
                                                    <p style="margin:0; font-size:12px; color:#374151; font-family:Segoe UI, Arial, sans-serif;">{{ $traslado['data']['created_at'] }}</p>
                                                </td>
                                                <td width="25%" valign="top">
                                                    <p style="margin:0 0 2px 0; font-size:10px; font-weight:bold; color:#9ca3af; text-transform:uppercase; font-family:Segoe UI, Arial, sans-serif;">Fecha traslado</p>
                                                    <p style="margin:0; font-size:12px; color:#374151; font-family:Segoe UI, Arial, sans-serif;">{{ \Carbon\Carbon::parse($traslado['data']['date'])->format('d/m/Y') }}</p>
                                                </td>
                                                <td width="25%" valign="top">
                                                    <p style="margin:0 0 2px 0; font-size:10px; font-weight:bold; color:#9ca3af; text-transform:uppercase; font-family:Segoe UI, Arial, sans-serif;">ID</p>
                                                    <p style="margin:0; font-size:12px; color:#374151; font-family:Segoe UI, Arial, sans-serif;">{{ $traslado['data']['id'] }}</p>
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

                                @if (!is_null($traslado['approved']))
                                <tr><td colspan="2" style="height:14px; line-height:14px; font-size:0;">&nbsp;</td></tr>

                                <!-- Caja de tránsito -->
                                <tr>
                                    <td colspan="2" style="background-color:#fffbeb; border:1px solid #fde68a; border-radius:10px; padding:14px 16px;">
                                        <p style="margin:0 0 4px 0; font-size:13px; font-weight:bold; color:#92400e; font-family:Segoe UI, Arial, sans-serif;">
                                            Traslado en tránsito
                                        </p>
                                        <p style="margin:0 0 12px 0; font-size:12px; color:#a16207; line-height:1.4; font-family:Segoe UI, Arial, sans-serif;">
                                            Este traslado quedó en tránsito. Cuando la mercancía llegue físicamente a la bodega de destino, descarga este archivo y vuelve a cargarlo para confirmarlo en Siigo.
                                        </p>
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="border-radius:8px; background-color:#d97706;">
                                                    <a href="{{ $traslado['approved']['download_url'] }}" style="display:inline-block; padding:10px 16px; font-size:13px; font-weight:600; color:#ffffff; text-decoration:none; font-family:Segoe UI, Arial, sans-serif;">
                                                        &#8681; {{ $traslado['approved']['name'] }}
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
