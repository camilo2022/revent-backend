<!DOCTYPE html>
<html lang="es" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml"
    xmlns:o="urn:schemas-microsoft-com:office:office">

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

        .br {
            display: inline-block;
            margin-top: 3px;
            padding: 2px 7px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: bold;
            white-space: nowrap;
            background-color: #e0e7ff;
            color: #3730a3;
        }

        .bi {
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

        .bp {
            display: inline-block;
            margin-top: 3px;
            padding: 2px 7px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: bold;
            white-space: nowrap;
            background-color: #f3f4f6;
            color: #4b5563;
        }

        .lk {
            font-size: 12px;
            font-weight: 600;
            color: #1d4ed8;
            text-decoration: none;
            white-space: nowrap;
        }
    </style>
</head>

<body style="margin:0; padding:0; background-color:#f3f4f6; -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%;">
    @php
        $hayOrdenes = !empty($ordenes_compra);
        $conPdf = $hayOrdenes && collect($ordenes_compra)->contains(fn($o) => !empty($o['pdf']));
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
                                                @if ($hayOrdenes)
                                                    Algunas órdenes no se pudieron generar. Revisa los siguientes
                                                    errores; las órdenes que sí se generaron aparecen más abajo.
                                                @else
                                                    No fue posible generar la(s) orden(es) de compra. Corrige los
                                                    siguientes errores y vuelve a intentarlo.
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
                                    @foreach ($errors as $error)
                                        <tr>
                                            <td class="fr">{{ $error['Row'] ?? '-' }}</td>
                                            <td>{{ $error['ProductCode'] ?? '-' }}</td>
                                            <td>{{ $error['WarehouseCode'] ?? '-' }}</td>
                                            <td class="e">{{ $error['Error'] ?? '' }}</td>
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

                    @if ($hayOrdenes)

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
                                                {{ count($ordenes_compra) }} orden(es) de compra generada(s)
                                                correctamente
                                            </p>
                                            <p
                                                style="margin:0; font-size:13px; color:#166534; font-family:Segoe UI, Arial, sans-serif; line-height:1.4;">
                                                Puedes ver cada orden en Siigo desde el enlace "Ver".@if ($conPdf)
                                                    Los PDF van adjuntos en este correo.
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

                        <!-- Tarjeta con la tabla condensada de órdenes -->
                        <tr>
                            <td
                                style="background-color:#ffffff; border:1px solid #eef0f2; border-radius:12px; padding:20px;">
                                <table role="presentation" class="tt" width="100%" cellpadding="0" cellspacing="0"
                                    border="0">
                                    <tr>
                                        <th width="24">#</th>
                                        <th>Documento</th>
                                        <th>Bodega</th>
                                        <th align="right" style="text-align:right;">Acciones</th>
                                    </tr>
                                    {{-- Filas sin indentación: cada byte cuenta para que Gmail no recorte el correo --}}
                                    @foreach ($ordenes_compra as $index => $orden)
                                        <tr>
                                            <td class="n">{{ $index + 1 }}</td>
                                            <td class="d">{{ $orden['documento'] ?? 'Sin número' }}<span
                                                    class="s">
                                                    @if (($orden['tipo'] ?? null) === 'REMISION')
                                                    <span class="br">Remisión</span>@else<span
                                                            class="bi">IVA</span>
                                                        @endif @if (!empty($orden['pdf']))
                                                            <span class="bp">PDF adjunto</span>
                                                        @endif
                                                </span>
                                            </td>
                                            <td>{{ $orden['bodega']['id'] ?? '-' }} -
                                                {{ $orden['bodega']['name'] ?? 'Sin bodega' }}</td>
                                            <td align="right" style="text-align:right;">
                                                @if (!empty($orden['url']))
                                                    <a href="{{ $orden['url'] }}" target="_blank"
                                                    class="lk">&#128065; Ver</a>@else<span
                                                        class="n">-</span>
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

                        {{-- =============== FIN BLOQUE DE ÉXITO =============== --}}

                    @endif

                </table>

            </td>
        </tr>
    </table>

</body>

</html>
