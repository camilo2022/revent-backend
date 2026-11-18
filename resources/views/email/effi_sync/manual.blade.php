<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, Helvetica, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 760px; margin: 20px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,0.1); }
        .header { padding: 20px; color: #fff; font-size: 18px; font-weight: bold; background: #ef6c00; }
        .content { padding: 20px; color: #333; font-size: 14px; line-height: 1.5; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 12px; }
        th, td { padding: 6px 8px; border: 1px solid #e0e0e0; text-align: left; vertical-align: top; }
        th { background: #f0f0f0; }
        h3 { margin: 24px 0 6px; font-size: 15px; }
        .why { color: #666; font-size: 13px; margin: 0 0 8px; }
        .btn { display: inline-block; background: #2e7d32; color: #fff !important; text-decoration: none; padding: 10px 18px; border-radius: 6px; font-weight: bold; }
        .note { background: #fff8e1; border: 1px solid #ffe082; border-radius: 6px; padding: 10px 12px; font-size: 13px; }
        .footer { padding: 15px 20px; font-size: 12px; color: #999; border-top: 1px solid #eee; }
    </style>
</head>
<body>
@php $limit = 40; @endphp
<div class="container">
    <div class="header">⚠️ Hay que sincronizar la bodega WEB con Effi manualmente</div>

    <div class="content">
        <p>
            Se hizo movimiento en la bodega <strong>WEB</strong> con:
        </p>
        <ul>
            @foreach ($documents as $doc)
                <li><strong>{{ $doc['documento'] }}</strong> — {{ $doc['sentido'] }}</li>
            @endforeach
        </ul>
        <p>
            Para dejar Effi igual que Siigo se intentó sincronizar automáticamente, pero <strong>no se hizo nada</strong>:
            hay productos con inconsistencias en los que alguien debe decidir cómo se sincronizan. El sistema no toma esa decisión por su cuenta.
        </p>

        <p><a class="btn" href="{{ $link }}">Abrir la sincronización y tomar las decisiones</a></p>
        <p>Allí pulsa «Ver plan de sincronización», entra a la pestaña «Decisiones que te tocan», responde cada una y sincroniza.</p>

        @if (!empty($pending['categorias']))
            <h3>Categorías que no existen en Effi ({{ count($pending['categorias']) }})</h3>
            <p class="why">Son productos nuevos para Effi cuyo modelo en Siigo no tiene una categoría igual en Effi. Debes elegir: usar una categoría existente, crear la categoría en Effi o crear el producto sin categoría.</p>
            <table>
                <tr><th>Modelo en Siigo</th><th>Productos</th><th>Ejemplos</th><th>Categoría parecida (sugerencia)</th></tr>
                @foreach ($pending['categorias'] as $c)
                    <tr>
                        <td>{{ $c['modelo'] !== '' ? $c['modelo'] : '(sin modelo)' }}</td>
                        <td>{{ $c['cantidad'] }}</td>
                        <td>{{ implode(', ', $c['ejemplos']) }}</td>
                        <td>{{ $c['sugerida']['nombre'] ?? 'ninguna' }}</td>
                    </tr>
                @endforeach
            </table>
        @endif

        @if (!empty($pending['duplicados']))
            <h3>Referencias repetidas en Effi ({{ count($pending['duplicados']) }})</h3>
            <p class="why">La misma referencia está en dos artículos de Effi. Debes elegir cuál inhabilitar (se recomienda la que no controla stock por bodega); el inhabilitado se vacía y se anula, y en Effi se puede reactivar.</p>
            <table>
                <tr><th>Referencia</th><th>Descripción</th><th>Artículos en Effi</th></tr>
                @foreach (array_slice($pending['duplicados'], 0, $limit) as $d)
                    <tr>
                        <td>{{ $d['codigo'] }}</td>
                        <td>{{ $d['descripcion'] }}</td>
                        <td>@foreach ($d['articulos'] as $a)#{{ $a['id'] }} ({{ $a['gestion_stock'] == 3 ? 'control por bodega' : 'sin control por bodega' }}, {{ $a['stock'] }} u.)@if (!$loop->last) · @endif @endforeach</td>
                    </tr>
                @endforeach
            </table>
        @endif

        @if (!empty($pending['otros']))
            <h3>Otros códigos que no se pueden sincronizar solos ({{ count($pending['otros']) }})</h3>
            <table>
                <tr><th>Código</th><th>Descripción</th><th>Motivo</th></tr>
                @foreach (array_slice($pending['otros'], 0, $limit) as $o)
                    <tr><td>{{ $o['codigo'] }}</td><td>{{ $o['descripcion'] }}</td><td>{{ $o['motivo'] }}</td></tr>
                @endforeach
            </table>
        @endif

        @if (!empty($pending['avisos']))
            <h3>Productos con datos incompletos ({{ count($pending['avisos']) }})</h3>
            <p class="why">Se crearían en Effi sin algún dato (por ejemplo precio o marca). Conviene revisarlos antes de sincronizar.</p>
            <table>
                <tr><th>Código</th><th>Descripción</th><th>Qué falta</th></tr>
                @foreach (array_slice($pending['avisos'], 0, $limit) as $o)
                    <tr><td>{{ $o['codigo'] }}</td><td>{{ $o['descripcion'] }}</td><td>{{ $o['motivo'] }}</td></tr>
                @endforeach
            </table>
        @endif

        <div class="note">Mientras haya decisiones pendientes no se vuelve a repetir este aviso en las siguientes 6 horas, aunque se hagan más traslados.</div>
    </div>

    <div class="footer">Correo automático de la sincronización Siigo → Effi.</div>
</div>
</body>
</html>
