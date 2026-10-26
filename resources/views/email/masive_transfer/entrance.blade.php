@extends('layouts.email')

@section('title', 'Inventario recibido en tu bodega')

@push('styles')
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
        /* Estilos de la tabla condensada: en clases para no repetirlos en cada fila (evita que Gmail recorte el correo) */
        .f {
            font-family: Segoe UI, Arial, sans-serif;
        }

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
@endpush

@section('content')
    @php
        $bodega = fn($codigo, $data) => $data ? $codigo . ' - ' . $data['name'] : '-';
        $primero = $traslados[0] ?? null;
        $conTransito = collect($traslados)->filter(fn($t) => !is_null($t['approved'] ?? null));
        $conPdf = collect($traslados)->contains(fn($t) => !empty($t['pdf']));
    @endphp

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
        style="background-color:#f3f4f6;">
        <tr>
            <td align="center" style="padding:24px 12px;">

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                    style="max-width:600px; width:100%;">

                    <!-- Banner de entrada de inventario -->
                    <tr>
                        <td
                            style="background-color:#eff6ff; border:1px solid #bfdbfe; border-radius:12px; padding:18px 20px; font-family:Segoe UI, Arial, sans-serif;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td width="40" valign="top" style="padding-right:12px;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td width="36" height="36" align="center" valign="middle"
                                                    style="background-color:#2563eb; border-radius:50%; font-family:Arial, sans-serif; color:#ffffff; font-size:16px; font-weight:bold;">
                                                    &#8595;
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td valign="middle">
                                        <p
                                            style="margin:0 0 4px 0; font-size:15px; font-weight:bold; color:#1e3a8a; font-family:Segoe UI, Arial, sans-serif;">
                                            Enviaron inventario a tu bodega
                                        </p>
                                        <p
                                            style="margin:0; font-size:13px; color:#1d4ed8; font-family:Segoe UI, Arial, sans-serif; line-height:1.4;">
                                            Te informamos que {{ count($traslados) > 1 ? 'llegaron' : 'llegó' }}
                                            {{ count($traslados) }} traslado(s) con mercancía destinada a tu bodega.
                                            Revisa el detalle a continuación.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="height:16px; line-height:16px; font-size:0;">&nbsp;</td>
                    </tr>

                    @if ($primero)
                        <!-- Tarjeta con todos los traslados -->
                        <tr>
                            <td
                                style="background-color:#ffffff; border:1px solid #eef0f2; border-radius:12px; padding:20px;">
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                    border="0">

                                    <!-- Header: titulo + badge -->
                                    <tr>
                                        <td valign="top">
                                            <p
                                                style="margin:0 0 2px 0; font-size:11px; font-weight:bold; color:#9ca3af; letter-spacing:0.5px; font-family:Segoe UI, Arial, sans-serif; text-transform:uppercase;">
                                                {{ count($traslados) }} traslado(s)
                                            </p>
                                            <p
                                                style="margin:0; font-size:16px; font-weight:bold; color:#1f2937; font-family:Segoe UI, Arial, sans-serif;">
                                                {{ count($traslados) > 1 ? 'Traslados recibidos' : $primero['data']['document'] }}
                                            </p>
                                        </td>
                                        <td valign="top" align="right">
                                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                                <tr>
                                                    <td
                                                        style="background-color:#dbeafe; color:#1d4ed8; font-size:11px; font-weight:bold; font-family:Segoe UI, Arial, sans-serif; padding:5px 10px; border-radius:999px; white-space:nowrap;">
                                                        &#8595; Entrada
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td colspan="2" style="height:12px; line-height:12px; font-size:0;">&nbsp;
                                        </td>
                                    </tr>

                                    <!-- Meta info (común a todos los traslados del cargue) -->
                                    <tr>
                                        <td colspan="2"
                                            style="border-top:1px solid #f1f3f5; border-bottom:1px solid #f1f3f5; padding:12px 0;">
                                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                                border="0">
                                                <tr>
                                                    <td width="34%" valign="top">
                                                        <p class="lbl">Bodega destino</p>
                                                        <p class="val">
                                                            {{ $bodega($primero['data']['bodega_ingreso'], $primero['data']['bodega_ingreso_data']) }}
                                                        </p>
                                                    </td>
                                                    <td width="33%" valign="top">
                                                        <p class="lbl">Usuario</p>
                                                        <p class="val">{{ $primero['data']['user'] }}</p>
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
                                        <td colspan="2" style="height:14px; line-height:14px; font-size:0;">&nbsp;
                                        </td>
                                    </tr>

                                    <!-- Tabla condensada de traslados -->
                                    <tr>
                                        <td colspan="2">
                                            <table role="presentation" class="tt" width="100%" cellpadding="0"
                                                cellspacing="0" border="0">
                                                <tr>
                                                    <th width="24">#</th>
                                                    <th>Documento</th>
                                                    <th>Origen</th>
                                                    <th align="right" style="text-align:right;">Acciones</th>
                                                </tr>
                                                {{-- Filas sin indentación: cada byte cuenta para que Gmail no recorte el correo --}}
                                                @foreach ($traslados as $index => $traslado)
                                                    <tr>
                                                        <td class="n">{{ $index + 1 }}</td>
                                                        <td class="d">{{ $traslado['data']['document'] }}
                                                            @if (!empty($traslado['data']['observation']))
                                                                <span
                                                                    class="o">{{ $traslado['data']['observation'] }}</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $bodega($traslado['data']['bodega_salida'], $traslado['data']['bodega_salida_data']) }}
                                                        </td>
                                                        <td align="right" style="text-align:right;"><a
                                                                href="{{ $traslado['preview'] }}" target="_blank"
                                                                class="lk">&#128065; Ver</a>
                                                            @if (!empty($traslado['approved']['download_url'] ?? null))
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

                                    @if ($conPdf)
                                        <tr>
                                            <td colspan="2" style="padding-top:12px;">
                                                <p
                                                    style="margin:0; font-size:11px; color:#6b7280; font-family:Segoe UI, Arial, sans-serif;">
                                                    &#128206; Adjuntamos el PDF de la nota de traslado.
                                                </p>
                                            </td>
                                        </tr>
                                    @endif

                                    @if ($conTransito->isNotEmpty())
                                        <tr>
                                            <td colspan="2" style="height:14px; line-height:14px; font-size:0;">
                                                &nbsp;</td>
                                        </tr>

                                        <!-- Caja de tránsito -->
                                        <tr>
                                            <td colspan="2"
                                                style="background-color:#fffbeb; border:1px solid #fde68a; border-radius:10px; padding:14px 16px;">
                                                <p
                                                    style="margin:0 0 4px 0; font-size:13px; font-weight:bold; color:#92400e; font-family:Segoe UI, Arial, sans-serif;">
                                                    Traslado en tránsito
                                                </p>
                                                <p
                                                    style="margin:0{{ $conTransito->count() === 1 ? ' 0 12px 0' : '' }}; font-size:12px; color:#a16207; line-height:1.4; font-family:Segoe UI, Arial, sans-serif;">
                                                    Cuando la mercancía llegue físicamente a tu bodega, descarga
                                                    {{ $conTransito->count() === 1 ? 'este archivo' : 'el Excel de cada traslado (enlace "Excel" en la tabla)' }},
                                                    cárgalo de nuevo y confírmalo en Siigo. También
                                                    {{ $conTransito->count() === 1 ? 'va adjunto' : 'van adjuntos' }}
                                                    en este correo.
                                                </p>
                                            </td>
                                        </tr>
                                    @endif

                                </table>
                            </td>
                        </tr>
                    @endif

                </table>

            </td>
        </tr>
    </table>
@endsection
