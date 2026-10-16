<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nota de traslado {{ $document['Code'] }}</title>

    <style>
        {{-- Estilos originales de Siigo (ya limpiados en el controller) --}}
        {!! $siigoStyle !!}

        @page { margin: 20px; }

        .TableDocumentDetail thead {
            display: table-header-group;
        }

        .TableDocumentDetail tr {
            page-break-inside: avoid;
        }

        .images-section {
            margin-top: 25px;
        }

        .images-section .images-title {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 18px;
            font-weight: bold;
            color: #000;
            margin: 0 0 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #ccc;
            page-break-after: avoid;
        }

        .images-section .images-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .images-section .image-cell {
            width: 25%;
            padding: 8px;
            vertical-align: top;
            text-align: center;
            border: 1px solid #e5e7eb;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            color: #374151;
            page-break-inside: avoid;
        }

        .images-section .image-cell img {
            width: 230px;
        }

        .images-section .no-image {
            padding: 70px 0;
            background: #f3f4f6;
            color: #9ca3af;
        }

        .images-section .image-ref {
            margin-top: 6px;
            font-size: 15px;
            font-weight: bold;
            color: #111827;
        }

        .images-section .image-color {
            margin-top: 3px;
            font-size: 13px;
            color: #374151;
        }
    </style>
</head>
<body>

    {!! $siigoBody !!}

    <div class="images-section">
        <div class="images-title">Imágenes por referencia</div>

        <table class="images-table">
            @foreach ($groups->chunk(3) as $row)
                <tr>
                    @foreach ($row as $card)
                        <td class="image-cell">
                            @if (!empty($card['Image']))
                                <img src="{{ $card['Image'] }}" alt="{{ $card['Reference'] }}">
                            @else
                                <div class="no-image">Sin imagen</div>
                            @endif

                            <div class="image-ref">{{ $card['Reference'] }}</div>

                            @foreach ($card['Colors'] as $color)
                                <div class="image-color">{{ $color['Color'] }}</div>
                            @endforeach
                        </td>
                    @endforeach

                    @for ($i = $row->count(); $i < 3; $i++)
                        <td class="image-cell" style="border:none;"></td>
                    @endfor
                </tr>
            @endforeach
        </table>
    </div>

</body>
</html>
