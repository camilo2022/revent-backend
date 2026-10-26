@extends('layouts.email')

@section('title', 'Confirmación de salida de inventario')

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
        $conPdf = collect($traslados)->contains(fn($t) => !empty($t['pdf']));
    @endphp

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
        style="background-color:#f3f4f6;">
        <tr>
            <td align="center" style="padding:24px 12px;">

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                    style="max-width:600px; width:100%;">

                    <!-- Banner de confirmación de salida -->
                    <tr>
                        <td
                            style="background-color:#f0fdf4; border:1px solid #bbf7d0; border-radius:12px; padding:18px 20px; font-family:Segoe UI, Arial, sans-serif;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
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
                                            Confirmación de salida de inventario
                                        </p>
                                        <p
                                            style="margin:0; font-size:13px; color:#166534; font-family:Segoe UI, Arial, sans-serif; line-height:1.4;">
                                            Se {{ count($traslados) > 1 ? 'generaron' : 'generó' }}
                                            {{ count($traslados) }} traslado(s) de salida desde tu bodega. Revisa el
                                            detalle a continuación.
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
                                                {{ count($traslados) > 1 ? 'Traslados de salida' : $primero['data']['document'] }}
                                            </p>
                                        </td>
                                        <td valign="top" align="right">
                                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                                <tr>
                                                    <td
                                                        style="background-color:#fee2e2; color:#b91c1c; font-size:11px; font-weight:bold; font-family:Segoe UI, Arial, sans-serif; padding:5px 10px; border-radius:999px; white-space:nowrap;">
                                                        &#8593; Salida
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
                                                        <p class="lbl">Bodega origen</p>
                                                        <p class="val">
                                                            {{ $bodega($primero['data']['bodega_salida'], $primero['data']['bodega_salida_data']) }}
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
                                                    <th>Destino</th>
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
                                                        <td>{{ $bodega($traslado['data']['bodega_ingreso'], $traslado['data']['bodega_ingreso_data']) }}
                                                        </td>
                                                        <td align="right" style="text-align:right;"><a
                                                                href="{{ $traslado['preview'] }}" target="_blank"
                                                                class="lk">&#128065; Ver</a>
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

                                </table>
                            </td>
                        </tr>
                    @endif

                </table>

            </td>
        </tr>
    </table>
@endsection
