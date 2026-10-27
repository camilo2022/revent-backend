@extends('layouts.integration')

@section('title', 'Trazabilidad con auditoría de tránsitos')

@push('styles')
<link rel="stylesheet" href="{{ asset('integration/css/traceability.css') }}">
@endpush

@php
    $labels = [
        'transito' => 'Por tránsito',
        'directo' => 'Traslado directo',
        'parcial' => 'Parcial',
        'sin_bodega_transito' => 'Bodega sin tránsito',
    ];
    $summary = $audit['summary'];
    $directCount = ($summary['directo'] ?? 0) + ($summary['parcial'] ?? 0);
    $fmt = fn ($n) => rtrim(rtrim(number_format($n, 2, ',', '.'), '0'), ',');
@endphp

@section('content')
<div class="tr-wrap">
    <div class="tr-top">
        <div class="tr-head">
            <h1>Trazabilidad con auditoría de tránsitos</h1>
            <p>
                Bodega <strong>{{ $audit['warehouse'] }}</strong>
                @if ($audit['transit'])
                    · tránsito revisado: <strong>{{ $audit['transit'] }}</strong>
                @endif
                · desde <strong>{{ \Carbon\Carbon::parse($audit['start_date'])->format('d/m/Y') }}</strong>
            </p>
        </div>
        <a href="{{ route('siigo.product_traceability_excel', $key) }}" class="tr-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Descargar Excel
        </a>
    </div>

    @unless ($audit['transit'])
        <div class="tr-alert">
            No existe una bodega «TRANSITO {{ $audit['warehouse'] }}» en Siigo, así que no se puede comprobar el paso por tránsito.
            Los traslados se muestran sin clasificar.
        </div>
    @endunless

    <div class="tr-cards">
        <div class="tr-card ok"><div class="n">{{ $summary['transito'] ?? 0 }}</div><div class="l">Ingresos por tránsito</div></div>
        <div class="tr-card bad"><div class="n">{{ $summary['directo'] ?? 0 }}</div><div class="l">Traslados directos</div></div>
        <div class="tr-card warn"><div class="n">{{ $summary['parcial'] ?? 0 }}</div><div class="l">Parciales (parte directa)</div></div>
        <div class="tr-card muted"><div class="n">{{ $summary['otros'] ?? 0 }}</div><div class="l">Otros ingresos (compra, ajuste…)</div></div>
        <div class="tr-card muted"><div class="n">{{ $summary['sin_movimientos'] ?? 0 }}</div><div class="l">Códigos sin movimientos</div></div>
    </div>

    <div class="tr-filters">
        <button type="button" class="tr-chip active" data-filter="all">Todos</button>
        <button type="button" class="tr-chip" data-filter="irregular">Con traslado directo ({{ $directCount }})</button>
        <button type="button" class="tr-chip" data-filter="ok">Solo por tránsito</button>
        <input type="search" class="tr-search" id="trSearch" placeholder="Buscar código o nombre…">
    </div>

    @foreach ($audit['products'] as $product)
        @php
            $statuses = collect($product['rows'])->pluck('status')->filter()->unique();
            $irregular = $statuses->contains('directo') || $statuses->contains('parcial');
            $group = $irregular ? 'irregular' : 'ok';
            $hasEntries = collect($product['rows'])->contains(fn ($r) => $r['entry'] > 0);
            $badge = $irregular ? ($statuses->contains('directo') ? 'directo' : 'parcial')
                : ($statuses->isNotEmpty() ? $statuses->first() : (count($product['rows']) ? 'otro' : 'sin_movimientos'));
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
                            <th>Tránsito previo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($product['rows'] as $row)
                            <tr>
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
                                            <span class="tr-sub">{{ $fmt($row['direct']) }} sin pasar por tránsito</span>
                                        @endif
                                    @elseif ($row['entry'] > 0)
                                        <span class="tr-badge otro">No es traslado</span>
                                    @endif
                                </td>
                                <td>
                                    @forelse ($row['transit_vouchers'] as $transit)
                                        <span>
                                            @if ($transit['url'])
                                                <a href="{{ $transit['url'] }}" target="_blank" rel="noopener">{{ $transit['voucher'] }}</a>
                                            @else
                                                {{ $transit['voucher'] }}
                                            @endif
                                            <span class="tr-sub">{{ $transit['date'] }} · {{ $fmt($transit['quantity']) }} u.</span>
                                        </span>
                                    @empty
                                        @if ($row['status']) <span class="tr-sub">—</span> @endif
                                    @endforelse
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
        <a href="{{ route('siigo.product_traceability_excel', $key) }}" class="tr-btn tr-btn--ghost">Descargar Excel</a>
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
