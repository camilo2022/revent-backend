<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, Helvetica, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 760px; margin: 20px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,0.1); }
        .header { padding: 20px; color: #fff; font-size: 18px; font-weight: bold; background: #2e7d32; }
        .header.warn { background: #ef6c00; }
        .content { padding: 20px; color: #333; font-size: 14px; line-height: 1.5; }
        .cards { display: flex; gap: 12px; margin: 16px 0; flex-wrap: wrap; }
        .card { flex: 1; min-width: 150px; background: #f8f9fa; border-radius: 6px; padding: 8px; text-align: center; border: 1px solid #e0e0e0; }
        .card .value { font-size: 22px; font-weight: bold; }
        .card .label { font-size: 11px; color: #777; text-transform: uppercase; }
        .card.green .value { color: #2e7d32; }
        .card.red .value { color: #c62828; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 12px; }
        th, td { padding: 6px 8px; border: 1px solid #e0e0e0; text-align: left; vertical-align: top; }
        th { background: #f0f0f0; }
        td.num { text-align: right; white-space: nowrap; }
        h3 { margin: 24px 0 8px; font-size: 15px; }
        .err { color: #c62828; }
        .note { background: #f8f9fa; border: 1px solid #e0e0e0; border-radius: 6px; padding: 10px 12px; font-size: 13px; }
        .footer { padding: 15px 20px; font-size: 12px; color: #999; border-top: 1px solid #eee; }
    </style>
</head>
<body>
@php
    $t = $report['totales'];
    $n = fn ($v) => number_format($v, 0, ',', '.');
    $limit = 120;
    $shown = array_slice($report['lineas'], 0, $limit);
@endphp
<div class="container">
    <div class="header {{ $t['con_error'] ? 'warn' : '' }}">
        {{ $t['con_error'] ? '⚠️ Bodega WEB sincronizada con Effi, con códigos pendientes' : '✔️ Bodega WEB sincronizada con Effi' }}
    </div>

    <div class="content">
        @if ($report['manual'])
            <p>
                Terminó la sincronización de la bodega <strong>WEB</strong> de Siigo con Effi ({{ $report['bodega_effi'] }})
                que se lanzó desde la pantalla de sincronización, con las decisiones que se tomaron allí. Finalizó el {{ $report['fecha'] }}.
            </p>
            @foreach ($report['documentos'] as $doc)
                <p><strong>{{ $doc['documento'] }}</strong> — {{ $doc['sentido'] }}</p>
            @endforeach
        @else
            <p>
                Se hizo la sincronización automática de la bodega <strong>WEB</strong> de Siigo con Effi ({{ $report['bodega_effi'] }})
                el {{ $report['fecha'] }}, porque se hizo movimiento en la bodega WEB con:
            </p>
            <ul>
                @foreach ($report['documentos'] as $doc)
                    <li><strong>{{ $doc['documento'] }}</strong> — {{ $doc['sentido'] }}</li>
                @endforeach
            </ul>
            <p>No hubo inconsistencias que decidir, así que Effi quedó igual que Siigo sin intervención de nadie.</p>
        @endif

        <div class="cards">
            <div class="card green"><div class="value">+{{ $n($t['entradas']) }}</div><div class="label">Unidades que ingresaron<br>({{ $t['codigos_con_entrada'] }} referencias)</div></div>
            <div class="card red"><div class="value">−{{ $n($t['salidas']) }}</div><div class="label">Unidades que salieron<br>({{ $t['codigos_con_salida'] }} referencias)</div></div>
            <div class="card"><div class="value">{{ $n($t['unidades_effi_despues']) }}</div><div class="label">Unidades en Effi ahora<br>(antes {{ $n($t['unidades_effi_antes']) }}; Siigo {{ $n($t['unidades_siigo']) }})</div></div>
        </div>

        <ul>
            <li>Referencias de Effi cambiadas al código de Siigo: <strong>{{ $t['referencias_cambiadas'] }}</strong></li>
            <li>Nombres de Effi cambiados a la descripción exacta de Siigo: <strong>{{ $t['descripciones_cambiadas'] ?? 0 }}</strong></li>
            <li>Artículos creados en Effi: <strong>{{ $t['articulos_creados'] }}</strong></li>
            <li>Artículos a los que se les cambió el control de stock a «por bodega»: <strong>{{ $t['controles_cambiados'] }}</strong></li>
        </ul>

        @if ($t['con_error'])
            <h3 class="err">No se pudieron sincronizar {{ $t['con_error'] }} código(s)</h3>
            <p>Siguen con sus unidades anteriores en Effi. Revísalos en la pantalla de sincronización o desde Effi:</p>
            <table>
                <tr><th>Código</th><th>Descripción</th><th>Por qué falló</th></tr>
                @foreach (collect($report['lineas'])->whereNotNull('error') as $l)
                    <tr><td>{{ $l['codigo'] }}</td><td>{{ $l['descripcion'] }}</td><td class="err">{{ $l['error'] }}</td></tr>
                @endforeach
            </table>
        @endif

        <h3>Detalle por referencia</h3>
        <table>
            <tr><th>Código</th><th>Descripción</th><th>Antes en Effi</th><th>Ahora en Effi</th><th>Qué se hizo</th></tr>
            @foreach ($shown as $l)
                <tr>
                    <td>{{ $l['codigo'] }}</td>
                    <td>{{ $l['descripcion'] }}</td>
                    <td class="num">{{ $n($l['antes']) }}</td>
                    <td class="num">{{ $n($l['despues']) }}</td>
                    <td>{{ implode('. ', $l['acciones']) }}{!! $l['error'] ? '<div class="err">No se pudo: ' . e($l['error']) . '</div>' : '' !!}</td>
                </tr>
            @endforeach
        </table>
        @if (count($report['lineas']) > $limit)
            <div class="note">Aquí se muestran las primeras {{ $limit }} de {{ count($report['lineas']) }} referencias. El detalle completo va en el archivo adjunto (sincronizacion_effi_bodega_web.csv).</div>
        @else
            <div class="note">El mismo detalle va en el archivo adjunto (sincronizacion_effi_bodega_web.csv) para abrirlo en Excel.</div>
        @endif
    </div>

    <div class="footer">
        Correo automático de la sincronización Siigo → Effi. Pantalla de sincronización: <a href="{{ $link }}">{{ $link }}</a>
    </div>
</div>
</body>
</html>
