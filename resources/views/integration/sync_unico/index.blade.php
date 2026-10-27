@extends('layouts.integration')

@section('title', 'Sincronizar ventas con Único')

@push('styles')
<link rel="stylesheet" href="{{ asset('integration/css/sync-unico.css') }}">
@endpush

@php
    $statusLabels = [
        'running' => 'En proceso',
        'stale' => 'Sin respuesta',
        'success' => 'Completada',
        'failed' => 'Falló',
    ];
    $fmtDate = fn ($iso) => $iso ? \Carbon\Carbon::parse($iso)->format('d/m/Y h:i A') : '—';
    $fmtDay = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('d/m') : '—';
    $fmtNum = fn ($n) => $n === null ? '—' : number_format((int) $n, 0, ',', '.');
@endphp

@section('content')
<div class="su-wrap">

    <div class="su-card">
        <div class="excel-upload-title">Sincronizar ventas con Único</div>
        <div class="excel-upload-subtitle">
            Las ventas de Siigo se envían a Único automáticamente <strong>cada hora, de 8:00 a. m. a 10:00 p. m.</strong>
            Si una ejecución automática falla o no llega a correr, lánzala a mano desde aquí.
        </div>

        @if (session('status'))
            <div class="excel-status">{{ session('status') }}</div>
        @endif

        <form action="{{ route('siigo.sync_unico_run') }}" method="POST" id="syncForm">
            @csrf

            <div class="section-title">¿Qué ventas quieres sincronizar?</div>

            <div class="su-options">
                <label class="su-option">
                    <input type="radio" name="mode" value="today" {{ old('mode', 'today') === 'today' ? 'checked' : '' }}>
                    <span class="su-option-body">
                        <span class="su-option-title">Hoy</span>
                        <span class="su-option-hint">El mismo rango de la sincronización automática.</span>
                    </span>
                </label>
                <label class="su-option">
                    <input type="radio" name="mode" value="yesterday" {{ old('mode') === 'yesterday' ? 'checked' : '' }}>
                    <span class="su-option-body">
                        <span class="su-option-title">Ayer</span>
                        <span class="su-option-hint">Para recuperar un día completo que no se envió.</span>
                    </span>
                </label>
                <label class="su-option">
                    <input type="radio" name="mode" value="range" {{ old('mode') === 'range' ? 'checked' : '' }}>
                    <span class="su-option-body">
                        <span class="su-option-title">Rango de fechas</span>
                        <span class="su-option-hint">Hasta {{ \App\Http\Requests\Integration\Siigo\ManualSyncUnicoRequest::MAX_DAYS }} días.</span>
                    </span>
                </label>
            </div>

            <div class="su-range {{ old('mode') === 'range' ? 'show' : '' }}" id="rangeFields">
                <div class="excel-field-group">
                    <label for="createdStart" class="excel-field-label">Fecha inicial <span class="required-mark">*</span></label>
                    <input type="date" name="created_start" id="createdStart" class="excel-field-input @error('created_start') is-invalid @enderror"
                        value="{{ old('created_start') }}" max="{{ now()->format('Y-m-d') }}">
                    @error('created_start')<div class="excel-error show">{{ $message }}</div>@enderror
                </div>
                <div class="excel-field-group">
                    <label for="createdEnd" class="excel-field-label">Fecha final <span class="required-mark">*</span></label>
                    <input type="date" name="created_end" id="createdEnd" class="excel-field-input @error('created_end') is-invalid @enderror"
                        value="{{ old('created_end') }}" max="{{ now()->format('Y-m-d') }}">
                    @error('created_end')<div class="excel-error show">{{ $message }}</div>@enderror
                </div>
            </div>

            @error('mode')<div class="excel-error show">{{ $message }}</div>@enderror

            <div class="su-note">
                Único responde cuántas ventas insertó y cuántas rechazó; lo verás en «Enviadas» y «Rechazadas» del historial.
                El resultado completo llega por correo a <strong>{{ $notifyEmail }}</strong>.
            </div>

            <button type="submit" class="excel-submit-btn" id="syncBtn">
                <span class="btn-label">Sincronizar ahora</span>
            </button>
        </form>
    </div>

    <div class="su-card">
        <div class="su-history-head">
            <div class="excel-upload-title">Últimas sincronizaciones</div>
            <a href="{{ route('siigo.sync_unico_manual') }}" class="su-refresh">Actualizar</a>
        </div>

        @if ($runs)
            <div class="su-table-wrap">
                <table class="su-table">
                    <thead>
                        <tr>
                            <th>Lanzada</th>
                            <th>Origen</th>
                            <th>Ventas del</th>
                            <th>Estado</th>
                            <th class="num">Facturas</th>
                            <th class="num">Enviadas</th>
                            <th class="num">Rechazadas</th>
                            <th>Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($runs as $run)
                            <tr>
                                <td>{{ $fmtDate($run['started_at']) }}</td>
                                <td>{{ ucfirst($run['source']) }}</td>
                                <td>
                                    {{ $fmtDay($run['range'][0]) }}
                                    @if ($fmtDay($run['range'][0]) !== $fmtDay($run['range'][1])) → {{ $fmtDay($run['range'][1]) }} @endif
                                </td>
                                <td><span class="su-badge {{ $run['status'] }}">{{ $statusLabels[$run['status']] ?? $run['status'] }}</span></td>
                                <td class="num">{{ $fmtNum($run['invoices']) }}</td>
                                <td class="num">{{ $fmtNum($run['sent']) }}</td>
                                <td class="num">{{ $fmtNum($run['rejected']) }}</td>
                                <td class="su-detail">
                                    @if ($run['status'] === 'failed')
                                        <span class="su-error" title="{{ $run['error'] }}">{{ \Illuminate\Support\Str::limit($run['error'], 90) }}</span>
                                    @elseif ($run['status'] === 'stale')
                                        Lleva más de 90 minutos sin terminar: revisa que la cola de sincronización esté corriendo.
                                    @elseif ($run['status'] === 'running')
                                        Esperando resultado…
                                    @elseif ($run['finished_at'])
                                        Terminó {{ $fmtDate($run['finished_at']) }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="su-empty">Todavía no hay sincronizaciones registradas. Aparecerán aquí desde la próxima ejecución, automática o manual.</div>
        @endif
    </div>

    <a href="{{ route('home') }}" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Volver a acciones disponibles
    </a>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const form = document.getElementById('syncForm');
        const btn = document.getElementById('syncBtn');
        const range = document.getElementById('rangeFields');
        const radios = form.querySelectorAll('input[name="mode"]');
        const labels = { today: 'hoy', yesterday: 'ayer', range: 'el rango de fechas indicado' };

        function toggleRange() {
            const mode = form.querySelector('input[name="mode"]:checked').value;
            range.classList.toggle('show', mode === 'range');
        }

        radios.forEach(r => r.addEventListener('change', toggleRange));
        toggleRange();

        form.addEventListener('submit', (e) => {
            const mode = form.querySelector('input[name="mode"]:checked').value;
            if (!confirm('¿Sincronizar con Único las ventas de ' + labels[mode] + '?')) {
                e.preventDefault();
                return;
            }
            btn.disabled = true;
            btn.querySelector('.btn-label').textContent = 'Lanzando…';
        });
    })();
</script>
@endpush
