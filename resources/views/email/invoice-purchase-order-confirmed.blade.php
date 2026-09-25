{{-- resources/views/email/invoice-purchase-order-confirmed.blade.php --}}

<!DOCTYPE html>

<html lang="es"
      xmlns="http://www.w3.org/1999/xhtml"
      xmlns:v="urn:schemas-microsoft-com:vml"
      xmlns:o="urn:schemas-microsoft-com:office:office">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title>
        Recepción de orden de compra {{ data_get($data, 'Order.DocName', '') }}
    </title>

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

<body style="
    margin:0;
    padding:0;
    background-color:#f3f4f6;
    -webkit-text-size-adjust:100%;
    -ms-text-size-adjust:100%;
">

<table role="presentation"
       width="100%"
       cellpadding="0"
       cellspacing="0"
       border="0"
       style="background-color:#f3f4f6;">

    <tr>

        <td align="center" style="padding:24px 12px;">

            <table role="presentation"
                   width="100%"
                   cellpadding="0"
                   cellspacing="0"
                   border="0"
                   style="max-width:640px; width:100%;">

                {{-- ========================================================= --}}
                {{-- LOGO --}}
                {{-- ========================================================= --}}

                <tr>

                    <td align="center" style="padding:4px 4px 20px 4px;">

                        <img
                            src="https://revent.com.co/cdn/shop/files/Logo_Revent-Negro.png?v=1744326854&width=250"
                            alt="REVENT CALZADO S.A.S."
                            width="150"
                            style="
                                display:block;
                                width:150px;
                                max-width:150px;
                                height:auto;
                            "
                        >

                    </td>

                </tr>


                {{-- ========================================================= --}}
                {{-- BANNER --}}
                {{-- ========================================================= --}}

                <tr>

                    <td style="
                        background-color:#111827;
                        border-radius:12px;
                        padding:26px 24px;
                        font-family:Segoe UI, Arial, sans-serif;
                    ">

                        <p style="
                            margin:0 0 4px 0;
                            font-size:11px;
                            font-weight:bold;
                            color:#9ca3af;
                            letter-spacing:0.5px;
                            text-transform:uppercase;
                        ">
                            REVENT CALZADO S.A.S.
                        </p>

                        <p style="
                            margin:0;
                            font-size:19px;
                            font-weight:bold;
                            color:#ffffff;
                            line-height:1.3;
                        ">
                            Recepción de orden de compra
                            {{ data_get($data, 'Order.DocName', '') }}
                        </p>

                    </td>

                </tr>


                <tr>
                    <td style="
                        height:16px;
                        line-height:16px;
                        font-size:0;
                    ">
                        &nbsp;
                    </td>
                </tr>


                {{-- ========================================================= --}}
                {{-- MENSAJE PRINCIPAL --}}
                {{-- ========================================================= --}}

                <tr>

                    <td style="
                        background-color:#ffffff;
                        border:1px solid #eef0f2;
                        border-radius:12px;
                        padding:24px;
                        font-family:Segoe UI, Arial, sans-serif;
                    ">

                        <p style="
                            margin:0 0 16px 0;
                            font-size:14px;
                            color:#374151;
                            line-height:1.6;
                        ">

                            Estimado

                            <strong>
                                {{ data_get($data, 'Order.FullName', 'señores') }}

                                @if(data_get($data, 'Order.CompanyName'))
                                    ({{ data_get($data, 'Order.CompanyName') }})
                                @endif

                            </strong>,

                        </p>


                        <p style="
                            margin:0 0 16px 0;
                            font-size:14px;
                            color:#374151;
                            line-height:1.6;
                        ">

                            Reciba un cordial saludo de parte de
                            <strong>REVENT CALZADO S.A.S.</strong>

                        </p>


                        <p style="
                            margin:0 0 16px 0;
                            font-size:14px;
                            color:#374151;
                            line-height:1.6;
                        ">

                            Se informa que se realizó la recepción de la orden de compra

                            @if(!empty($orderLink))

                                <a href="{{ $orderLink }}"
                                   target="_blank"
                                   style="
                                        color:#1d4ed8;
                                        font-weight:bold;
                                        text-decoration:none;
                                   ">

                                    {{ data_get($data, 'Order.DocName', '') }}

                                </a>

                            @else

                                <strong>
                                    {{ data_get($data, 'Order.DocName', '') }}
                                </strong>

                            @endif

                            .

                        </p>


                        <p style="
                            margin:0;
                            font-size:14px;
                            color:#374151;
                            line-height:1.6;
                        ">

                            A continuación, encontrará el detalle de las cantidades
                            solicitadas, recibidas y pendientes, junto con el checklist
                            correspondiente a la recepción.

                        </p>

                    </td>

                </tr>


                <tr>
                    <td style="
                        height:16px;
                        line-height:16px;
                        font-size:0;
                    ">
                        &nbsp;
                    </td>
                </tr>


                <tr>
                    <td style="
                        height:16px;
                        line-height:16px;
                        font-size:0;
                    ">
                        &nbsp;
                    </td>
                </tr>


                {{-- ========================================================= --}}
                {{-- RESUMEN DE RECEPCIÓN --}}
                {{-- ========================================================= --}}

                <tr>

                    <td style="
                        background-color:#ffffff;
                        border:1px solid #eef0f2;
                        border-radius:12px;
                        padding:20px;
                        font-family:Segoe UI, Arial, sans-serif;
                    ">

                        <p style="
                            margin:0 0 16px 0;
                            font-size:13px;
                            font-weight:bold;
                            color:#111827;
                            text-align:center;
                            letter-spacing:0.3px;
                            text-transform:uppercase;
                        ">
                            Resumen de recepción
                        </p>


                        <table role="presentation"
                               width="100%"
                               cellpadding="0"
                               cellspacing="0"
                               border="0">

                            <tr>

                                {{-- SOLICITADO --}}

                                <td align="center"
                                    width="33%"
                                    style="
                                        padding:8px;
                                        border-right:1px solid #eef0f2;
                                    ">

                                    <p style="
                                        margin:0 0 6px 0;
                                        font-size:11px;
                                        color:#6b7280;
                                        font-weight:bold;
                                        text-transform:uppercase;
                                    ">
                                        Solicitado
                                    </p>

                                    <p style="
                                        margin:0;
                                        font-size:20px;
                                        color:#111827;
                                        font-weight:bold;
                                    ">
                                        {{ number_format(
                                            (float) data_get($data, 'Totals.Requested', 0),
                                            0,
                                            ',',
                                            '.'
                                        ) }}
                                    </p>

                                </td>


                                {{-- RECIBIDO --}}

                                <td align="center"
                                    width="33%"
                                    style="
                                        padding:8px;
                                        border-right:1px solid #eef0f2;
                                    ">

                                    <p style="
                                        margin:0 0 6px 0;
                                        font-size:11px;
                                        color:#6b7280;
                                        font-weight:bold;
                                        text-transform:uppercase;
                                    ">
                                        Recibido
                                    </p>

                                    <p style="
                                        margin:0;
                                        font-size:20px;
                                        color:#166534;
                                        font-weight:bold;
                                    ">
                                        {{ number_format(
                                            (float) data_get($data, 'Totals.Receiving', 0),
                                            0,
                                            ',',
                                            '.'
                                        ) }}
                                    </p>

                                </td>


                                {{-- PENDIENTE --}}

                                <td align="center"
                                    width="33%"
                                    style="padding:8px;">

                                    <p style="
                                        margin:0 0 6px 0;
                                        font-size:11px;
                                        color:#6b7280;
                                        font-weight:bold;
                                        text-transform:uppercase;
                                    ">
                                        Pendiente
                                    </p>

                                    <p style="
                                        margin:0;
                                        font-size:20px;
                                        color:#b45309;
                                        font-weight:bold;
                                    ">
                                        {{ number_format(
                                            (float) data_get($data, 'Totals.Pending', 0),
                                            0,
                                            ',',
                                            '.'
                                        ) }}
                                    </p>

                                </td>

                            </tr>

                        </table>

                    </td>

                </tr>


                <tr>
                    <td style="
                        height:16px;
                        line-height:16px;
                        font-size:0;
                    ">
                        &nbsp;
                    </td>
                </tr>


                {{-- ========================================================= --}}
                {{-- DETALLE DE PRODUCTOS --}}
                {{-- ========================================================= --}}

                <tr>

                    <td style="
                        background-color:#ffffff;
                        border:1px solid #eef0f2;
                        border-radius:12px;
                        padding:20px;
                        font-family:Segoe UI, Arial, sans-serif;
                    ">

                        <p style="
                            margin:0 0 16px 0;
                            font-size:13px;
                            font-weight:bold;
                            color:#111827;
                            text-align:center;
                            letter-spacing:0.3px;
                            text-transform:uppercase;
                        ">
                            Detalle de productos
                        </p>


                        <table role="presentation"
                               width="100%"
                               cellpadding="0"
                               cellspacing="0"
                               border="0"
                               style="
                                    border-collapse:collapse;
                                    width:100%;
                               ">

                            <thead>

                                <tr style="background-color:#f9fafb;">

                                    <th align="left"
                                        style="
                                            padding:9px 6px;
                                            font-size:10px;
                                            color:#6b7280;
                                            text-transform:uppercase;
                                            border-bottom:1px solid #eef0f2;
                                        ">
                                        Producto
                                    </th>

                                    <th align="center"
                                        style="
                                            padding:9px 4px;
                                            font-size:10px;
                                            color:#6b7280;
                                            text-transform:uppercase;
                                            border-bottom:1px solid #eef0f2;
                                        ">
                                        Talla
                                    </th>

                                    <th align="center"
                                        style="
                                            padding:9px 4px;
                                            font-size:10px;
                                            color:#6b7280;
                                            text-transform:uppercase;
                                            border-bottom:1px solid #eef0f2;
                                        ">
                                        Sol.
                                    </th>

                                    <th align="center"
                                        style="
                                            padding:9px 4px;
                                            font-size:10px;
                                            color:#6b7280;
                                            text-transform:uppercase;
                                            border-bottom:1px solid #eef0f2;
                                        ">
                                        Rec.
                                    </th>

                                    <th align="center"
                                        style="
                                            padding:9px 4px;
                                            font-size:10px;
                                            color:#6b7280;
                                            text-transform:uppercase;
                                            border-bottom:1px solid #eef0f2;
                                        ">
                                        Pend.
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                @forelse(data_get($data, 'Items', []) as $item)

                                    <tr>

                                        <td style="
                                            padding:10px 6px;
                                            border-bottom:1px solid #f3f4f6;
                                            vertical-align:top;
                                        ">

                                            <p style="
                                                margin:0 0 3px 0;
                                                font-size:12px;
                                                color:#111827;
                                                font-weight:bold;
                                            ">
                                                {{ $item['Reference'] ?? $item['ProductCode'] ?? '' }}
                                            </p>


                                            <p style="
                                                margin:0;
                                                font-size:11px;
                                                color:#6b7280;
                                                line-height:1.4;
                                            ">
                                                {{ $item['Description'] ?? $item['LongDescription'] ?? '' }}
                                            </p>


                                            @if(!empty($item['Color']))

                                                <p style="
                                                    margin:3px 0 0 0;
                                                    font-size:10px;
                                                    color:#9ca3af;
                                                ">
                                                    Color: {{ $item['Color'] }}
                                                </p>

                                            @endif

                                        </td>


                                        <td align="center"
                                            style="
                                                padding:10px 4px;
                                                font-size:12px;
                                                color:#374151;
                                                border-bottom:1px solid #f3f4f6;
                                                vertical-align:top;
                                            ">
                                            {{ $item['Size'] ?? '-' }}
                                        </td>


                                        <td align="center"
                                            style="
                                                padding:10px 4px;
                                                font-size:12px;
                                                color:#374151;
                                                border-bottom:1px solid #f3f4f6;
                                                vertical-align:top;
                                            ">
                                            {{ number_format(
                                                (float) ($item['Quantity'] ?? 0),
                                                0,
                                                ',',
                                                '.'
                                            ) }}
                                        </td>


                                        <td align="center"
                                            style="
                                                padding:10px 4px;
                                                font-size:12px;
                                                color:#166534;
                                                font-weight:bold;
                                                border-bottom:1px solid #f3f4f6;
                                                vertical-align:top;
                                            ">
                                            {{ number_format(
                                                (float) ($item['ReceivingQuantity'] ?? 0),
                                                0,
                                                ',',
                                                '.'
                                            ) }}
                                        </td>


                                        <td align="center"
                                            style="
                                                padding:10px 4px;
                                                font-size:12px;
                                                color:#b45309;
                                                font-weight:bold;
                                                border-bottom:1px solid #f3f4f6;
                                                vertical-align:top;
                                            ">
                                            {{ number_format(
                                                (float) ($item['PendingQuantity'] ?? 0),
                                                0,
                                                ',',
                                                '.'
                                            ) }}
                                        </td>

                                    </tr>

                                @empty

                                    <tr>

                                        <td colspan="5"
                                            align="center"
                                            style="
                                                padding:18px 6px;
                                                font-size:12px;
                                                color:#6b7280;
                                            ">
                                            No hay productos registrados en la recepción.
                                        </td>

                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </td>

                </tr>


                <tr>
                    <td style="
                        height:16px;
                        line-height:16px;
                        font-size:0;
                    ">
                        &nbsp;
                    </td>
                </tr>


                {{-- ========================================================= --}}
                {{-- CHECKLIST --}}
                {{-- ========================================================= --}}

                <tr>

                    <td style="
                        background-color:#ffffff;
                        border:1px solid #eef0f2;
                        border-radius:12px;
                        padding:14px;
                        font-family:Segoe UI, Arial, sans-serif;
                    ">

                        <p style="
                            margin:0 0 10px 0;
                            font-size:13px;
                            font-weight:bold;
                            color:#111827;
                            text-align:center;
                            letter-spacing:0.3px;
                            text-transform:uppercase;
                        ">
                            Checklist de recepción
                        </p>


                        @php

                            $checklist = data_get($data, 'Checklist', []);

                            /*
                             * Se conserva la organización visual
                             * del formulario:
                             *
                             * 1. Frente al transportador
                             * 2. Calidad y detalle
                             * 3. Cierre administrativo
                             */

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

                            {{-- ================================================= --}}
                            {{-- ENCABEZADO DE SECCIÓN --}}
                            {{-- ================================================= --}}

                            <table role="presentation"
                                   width="100%"
                                   cellpadding="0"
                                   cellspacing="0"
                                   border="0"
                                   style="
                                        width:100%;
                                        border-collapse:collapse;
                                        margin-top:7px;
                                   ">

                                <tr>

                                    <td style="
                                        background-color:#fff3cd;
                                        color:#111827;
                                        padding:5px 8px;
                                        font-size:10px;
                                        font-weight:800;
                                        text-transform:uppercase;
                                        border:1px solid #e5e7eb;
                                    ">
                                        {{ $section['title'] }}
                                    </td>

                                </tr>

                            </table>


                            {{-- ================================================= --}}
                            {{-- ELEMENTOS DE LA SECCIÓN --}}
                            {{-- ================================================= --}}

                            @php

                                $sectionItems = array_keys($section['items']);
                                $sectionLabels = array_values($section['items']);
                                $sectionCount = count($sectionItems);

                            @endphp


                            <table role="presentation"
                                   width="100%"
                                   cellpadding="0"
                                   cellspacing="0"
                                   border="0"
                                   style="
                                        width:100%;
                                        border-collapse:collapse;
                                        border-left:1px solid #e5e7eb;
                                        border-right:1px solid #e5e7eb;
                                        border-bottom:1px solid #e5e7eb;
                                   ">

                                @for($i = 0; $i < $sectionCount; $i += 2)

                                    <tr>

                                        {{-- COLUMNA IZQUIERDA --}}

                                        <td width="50%"
                                            valign="middle"
                                            style="
                                                width:50%;
                                                padding:5px 7px;
                                                border-bottom:1px solid #f3f4f6;
                                                border-right:1px solid #f3f4f6;
                                                font-size:10px;
                                                color:#374151;
                                            ">

                                            <table role="presentation"
                                                   width="100%"
                                                   cellpadding="0"
                                                   cellspacing="0"
                                                   border="0">

                                                <tr>

                                                    <td style="
                                                        font-size:10px;
                                                        color:#374151;
                                                    ">

                                                        {{ $sectionLabels[$i] }}

                                                    </td>

                                                    <td align="right"
                                                        style="
                                                            white-space:nowrap;
                                                            padding-left:5px;
                                                            font-size:10px;
                                                            font-weight:bold;
                                                        ">

                                                        @if(!empty($checklist[$sectionItems[$i]]))

                                                            <span style="color:#166534;">
                                                                ✓ Cumple
                                                            </span>

                                                        @else

                                                            <span style="color:#b91c1c;">
                                                                ✕ No cumple
                                                            </span>

                                                        @endif

                                                    </td>

                                                </tr>

                                            </table>

                                        </td>


                                        {{-- COLUMNA DERECHA --}}

                                        @if(isset($sectionItems[$i + 1]))

                                            <td width="50%"
                                                valign="middle"
                                                style="
                                                    width:50%;
                                                    padding:5px 7px;
                                                    border-bottom:1px solid #f3f4f6;
                                                    font-size:10px;
                                                    color:#374151;
                                                ">

                                                <table role="presentation"
                                                       width="100%"
                                                       cellpadding="0"
                                                       cellspacing="0"
                                                       border="0">

                                                    <tr>

                                                        <td style="
                                                            font-size:10px;
                                                            color:#374151;
                                                        ">

                                                            {{ $sectionLabels[$i + 1] }}

                                                        </td>

                                                        <td align="right"
                                                            style="
                                                                white-space:nowrap;
                                                                padding-left:5px;
                                                                font-size:10px;
                                                                font-weight:bold;
                                                            ">

                                                            @if(!empty($checklist[$sectionItems[$i + 1]]))

                                                                <span style="color:#166534;">
                                                                    ✓ Cumple
                                                                </span>

                                                            @else

                                                                <span style="color:#b91c1c;">
                                                                    ✕ No cumple
                                                                </span>

                                                            @endif

                                                        </td>

                                                    </tr>

                                                </table>

                                            </td>

                                        @else

                                            <td width="50%"
                                                style="
                                                    width:50%;
                                                    padding:5px 7px;
                                                    border-bottom:1px solid #f3f4f6;
                                                ">
                                                &nbsp;
                                            </td>

                                        @endif

                                    </tr>

                                @endfor

                            </table>

                        @endforeach


                        {{-- ================================================= --}}
                        {{-- REFERENCIAS RECIBIDAS --}}
                        {{-- ================================================= --}}

                        @if(!empty(data_get($data, 'Checklist.referencias_recibidas')))

                            <p style="
                                margin:10px 0 4px 0;
                                font-size:10px;
                                color:#111827;
                                font-weight:bold;
                            ">
                                Referencias recibidas
                            </p>

                            <p style="
                                margin:0;
                                padding:7px 9px;
                                background-color:#f9fafb;
                                border:1px solid #f3f4f6;
                                border-radius:6px;
                                font-size:10px;
                                color:#374151;
                                line-height:1.4;
                            ">
                                {{ data_get($data, 'Checklist.referencias_recibidas') }}
                            </p>

                        @endif


                        {{-- ================================================= --}}
                        {{-- OBSERVACIONES --}}
                        {{-- ================================================= --}}

                        @if(!empty(data_get($data, 'Checklist.observaciones')))

                            <p style="
                                margin:10px 0 4px 0;
                                font-size:10px;
                                color:#111827;
                                font-weight:bold;
                            ">
                                Observaciones
                            </p>

                            <p style="
                                margin:0;
                                padding:7px 9px;
                                background-color:#f9fafb;
                                border:1px solid #f3f4f6;
                                border-radius:6px;
                                font-size:10px;
                                color:#374151;
                                line-height:1.4;
                            ">
                                {{ data_get($data, 'Checklist.observaciones') }}
                            </p>

                        @endif

                    </td>

                </tr>


                <tr>

                    <td style="
                        height:16px;
                        line-height:16px;
                        font-size:0;
                    ">
                        &nbsp;
                    </td>

                </tr>


                {{-- ========================================================= --}}
                {{-- BOTÓN SIIGO --}}
                {{-- ========================================================= --}}

                @if(!empty($orderLink))

                    <tr>

                        <td align="center"
                            style="
                                padding:4px 4px 16px 4px;
                                font-family:Segoe UI, Arial, sans-serif;
                            ">

                            <table role="presentation"
                                   cellpadding="0"
                                   cellspacing="0"
                                   border="0">

                                <tr>

                                    <td align="center"
                                        style="
                                            border-radius:8px;
                                            background-color:#16a34a;
                                        ">

                                        <a href="{{ $orderLink }}"
                                           target="_blank"
                                           style="
                                                display:inline-block;
                                                padding:12px 22px;
                                                font-family:Segoe UI, Arial, sans-serif;
                                                font-size:13px;
                                                font-weight:bold;
                                                color:#ffffff;
                                                text-decoration:none;
                                                border-radius:8px;
                                           ">
                                            Ver orden en Siigo
                                        </a>

                                    </td>

                                </tr>

                            </table>

                        </td>

                    </tr>

                @endif


                {{-- ========================================================= --}}
                {{-- OBSERVACIONES DE LA ORDEN --}}
                {{-- ========================================================= --}}

                @if(data_get($data, 'Order.Observations'))

                    <tr>

                        <td style="
                            background-color:#ffffff;
                            border:1px solid #eef0f2;
                            border-radius:12px;
                            padding:16px 20px;
                            font-family:Segoe UI, Arial, sans-serif;
                        ">

                            <p style="
                                margin:0 0 8px 0;
                                font-size:12px;
                                font-weight:bold;
                                color:#111827;
                                text-align:center;
                            ">
                                Observaciones de la orden
                            </p>

                            <p style="
                                margin:0;
                                font-size:12px;
                                color:#374151;
                                line-height:1.6;
                                text-align:center;
                            ">
                                {{ data_get($data, 'Order.Observations') }}
                            </p>

                        </td>

                    </tr>


                    <tr>

                        <td style="
                            height:16px;
                            line-height:16px;
                            font-size:0;
                        ">
                            &nbsp;
                        </td>

                    </tr>

                @endif


                {{-- ========================================================= --}}
                {{-- FIRMA --}}
                {{-- ========================================================= --}}

                <tr>

                    <td style="
                        padding:12px 4px 4px 4px;
                        font-family:Segoe UI, Arial, sans-serif;
                    ">

                        <p style="
                            margin:0 0 4px 0;
                            font-size:14px;
                            color:#374151;
                            line-height:1.6;
                        ">
                            Atentamente,
                        </p>

                        <p style="
                            margin:16px 0 0 0;
                            font-size:13px;
                            color:#6b7280;
                            line-height:1.5;
                        ">

                            <strong style="color:#374151;">
                                Ninoska Fontalvo
                            </strong>

                            <br>

                            Auxiliar administrativo

                            <br>

                            Departamento de Cartera

                            <br>

                            Revent Calzado SAS

                            <br>

                            Celular: 3222792893

                        </p>

                    </td>

                </tr>


                <tr>

                    <td style="
                        height:8px;
                        line-height:8px;
                        font-size:0;
                    ">
                        &nbsp;
                    </td>

                </tr>


                {{-- ========================================================= --}}
                {{-- FOOTER --}}
                {{-- ========================================================= --}}

                <tr>

                    <td align="center"
                        style="
                            padding:16px 4px 4px 4px;
                            font-family:Segoe UI, Arial, sans-serif;
                        ">

                        <img
                            src="https://revent.com.co/cdn/shop/files/Logo_Revent-Negro.png?v=1744326854&width=250"
                            alt="REVENT CALZADO S.A.S."
                            width="90"
                            style="
                                display:block;
                                width:90px;
                                max-width:90px;
                                height:auto;
                                opacity:0.6;
                                margin:0 auto 6px auto;
                            "
                        >

                        <p style="
                            margin:0;
                            font-size:11px;
                            color:#9ca3af;
                        ">
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
