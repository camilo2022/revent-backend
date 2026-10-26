@extends('layouts.email')

@section('title', 'Órdenes de compra - REVENT CALZADO S.A.S.')

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

        .tt .s {
            display: block;
            font-size: 11px;
            color: #9ca3af;
            font-weight: normal;
        }

        .dl {
            font-size: 13px;
            font-weight: bold;
            color: #1d4ed8;
            text-decoration: none;
            white-space: nowrap;
        }

        .br {
            display: inline-block;
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
    </style>
@endpush

@section('content')
    @php
        $conPdf = collect($ordenes_compra)->contains(fn($o) => !empty($o['pdf']));
    @endphp

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
        style="background-color:#f3f4f6;">
        <tr>
            <td align="center" style="padding:24px 12px;">

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                    style="max-width:600px; width:100%;">

                    <!-- Logo -->
                    <tr>
                        <td align="center" style="padding:4px 4px 20px 4px;">
                            <img src="https://revent.com.co/cdn/shop/files/Logo_Revent-Negro.png?v=1744326854&width=250"
                                alt="REVENT CALZADO S.A.S." width="150"
                                style="display:block; width:150px; max-width:150px; height:auto;">
                        </td>
                    </tr>

                    <!-- Banner de título -->
                    <tr>
                        <td
                            style="background-color:#111827; border-radius:12px; padding:22px 24px; font-family:Segoe UI, Arial, sans-serif;">
                            <p
                                style="margin:0 0 4px 0; font-size:11px; font-weight:bold; color:#9ca3af; letter-spacing:0.5px; text-transform:uppercase;">
                                REVENT CALZADO S.A.S.
                            </p>
                            <p style="margin:0; font-size:19px; font-weight:bold; color:#ffffff; line-height:1.3;">
                                Orden de producción
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="height:16px; line-height:16px; font-size:0;">&nbsp;</td>
                    </tr>

                    <!-- Cuerpo de la carta -->
                    <tr>
                        <td
                            style="background-color:#ffffff; border:1px solid #eef0f2; border-radius:12px; padding:22px 24px; font-family:Segoe UI, Arial, sans-serif;">

                            <p style="margin:0 0 14px 0; font-size:14px; color:#374151; line-height:1.6;">
                                Estimados <strong>{{ $provider['contactName'] ?? 'señores' }}</strong>,
                            </p>

                            <p style="margin:0 0 16px 0; font-size:14px; color:#374151; line-height:1.6;">
                                Reciban un cordial saludo de parte de <strong>REVENT CALZADO S.A.S.</strong> Por medio
                                del
                                presente correo les remitimos las siguientes órdenes de compra generadas para dar inicio
                                al proceso de producción correspondiente. Les agradecemos revisar el detalle de cada una
                                y confirmarnos su recepción a la mayor brevedad posible.
                            </p>

                            @if (!empty($files) && count($files))

                                <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                                    style="margin:0 auto 16px;">
                                    @foreach ($files->chunk(2) as $fila)
                                        <tr>
                                            @foreach ($fila as $foto)
                                                <td style="padding:4px;">
                                                    <img src="{{ $foto['url'] }}" alt="{{ $referencia }}"
                                                        width="270" height="270"
                                                        style="display:block;width:270px;height:270px;object-fit:contain;border:1px solid #eef0f2;border-radius:10px;">
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach

                                    <tr>
                                        <td colspan="2" align="center" style="padding-top:8px;">
                                            <span
                                                style="display:inline-block; padding:3px 10px; border-radius:999px; background-color:#f3f4f6; font-size:11px; font-weight:bold; color:#4b5563; font-family:Segoe UI, Arial, sans-serif;">
                                                Referencia: {{ $referencia }}
                                            </span>
                                        </td>
                                    </tr>
                                </table>

                            @endif

                            <p style="margin:0; font-size:14px; color:#374151; line-height:1.6;">
                                Pueden acceder al documento completo de cada orden haciendo clic sobre su número.
                                @if ($conPdf)
                                    También van adjuntas en PDF en este correo.
                                @endif
                            </p>

                        </td>
                    </tr>

                    <tr>
                        <td style="height:16px; line-height:16px; font-size:0;">&nbsp;</td>
                    </tr>

                    <!-- Tabla condensada de órdenes -->
                    <tr>
                        <td
                            style="background-color:#ffffff; border:1px solid #eef0f2; border-radius:12px; padding:18px 20px;">
                            <table role="presentation" class="tt" width="100%" cellpadding="0" cellspacing="0"
                                border="0">
                                <tr>
                                    <th width="24">#</th>
                                    <th>Orden de compra</th>
                                    <th>Tipo</th>
                                    <th align="right" style="text-align:right;">Ver</th>
                                </tr>
                                {{-- Filas sin indentación: cada byte cuenta para que Gmail no recorte el correo --}}
                                @foreach ($ordenes_compra as $index => $orden)
                                    <tr>
                                        <td class="n">{{ $index + 1 }}</td>
                                        <td><a href="{{ $orden['url'] }}" target="_blank"
                                                class="dl">{{ $orden['documento'] ?? 'Sin número' }}</a><span
                                                class="s">{{ $orden['bodega']['id'] }} -
                                                {{ $orden['bodega']['name'] }}</span></td>
                                        <td>
                                            @if ($orden['tipo'] === 'REMISION')
                                            <span class="br">Remisión</span>@else<span class="bi">IVA</span>
                                            @endif
                                        </td>
                                        <td align="right" style="text-align:right;"><a href="{{ $orden['url'] }}"
                                                target="_blank" class="lk">&#128065; Ver</a></td>
                                    </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="height:16px; line-height:16px; font-size:0;">&nbsp;</td>
                    </tr>

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

                    <tr>
                        <td style="height:8px; line-height:8px; font-size:0;">&nbsp;</td>
                    </tr>

                    <!-- Pie de página -->
                    <tr>
                        <td align="center" style="padding:16px 4px 4px 4px; font-family:Segoe UI, Arial, sans-serif;">
                            <img src="https://revent.com.co/cdn/shop/files/Logo_Revent-Negro.png?v=1744326854&width=250"
                                alt="REVENT CALZADO S.A.S." width="90"
                                style="display:block; width:90px; max-width:90px; height:auto; opacity:0.6; margin:0 auto 6px auto;">
                            <p style="margin:0; font-size:11px; color:#9ca3af;">
                                REVENT CALZADO S.A.S. · revent.com.co
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>
@endsection
