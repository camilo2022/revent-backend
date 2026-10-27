@extends('layouts.integration')

@section('title', 'Trazabilidad con auditoría de traslados')

@push('styles')
<link rel="stylesheet" href="{{ asset('integration/css/traceability.css') }}">
@endpush

@php
    $labels = [
        'transito' => 'Por tránsito',
        'directo' => 'Traslado directo',
        'parcial' => 'Parcial',
        'sin_bodega_transito' => 'Bodega sin tránsito',
        'salida_transito' => 'Sale a tránsito',
        'salida_directa' => 'Salida directa a otra bodega',
        'sin_origen' => 'Sin dato de origen',
    ];
    // Estados que se resaltan: salió o llegó directo, sin pasar por la bodega de tránsito.
    $alerts = ['directo', 'parcial', 'salida_directa'];
    $summary = $audit['summary'];
    $alertCount = ($summary['directo'] ?? 0) + ($summary['parcial'] ?? 0) + ($summary['salida_directa'] ?? 0);
    $fmt = fn ($n) => rtrim(rtrim(number_format($n, 2, ',', '.'), '0'), ',');
@endphp

@section('content')
<div class="tr-wrap">
    <div class="tr-top">
        <div class="tr-head">
            <h1>Trazabilidad con auditoría de traslados</h1>
            <p>
                Bodega <strong>{{ $audit['warehouse'] }}</strong>
                @if ($audit['transit'])
                    · tránsito: <strong>{{ $audit['transit'] }}</strong>
                @endif
                · desde <strong>{{ \Carbon\Carbon::parse($audit['start_date'])->format('d/m/Y') }}</strong>
            </p>
        </div>
    </div>

    @unless ($audit['transit'])
        <div class="tr-alert">
            No existe una bodega «TRANSITO {{ $audit['warehouse'] }}» en Siigo, así que todo ingreso por traslado se muestra sin clasificar.
        </div>
    @endunless

    <div class="tr-cards">
        <div class="tr-card ok"><div class="n">{{ $summary['transito'] ?? 0 }}</div><div class="l">Ingresos por tránsito</div></div>
        <div class="tr-card bad"><div class="n">{{ $summary['directo'] ?? 0 }}</div><div class="l">Ingresos directos desde otra bodega</div></div>
        <div class="tr-card bad"><div class="n">{{ $summary['salida_directa'] ?? 0 }}</div><div class="l">Salidas directas a otra bodega</div></div>
        <div class="tr-card warn"><div class="n">{{ $summary['parcial'] ?? 0 }}</div><div class="l">Parciales (parte directa)</div></div>
        <div class="tr-card muted"><div class="n">{{ $summary['otros'] ?? 0 }}</div><div class="l">Otros ingresos (compra, ajuste…)</div></div>
        <div class="tr-card muted"><div class="n">{{ $summary['sin_movimientos'] ?? 0 }}</div><div class="l">Códigos sin movimientos</div></div>
    </div>

    <div class="tr-filters">
        <button type="button" class="tr-chip active" data-filter="all">Todos</button>
        <button type="button" class="tr-chip" data-filter="irregular">Con traslado directo ({{ $alertCount }})</button>
        <button type="button" class="tr-chip" data-filter="ok">Sin traslados directos</button>
        <input type="search" class="tr-search" id="trSearch" placeholder="Buscar código o nombre…">
    </div>

    @foreach ($audit['products'] as $product)
        @php
            $statuses = collect($product['rows'])->pluck('status')->filter()->unique();
            $irregular = $statuses->intersect($alerts)->isNotEmpty();
            $group = $irregular ? 'irregular' : 'ok';
            $hasEntries = collect($product['rows'])->contains(fn ($r) => $r['entry'] > 0);
            $badge = $statuses->contains('salida_directa') ? 'salida_directa'
                : ($statuses->contains('directo') ? 'directo'
                : ($statuses->contains('parcial') ? 'parcial'
                : ($statuses->isNotEmpty() ? $statuses->first() : (count($product['rows']) ? 'otro' : 'sin_movimientos'))));
            $badgeLabel = $labels[$badge] ?? (count($product['rows']) ? ($hasEntries ? 'Otros ingresos' : 'Solo salidas') : 'Sin movimientos');
        @endphp
        <details class="tr-product" data-group="{{ $group }}"
            data-text="{{ strtolower($product['code'] . ' ' . $product['name']) }}" {{ $irregular ? 'open' : '' }}>
            <summary>
                <span class="code">{{ $product['code'] }}</span>
                <span class="name">{{ $product['name'] ?? 'Sin movimientos en el periodo' }}</span>
                @if ($product['rows'])
                    <span class="tr-saldo">Saldo final <strong>{{ $fmt(end($product['rows'])['balance']) }}</strong></span>
                @endif
                <span class="tr-badge {{ $badge }}">{{ $badgeLabel }}</span>
            </summary>

            @if ($product['rows'])
                <table class="tr-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Comprobante</th>
                            <th class="num">Entrada</th>
                            <th class="num">Salida</th>
                            <th class="num">Saldo</th>
                            <th>Resultado</th>
                            <th>Origen → destino</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($product['rows'] as $row)
                            <tr class="{{ in_array($row['status'], $alerts, true) ? 'tr-row-alert' : '' }}">
                                <td>{{ $row['date'] }}<span class="tr-sub">{{ $row['time'] }}</span></td>
                                <td>
                                    @if ($row['url'])
                                        <a href="{{ $row['url'] }}" target="_blank" rel="noopener">{{ $row['voucher'] }}</a>
                                    @else
                                        {{ $row['voucher'] }}
                                    @endif
                                </td>
                                <td class="num in">{{ $row['entry'] > 0 ? $fmt($row['entry']) : '' }}</td>
                                <td class="num out">{{ $row['output'] > 0 ? $fmt($row['output']) : '' }}</td>
                                <td class="num saldo">{{ $fmt($row['balance']) }}</td>
                                <td>
                                    @if ($row['status'])
                                        <span class="tr-badge {{ $row['status'] }}">{{ $labels[$row['status']] }}</span>
                                        @if ($row['status'] === 'parcial')
                                            <span class="tr-sub">{{ $fmt($row['via_transit']) }} por tránsito · {{ $fmt($row['direct']) }} directas</span>
                                        @elseif ($row['status'] === 'directo')
                                            <span class="tr-sub">Llegó sin pasar por el tránsito</span>
                                        @elseif ($row['status'] === 'salida_directa')
                                            <span class="tr-sub">Salió a una bodega que no es tránsito</span>
                                        @endif
                                    @elseif ($row['entry'] > 0)
                                        <span class="tr-badge otro">No es traslado</span>
                                    @endif
                                </td>
                                <td class="tr-route">
                                    @if ($row['origin'] && $row['destination'])
                                        <strong>{{ $row['origin'] }}</strong> <span class="arrow">→</span> <strong>{{ $row['destination'] }}</strong>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="tr-empty">Este código no tuvo movimientos en la bodega en el periodo consultado.</div>
            @endif
        </details>
    @endforeach

    <div class="tr-actions">
        <a href="{{ route('siigo.product_traceability') }}" class="back-link">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            Nueva consulta
        </a>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const chips = document.querySelectorAll('.tr-chip');
        const cards = document.querySelectorAll('.tr-product');
        const search = document.getElementById('trSearch');
        let filter = 'all';

        function apply() {
            const q = search.value.trim().toLowerCase();
            cards.forEach(card => {
                const okGroup = filter === 'all' || card.dataset.group === filter;
                const okText = !q || card.dataset.text.includes(q);
                card.classList.toggle('tr-hidden', !(okGroup && okText));
            });
        }

        chips.forEach(chip => chip.addEventListener('click', () => {
            chips.forEach(c => c.classList.remove('active'));
            chip.classList.add('active');
            filter = chip.dataset.filter;
            apply();
        }));

        search.addEventListener('input', apply);
    })();
</script>
@endpush
