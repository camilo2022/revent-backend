{{-- resources/views/email/invoice-purchase-order-confirmed.blade.php --}}

<!DOCTYPE html>
<html lang="es" xmlns="http://www.w3.org/1999/xhtml"
    xmlns:v="urn:schemas-microsoft-com:vml"
    xmlns:o="urn:schemas-microsoft-com:office:office">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    @php
        $orderId = data_get($data, 'Order.ACEntryID');
        $orderLink = $orderId
            ? 'https://siigonube.siigo.com/#/asp/' . base64_encode("Default.aspx?TabID=1671&ERPDocumentID={$orderId}") . '?TabID=1671'
            : '';
    @endphp

    <title>Recepción de orden de compra {{ data_get($data, 'Order.DocName', '') }}</title>

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

                {{-- LOGO --}}
                <tr>
                    <td align="center" style="padding:4px 4px 18px 4px;">
                        <img src="https://revent.com.co/cdn/shop/files/Logo_Revent-Negro.png?v=1744326854&width=250"
                            alt="REVENT CALZADO S.A.S."
                            width="150"
                            style="display:block; width:150px; max-width:150px; height:auto;">
                    </td>
                </tr>

                {{-- BANNER --}}
                <tr>
                    <td style="background-color:#111827; border-radius:12px; padding:20px 22px; font-family:Segoe UI, Arial, sans-serif;">
                        <p style="margin:0 0 3px 0; font-size:10px; font-weight:bold; color:#9ca3af; letter-spacing:0.5px; text-transform:uppercase;">
                            REVENT CALZADO S.A.S.
                        </p>

                        <p style="margin:0; font-size:17px; font-weight:bold; color:#ffffff; line-height:1.3;">
                            Recepción de orden de compra {{ data_get($data, 'Order.DocName', '') }}
                        </p>
                    </td>
                </tr>

                <tr>
                    <td style="height:12px; line-height:12px; font-size:0;">&nbsp;</td>
                </tr>

                {{-- MENSAJE PRINCIPAL --}}
                <tr>
                    <td style="background-color:#ffffff; border:1px solid #eef0f2; border-radius:12px; padding:20px; font-family:Segoe UI, Arial, sans-serif;">

                        <p style="margin:0 0 12px 0; font-size:14px; color:#374151; line-height:1.5;">
                            Estimado señor(a),
                        </p>

                        <p style="margin:0 0 12px 0; font-size:14px; color:#374151; line-height:1.5;">
                            Reciba un cordial saludo de parte de <strong>REVENT CALZADO S.A.S.</strong>
                        </p>

                        <p style="margin:0 0 12px 0; font-size:14px; color:#374151; line-height:1.5;">
                            Se informa que se realizó la recepción de la orden de compra

                            @if(!empty($orderLink))
                                <a href="{{ $orderLink }}" target="_blank"
                                    style="color:#1d4ed8; font-weight:bold; text-decoration:none;">
                                    {{ data_get($data, 'Order.DocName', '') }}
                                </a>
                            @else
                                <strong>{{ data_get($data, 'Order.DocName', '') }}</strong>
                            @endif
                            .
                        </p>

                        <p style="margin:0; font-size:13px; color:#6b7280; line-height:1.5;">
                            A continuación, encontrará el detalle de las cantidades solicitadas, recibidas y pendientes, junto con el checklist correspondiente a la recepción.
                        </p>

                    </td>
                </tr>

                <tr>
                    <td style="height:12px; line-height:12px; font-size:0;">&nbsp;</td>
                </tr>

                {{-- RESUMEN DE RECEPCIÓN --}}
                <tr>
                    <td style="background-color:#ffffff; border:1px solid #eef0f2; border-radius:12px; padding:18px; font-family:Segoe UI, Arial, sans-serif;">

                        <p style="margin:0 0 12px 0; font-size:13px; font-weight:bold; color:#111827; text-align:center; letter-spacing:0.3px; text-transform:uppercase;">
                            Resumen de recepción
                        </p>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>

                                <td align="center" width="33%" style="padding:6px; border-right:1px solid #eef0f2;">
                                    <p style="margin:0 0 5px 0; font-size:10px; color:#6b7280; font-weight:bold; text-transform:uppercase;">
                                        Solicitado
                                    </p>

                                    <p style="margin:0; font-size:19px; color:#111827; font-weight:bold;">
                                        {{ number_format((float) data_get($data, 'Totals.Requested', 0), 0, ',', '.') }}
                                    </p>
                                </td>

                                <td align="center" width="33%" style="padding:6px; border-right:1px solid #eef0f2;">
                                    <p style="margin:0 0 5px 0; font-size:10px; color:#6b7280; font-weight:bold; text-transform:uppercase;">
                                        Recibido
                                    </p>

                                    <p style="margin:0; font-size:19px; color:#166534; font-weight:bold;">
                                        {{ number_format((float) data_get($data, 'Totals.Receiving', 0), 0, ',', '.') }}
                                    </p>
                                </td>

                                <td align="center" width="33%" style="padding:6px;">
                                    <p style="margin:0 0 5px 0; font-size:10px; color:#6b7280; font-weight:bold; text-transform:uppercase;">
                                        Pendiente
                                    </p>

                                    <p style="margin:0; font-size:19px; color:#b45309; font-weight:bold;">
                                        {{ number_format((float) data_get($data, 'Totals.Pending', 0), 0, ',', '.') }}
                                    </p>
                                </td>

                            </tr>
                        </table>

                    </td>
                </tr>

                <tr>
                    <td style="height:12px; line-height:12px; font-size:0;">&nbsp;</td>
                </tr>

                {{-- DETALLE DE PRODUCTOS --}}
                <tr>
                    <td style="background-color:#ffffff; border:1px solid #eef0f2; border-radius:12px; padding:18px; font-family:Segoe UI, Arial, sans-serif;">

                        <p style="margin:0 0 12px 0; font-size:13px; font-weight:bold; color:#111827; text-align:center; letter-spacing:0.3px; text-transform:uppercase;">
                            Detalle de productos
                        </p>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                            <thead>
                                <tr style="background-color:#f9fafb;">
                                    <th align="left" style="padding:8px 5px; font-size:9px; color:#6b7280; text-transform:uppercase; border-bottom:1px solid #eef0f2;">
                                        Producto
                                    </th>

                                    <th align="center" style="padding:8px 3px; font-size:9px; color:#6b7280; text-transform:uppercase; border-bottom:1px solid #eef0f2;">
                                        Talla
                                    </th>

                                    <th align="center" style="padding:8px 3px; font-size:9px; color:#6b7280; text-transform:uppercase; border-bottom:1px solid #eef0f2;">
                                        Sol.
                                    </th>

                                    <th align="center" style="padding:8px 3px; font-size:9px; color:#6b7280; text-transform:uppercase; border-bottom:1px solid #eef0f2;">
                                        Rec.
                                    </th>

                                    <th align="center" style="padding:8px 3px; font-size:9px; color:#6b7280; text-transform:uppercase; border-bottom:1px solid #eef0f2;">
                                        Pend.
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse(data_get($data, 'Items', []) as $item)
                                    <tr>

                                        <td style="padding:8px 5px; border-bottom:1px solid #f3f4f6; vertical-align:top;">
                                            <p style="margin:0 0 2px 0; font-size:11px; color:#111827; font-weight:bold;">
                                                {{ $item['Reference'] ?? $item['ProductCode'] ?? '' }}
                                            </p>

                                            <p style="margin:0; font-size:10px; color:#6b7280; line-height:1.3;">
                                                {{ $item['Description'] ?? $item['LongDescription'] ?? '' }}
                                            </p>

                                            @if(!empty($item['Color']))
                                                <p style="margin:2px 0 0 0; font-size:9px; color:#9ca3af;">
                                                    Color: {{ $item['Color'] }}
                                                </p>
                                            @endif
                                        </td>

                                        <td align="center" style="padding:8px 3px; font-size:11px; color:#374151; border-bottom:1px solid #f3f4f6; vertical-align:top;">
                                            {{ $item['Size'] ?? '-' }}
                                        </td>

                                        <td align="center" style="padding:8px 3px; font-size:11px; color:#374151; border-bottom:1px solid #f3f4f6; vertical-align:top;">
                                            {{ number_format((float) ($item['Quantity'] ?? 0), 0, ',', '.') }}
                                        </td>

                                        <td align="center" style="padding:8px 3px; font-size:11px; color:#166534; font-weight:bold; border-bottom:1px solid #f3f4f6; vertical-align:top;">
                                            {{ number_format((float) ($item['ReceivingQuantity'] ?? 0), 0, ',', '.') }}
                                        </td>

                                        @php
                                            $pending = (float) ($item['PendingQuantity'] ?? 0);
                                            $pendingColor = $pending < 0 ? '#b45309' : ($pending > 0 ? '#1d4ed8' : '#166534');
                                            $pendingText = $pending > 0 ? '+' . number_format($pending, 0, ',', '.') : number_format($pending, 0, ',', '.');
                                        @endphp
                                        <td align="center" style="padding:8px 3px; font-size:11px; color:{{ $pendingColor }}; font-weight:bold; border-bottom:1px solid #f3f4f6; vertical-align:top;">
                                            {{ $pendingText }}
                                        </td>

                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" align="center" style="padding:15px 5px; font-size:11px; color:#6b7280;">
                                            No hay productos registrados en la recepción.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                    </td>
                </tr>

                <tr>
                    <td style="height:12px; line-height:12px; font-size:0;">&nbsp;</td>
                </tr>

                {{-- CHECKLIST --}}
                <tr>
                    <td style="background-color:#ffffff; border:1px solid #eef0f2; border-radius:12px; padding:14px; font-family:Segoe UI, Arial, sans-serif;">

                        <p style="margin:0 0 10px 0; font-size:13px; font-weight:bold; color:#111827; text-align:center; letter-spacing:0.3px; text-transform:uppercase;">
                            Checklist de recepción
                        </p>

                        @php
                            $checklist = data_get($data, 'Checklist', []);

                            $checklistSections = [
                                [
                                    'title' => '1. Frente al transportador',
                                    'items' => [
                                        'cajas_master' => 'Cajas master',
                                        'sellos' => 'Sellos',
                                        'estado_cajas' => 'Estado de las cajas',
                                        'documentos' => 'Documentos',
                                    ],
                                ],
                                [
                                    'title' => '2. Calidad y detalle',
                                    'items' => [
                                        'empaque' => 'Empaque',
                                        'stickers' => 'Stickers',
                                        'producto' => 'Producto',
                                        'exactitud' => 'Exactitud de la recepción',
                                        'variacion_diseno' => 'Variación de diseño',
                                    ],
                                ],
                                [
                                    'title' => '3. Cierre administrativo',
                                    'items' => [
                                        'firma' => 'Firma',
                                        'reporte_whatsapp' => 'Reporte por WhatsApp',
                                        'respaldo_whatsapp' => 'Respaldo por WhatsApp',
                                        'alerta' => 'Alerta',
                                        'venta' => 'Venta',
                                    ],
                                ],
                            ];
                        @endphp

                        @foreach($checklistSections as $section)

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%; border-collapse:collapse; margin-top:7px;">
                                <tr>
                                    <td style="background-color:#fff3cd; color:#111827; padding:5px 8px; font-size:10px; font-weight:800; text-transform:uppercase; border:1px solid #e5e7eb;">
                                        {{ $section['title'] }}
                                    </td>
                                </tr>
                            </table>

                            @php
                                $sectionItems = array_keys($section['items']);
                                $sectionLabels = array_values($section['items']);
                                $sectionCount = count($sectionItems);
                            @endphp

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                                style="width:100%; border-collapse:collapse; border-left:1px solid #e5e7eb; border-right:1px solid #e5e7eb; border-bottom:1px solid #e5e7eb;">

                                @for($i = 0; $i < $sectionCount; $i += 2)

                                    <tr>

                                        <td width="50%" valign="middle"
                                            style="width:50%; padding:5px 7px; border-bottom:1px solid #f3f4f6; border-right:1px solid #f3f4f6; font-size:10px; color:#374151;">

                                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                                <tr>
                                                    <td style="font-size:10px; color:#374151;">
                                                        {{ $sectionLabels[$i] }}
                                                    </td>

                                                    <td align="right" style="white-space:nowrap; padding-left:5px; font-size:10px; font-weight:bold;">
                                                        @if(!empty($checklist[$sectionItems[$i]]))
                                                            <span style="color:#166534;">✓ Cumple</span>
                                                        @else
                                                            <span style="color:#b91c1c;">✕ No cumple</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            </table>

                                        </td>

                                        @if(isset($sectionItems[$i + 1]))

                                            <td width="50%" valign="middle"
                                                style="width:50%; padding:5px 7px; border-bottom:1px solid #f3f4f6; font-size:10px; color:#374151;">

                                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                                    <tr>
                                                        <td style="font-size:10px; color:#374151;">
                                                            {{ $sectionLabels[$i + 1] }}
                                                        </td>

                                                        <td align="right" style="white-space:nowrap; padding-left:5px; font-size:10px; font-weight:bold;">
                                                            @if(!empty($checklist[$sectionItems[$i + 1]]))
                                                                <span style="color:#166534;">✓ Cumple</span>
                                                            @else
                                                                <span style="color:#b91c1c;">✕ No cumple</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                </table>

                                            </td>

                                        @else

                                            <td width="50%" style="width:50%; padding:5px 7px; border-bottom:1px solid #f3f4f6;">
                                                &nbsp;
                                            </td>

                                        @endif

                                    </tr>

                                @endfor

                            </table>

                        @endforeach

                        {{-- REFERENCIAS --}}
                        @if(!empty(data_get($data, 'Checklist.referencias_recibidas')))

                            <p style="margin:10px 0 4px 0; font-size:10px; color:#111827; font-weight:bold;">
                                Referencias recibidas
                            </p>

                            <p style="margin:0; padding:7px 9px; background-color:#f9fafb; border:1px solid #f3f4f6; border-radius:6px; font-size:10px; color:#374151; line-height:1.4;">
                                {{ data_get($data, 'Checklist.referencias_recibidas') }}
                            </p>

                        @endif

                        {{-- OBSERVACIONES --}}
                        @if(!empty(data_get($data, 'Checklist.observaciones')))

                            <p style="margin:10px 0 4px 0; font-size:10px; color:#111827; font-weight:bold;">
                                Observaciones
                            </p>

                            <p style="margin:0; padding:7px 9px; background-color:#f9fafb; border:1px solid #f3f4f6; border-radius:6px; font-size:10px; color:#374151; line-height:1.4;">
                                {{ data_get($data, 'Checklist.observaciones') }}
                            </p>

                        @endif

                    </td>
                </tr>

                {{-- EVIDENCIAS --}}
                @if(!empty($imagenes))

                    <tr>
                        <td style="height:12px; line-height:12px; font-size:0;">&nbsp;</td>
                    </tr>

                    <tr>
                        <td style="background-color:#ffffff; border:1px solid #eef0f2; border-radius:12px; padding:16px 18px; font-family:Segoe UI, Arial, sans-serif;">

                            <p style="margin:0 0 12px 0; font-size:13px; font-weight:bold; color:#111827; text-align:center; letter-spacing:0.3px; text-transform:uppercase;">
                                Evidencias de recepción
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">

                                @foreach($imagenes as $index => $imagen)

                                    @if($index % 2 === 0)
                                        <tr>
                                    @endif

                                    <td width="50%" align="center" valign="top" style="padding:5px;">

                                        <a href="{{ $imagen }}" target="_blank" style="text-decoration:none;">

                                            <img src="{{ $imagen }}"
                                                alt="Evidencia de recepción {{ $index + 1 }}"
                                                style="display:block; width:100%; max-width:270px; height:170px; object-fit:cover; border:1px solid #eef0f2; border-radius:8px;">

                                        </a>

                                        <p style="margin:5px 0 0 0; font-size:10px; color:#9ca3af;">
                                            Evidencia {{ $index + 1 }}
                                        </p>

                                    </td>

                                    @if($index % 2 === 1 || $loop->last)
                                        </tr>
                                    @endif

                                @endforeach

                            </table>

                        </td>
                    </tr>

                @endif

                <tr>
                    <td style="height:12px; line-height:12px; font-size:0;">&nbsp;</td>
                </tr>

                {{-- CIERRE --}}
                <tr>
                    <td style="padding:12px 4px 4px 4px; font-family:Segoe UI, Arial, sans-serif;">
                        <p style="margin:0 0 4px 0; font-size:14px; color:#374151; line-height:1.6;">
                            Atentamente,
                        </p>
                        <p style="margin:16px 0 0 0; font-size:13px; color:#6b7280; line-height:1.5;">
                            <strong style="color:#374151;">{{ data_get($data, 'Checklist.responsable.nombre') }}</strong><br>
                            {{ data_get($data, 'Checklist.responsable.cargo') }}<br>
                            {{ data_get($data, 'Checklist.responsable.departamento') }}<br>
                            Revent Calzado SAS<br>
                            Celular: {{ data_get($data, 'Checklist.responsable.celular') }}
                        </p>
                    </td>
                </tr>

                <tr>
                    <td style="height:8px; line-height:8px; font-size:0;">&nbsp;</td>
                </tr>

                {{-- FOOTER --}}
                <tr>
                    <td align="center" style="padding:14px 4px 4px 4px; font-family:Segoe UI, Arial, sans-serif;">

                        <img src="https://revent.com.co/cdn/shop/files/Logo_Revent-Negro.png?v=1744326854&width=250"
                            alt="REVENT CALZADO S.A.S."
                            width="90"
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

</body>
</html>