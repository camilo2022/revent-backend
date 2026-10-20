<!DOCTYPE html>
<html lang="es" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml"
    xmlns:o="urn:schemas-microsoft-com:office:office">

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
    <style>
        /* Estilos de las tablas condensadas: en clases para no repetirlos en cada fila (evita que Gmail recorte el correo) */
        .tt {
            width: 100%;
            border-collapse: collapse;
        }

        .tt th {
            padding: 8px 6px;
            background-color: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            font-size: 10px;
            font-weight: bold;
            color: #9ca3af;
            text-transform: uppercase;
            text-align: left;
            font-family: Segoe UI, Arial, sans-serif;
        }

        .tt td {
            padding: 8px 6px;
            border-bottom: 1px solid #f1f3f5;
            font-size: 12px;
            color: #374151;
            vertical-align: top;
            font-family: Segoe UI, Arial, sans-serif;
        }

        .te th {
            background-color: #fef2f2;
            border-bottom: 1px solid #fecaca;
            color: #b91c1c;
        }

        .te td {
            border-bottom: 1px solid #fee2e2;
        }

        .tt .n {
            color: #9ca3af;
            font-size: 11px;
        }

        .tt .d {
            font-weight: bold;
            color: #1f2937;
            white-space: nowrap;
        }

        .tt .o {
            display: block;
            margin-top: 2px;
            font-size: 11px;
            color: #6b7280;
            font-weight: normal;
            white-space: normal;
        }

        .tt .s {
            display: block;
            font-size: 11px;
            color: #9ca3af;
            font-weight: normal;
        }

        .tt .e {
            color: #991b1b;
        }

        .tt .fr {
            font-weight: bold;
            color: #7f1d1d;
            white-space: nowrap;
        }

        .bt {
            display: inline-block;
            margin-top: 3px;
            padding: 2px 7px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: bold;
            white-space: nowrap;
            background-color: #fef3c7;
            color: #b45309;
        }

        .bd {
            display: inline-block;
            margin-top: 3px;
            padding: 2px 7px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: bold;
            white-space: nowrap;
            background-color: #dcfce7;
            color: #15803d;
        }

        .lk {
            font-size: 12px;
            font-weight: 600;
            color: #1d4ed8;
            text-decoration: none;
            white-space: nowrap;
        }

        .lk2 {
            font-size: 12px;
            font-weight: 600;
            color: #374151;
            text-decoration: none;
            white-space: nowrap;
        }

        .lk3 {
            font-size: 12px;
            font-weight: 600;
            color: #b45309;
            text-decoration: none;
            white-space: nowrap;
        }

        .lbl {
            margin: 0 0 2px 0;
            font-size: 10px;
            font-weight: bold;
            color: #9ca3af;
            text-transform: uppercase;
            font-family: Segoe UI, Arial, sans-serif;
        }

        .val {
            margin: 0;
            font-size: 12px;
            color: #374151;
            font-family: Segoe UI, Arial, sans-serif;
        }
    </style>
</head>

<body style="margin:0; padding:0; background-color:#f3f4f6; -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%;">
    @php
        $bodega = fn($codigo, $data) => $data ? $codigo . ' - ' . $data['name'] : '-';
        $hayTraslados = isset($traslados) && count($traslados) > 0;
        $primero = $hayTraslados ? $traslados[0] : null;
        $conTransito = $hayTraslados && collect($traslados)->contains(fn($t) => !is_null($t['approved'] ?? null));
    @endphp

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
        style="background-color:#f3f4f6;">
        <tr>
            <td align="center" style="padding:24px 12px;">

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                    style="max-width:600px; width:100%;">

                    @if (isset($errors) && count($errors) > 0)

                        {{-- ================= BLOQUE DE ERRORES ================= --}}

                        <!-- Banner de error -->
                        <tr>
                            <td
                                style="background-color:#fef2f2; border:1px solid #fecaca; border-radius:12px; padding:18px 20px; font-family:Segoe UI, Arial, sans-serif;">
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                                    width="100%">
                                    <tr>
                                        <td width="40" valign="top" style="padding-right:12px;">
                                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                                <tr>
                                                    <td width="36" height="36" align="center" valign="middle"
                                                        style="background-color:#dc2626; border-radius:50%; font-family:Arial, sans-serif; color:#ffffff; font-size:16px; font-weight:bold;">
                                                        &#10005;
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                        <td valign="middle">
                                            <p
                                                style="margin:0 0 4px 0; font-size:15px; font-weight:bold; color:#7f1d1d; font-family:Segoe UI, Arial, sans-serif;">
                                                {{ count($errors) }} error(es) de validación
                                            </p>
                                            <p
                                                style="margin:0; font-size:13px; color:#991b1b; font-family:Segoe UI, Arial, sans-serif; line-height:1.4;">
                                                @if ($hayTraslados)
                                                    Algunos registros no se pudieron procesar. Revisa los siguientes
                                                    errores; los traslados que sí se generaron aparecen más abajo.
                                                @else
                                                    No fue posible procesar el cargue. Corrige los siguientes errores y
                                                    vuelve a intentarlo.
                                                @endif
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                        <tr>
                            <td style="height:16px; line-height:16px; font-size:0;">&nbsp;</td>
                        </tr>

                        <!-- Tarjeta con la tabla condensada de errores -->
                        <tr>
                            <td
                                style="background-color:#ffffff; border:1px solid #fee2e2; border-radius:12px; padding:18px 20px;">
                                <table role="presentation" class="tt te" width="100%" cellpadding="0" cellspacing="0"
                                    border="0">
                                    <tr>
                                        <th width="44">Fila</th>
                                        <th>Producto</th>
                                        <th>Bodega</th>
                                        <th>Error</th>
                                    </tr>
                                    {{-- Filas sin indentación: cada byte cuenta para que Gmail no recorte el correo --}}
                                    @foreach ($errors as $index => $error)
                                        <tr>
                                            <td class="fr">{{ $error['Row'] ?? '-' }}</td>
                                            <td>{{ $error['ProductCode'] ?? '-' }}@if (isset($error['Description']) && $error['Description'] != ($error['ProductCode'] ?? null))
                                                    <span class="s">{{ $error['Description'] }}</span>
                                                @endif
                                            </td>
                                            <td>{{ $error['WarehouseCode'] ?? '-' }}</td>
                                            <td class="e">{{ $error['Error'] ?? '' }}@if (isset($error['AvailableQuantity']) || isset($error['RequiredQuantity']))
                                                    <span class="s">Disponible:
                                                        {{ $error['AvailableQuantity'] ?? 0 }} · Requerido:
                                                        {{ $error['RequiredQuantity'] ?? 0 }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </table>
                            </td>
                        </tr>

                        <tr>
                            <td style="height:16px; line-height:16px; font-size:0;">&nbsp;</td>
                        </tr>

                        {{-- =============== FIN BLOQUE DE ERRORES =============== --}}

                    @endif

                    @if ($hayTraslados)

                        {{-- ================= BLOQUE DE ÉXITO ================= --}}

                        <!-- Banner de éxito -->
                        <tr>
                            <td
                                style="background-color:#f0fdf4; border:1px solid #bbf7d0; border-radius:12px; padding:18px 20px; font-family:Segoe UI, Arial, sans-serif;">
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                                    width="100%">
                                    <tr>
                                        <td width="40" valign="top" style="padding-right:12px;">
                                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                                <tr>
                                                    <td width="36" height="36" align="center" valign="middle"
                                                        style="background-color:#16a34a; border-radius:50%; font-family:Arial, sans-serif; color:#ffffff; font-size:16px; font-weight:bold;">
                                                        &#10003;
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                        <td valign="middle">
                                            <p
                                                style="margin:0 0 4px 0; font-size:15px; font-weight:bold; color:#14532d; font-family:Segoe UI, Arial, sans-serif;">
                                                {{ count($traslados) }} traslado(s) generado(s) correctamente
                                            </p>
                                            <p
                                                style="margin:0; font-size:13px; color:#166534; font-family:Segoe UI, Arial, sans-serif; line-height:1.4;">
                                                Los traslados en tránsito requieren descargar el archivo de aprobación,
                                                cargarlo de nuevo y confirmarlo.
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                        <tr>
                            <td style="height:16px; line-height:16px; font-size:0;">&nbsp;</td>
                        </tr>

                        <!-- Tarjeta con todos los traslados -->
                        <tr>
                            <td
                                style="background-color:#ffffff; border:1px solid #eef0f2; border-radius:12px; padding:20px;">
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                    border="0">

                                    <!-- Meta info (común a todos los traslados del cargue) -->
                                    <tr>
                                        <td style="border-bottom:1px solid #f1f3f5; padding:0 0 12px 0;">
                                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                                border="0">
                                                <tr>
                                                    <td width="34%" valign="top">
                                                        <p class="lbl">Usuario</p>
                                                        <p class="val">{{ $primero['data']['user'] }}</p>
                                                    </td>
                                                    <td width="33%" valign="top">
                                                        <p class="lbl">Creado</p>
                                                        <p class="val">{{ $primero['data']['created_at'] }}</p>
                                                    </td>
                                                    <td width="33%" valign="top">
                                                        <p class="lbl">Fecha traslado</p>
                                                        <p class="val">
                                                            {{ \Carbon\Carbon::parse($primero['data']['date'])->format('d/m/Y') }}
                                                        </p>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td style="height:14px; line-height:14px; font-size:0;">&nbsp;</td>
                                    </tr>

                                    <!-- Tabla condensada de traslados -->
                                    <tr>
                                        <td>
                                            <table role="presentation" class="tt" width="100%" cellpadding="0"
                                                cellspacing="0" border="0">
                                                <tr>
                                                    <th width="24">#</th>
                                                    <th>Documento</th>
                                                    <th>Origen &#8594; Destino</th>
                                                    <th align="right" style="text-align:right;">Acciones</th>
                                                </tr>
                                                {{-- Filas sin indentación: cada byte cuenta para que Gmail no recorte el correo --}}
                                                @foreach ($traslados as $index => $traslado)
                                                    <tr>
                                                        <td class="n">{{ $index + 1 }}</td>
                                                        <td class="d">{{ $traslado['data']['document'] }}<span
                                                                class="s">ID {{ $traslado['data']['id'] }} &nbsp;
                                                                @if (!is_null($traslado['approved'] ?? null))
                                                                <span class="bt">En tránsito</span>@else<span
                                                                        class="bd">Directo</span>
                                                                @endif
                                                            </span>
                                                            @if (!empty($traslado['data']['observation']))
                                                                <span
                                                                    class="o">{{ $traslado['data']['observation'] }}</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $bodega($traslado['data']['bodega_salida'], $traslado['data']['bodega_salida_data']) }}<span
                                                                class="s">&#8594;
                                                                {{ $bodega($traslado['data']['bodega_ingreso'], $traslado['data']['bodega_ingreso_data']) }}</span>
                                                        </td>
                                                        <td align="right" style="text-align:right;"><a
                                                                href="{{ $traslado['preview'] }}" target="_blank"
                                                                class="lk">&#128065; Ver</a>
                                                            @if (!is_null($traslado['approved'] ?? null))
                                                                &nbsp;
                                                                <a href="{{ $traslado['approved']['download_url'] }}"
                                                                    class="lk3">&#8681; Excel</a>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </table>
                                        </td>
                                    </tr>

                                    @if ($conTransito)
                                        <tr>
                                            <td style="height:14px; line-height:14px; font-size:0;">&nbsp;</td>
                                        </tr>

                                        <!-- Caja de tránsito -->
                                        <tr>
                                            <td
                                                style="background-color:#fffbeb; border:1px solid #fde68a; border-radius:10px; padding:14px 16px;">
                                                <p
                                                    style="margin:0 0 4px 0; font-size:13px; font-weight:bold; color:#92400e; font-family:Segoe UI, Arial, sans-serif;">
                                                    Traslados en tránsito
                                                </p>
                                                <p
                                                    style="margin:0; font-size:12px; color:#a16207; line-height:1.4; font-family:Segoe UI, Arial, sans-serif;">
                                                    Cuando la mercancía llegue físicamente a la bodega de destino,
                                                    descarga el archivo de aprobación (enlace "Excel" en la tabla) y
                                                    vuelve a cargarlo para confirmarlo en Siigo. Los archivos también
                                                    van adjuntos en este correo.
                                                </p>
                                            </td>
                                        </tr>
                                    @endif

                                </table>
                            </td>
                        </tr>

                        <tr>
                            <td style="height:16px; line-height:16px; font-size:0;">&nbsp;</td>
                        </tr>

                        {{-- =============== FIN BLOQUE DE ÉXITO =============== --}}

                    @endif

                </table>

            </td>
        </tr>
    </table>

</body>

</html>
