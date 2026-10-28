@extends('layouts.email')

@section('title', 'Alerta de traslados directos')

@push('styles')
    <style>
        /* Tabla condensada: estilos en clases para no repetirlos en cada fila (evita que Gmail recorte el correo) */
        .tt { width: 100%; border-collapse: collapse; }
        .tt th { padding: 8px 6px; background-color: #fef2f2; border-bottom: 1px solid #fecaca; font-size: 10px; font-weight: bold; color: #b91c1c; text-transform: uppercase; text-align: left; font-family: Segoe UI, Arial, sans-serif; }
        .tt td { padding: 7px 6px; border-bottom: 1px solid #fee2e2; font-size: 12px; color: #374151; vertical-align: top; font-family: Segoe UI, Arial, sans-serif; }
        .tt .u { text-align: right; white-space: nowrap; font-weight: bold; color: #7f1d1d; }
        .tt .s { display: block; font-size: 11px; color: #9ca3af; font-weight: normal; }
        .lk { font-weight: 600; color: #1d4ed8; text-decoration: none; white-space: nowrap; }
    </style>
@endpush

@section('content')
    @php
        $fmt = fn ($n) => rtrim(rtrim(number_format($n, 2, ',', '.'), '0'), ',');
        $units = collect($transfers)->sum('units');
    @endphp

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f3f4f6;">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:720px; width:100%;">

                    <tr>
                        <td style="background-color:#fef2f2; border:1px solid #fecaca; border-radius:12px; padding:18px 20px; font-family:Segoe UI, Arial, sans-serif;">
                            <p style="margin:0 0 4px 0; font-size:15px; font-weight:bold; color:#7f1d1d;">
                                {{ count($transfers) }} traslado(s) directo(s) desde {{ $warehouse }}
                            </p>
                            <p style="margin:0; font-size:13px; color:#991b1b; line-height:1.4;">
                                Día {{ $date }} · {{ $fmt($units) }} unidades que salieron a una bodega que no es de tránsito.
                            </p>
                        </td>
                    </tr>

                    <tr><td height="12"></td></tr>

                    <tr>
                        <td style="background-color:#ffffff; border:1px solid #e5e7eb; border-radius:12px; padding:8px 12px;">
                            <table class="tt" role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <thead>
                                    <tr>
                                        <th>Nota de traslado</th>
                                        <th>Bodega de salida</th>
                                        <th>Bodega de entrada</th>
                                        <th>Producto</th>
                                        <th style="text-align:right;">Unid.</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($transfers as $transfer)
                                        @foreach ($transfer['lines'] as $line)
                                            <tr>
                                                <td><a href="{{ $transfer['url'] }}" class="lk" target="_blank" rel="noopener">{{ $transfer['voucher'] }}</a></td>
                                                <td>{{ $transfer['origin'] }}</td>
                                                <td>{{ $line['destination'] }}</td>
                                                <td>{{ $line['code'] }}<span class="s">{{ $line['name'] }}</span></td>
                                                <td class="u">{{ $fmt($line['quantity']) }}</td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding:16px 0 0 0; font-size:11px; color:#9ca3af; font-family:Segoe UI, Arial, sans-serif;">
                            Revisión automática diaria de traslados directos entre bodegas · REVENT CALZADO S.A.S.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
@endsection
