@extends('layouts.integration')

@section('title', 'Clientes por venta')

@push('styles')
<link rel="stylesheet" href="{{ asset('integration/css/sync-unico.css') }}">
<style>
    .cs-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end; }
    .cs-req { color: #dc2626; font-weight: 700; }
    .cs-hint { font-size: .75rem; color: #6b7280; margin-top: .6rem; }
    .cs-form label { display: block; font-size: .8rem; font-weight: 600; color: #374151; margin-bottom: .3rem; }
    .cs-form input, .cs-form select { width: 100%; padding: .55rem .7rem; border: 1px solid #d1d5db; border-radius: 10px; font-size: .9rem; background: #fff; }
    .cs-customer { border: 1px solid #eef0f2; border-radius: 12px; padding: 1rem 1.2rem; margin-top: .8rem; }
    .cs-head { display: flex; flex-wrap: wrap; justify-content: space-between; gap: .5rem; }
    .cs-name { font-weight: 700; color: #1f2937; }
    .cs-meta { font-size: .82rem; color: #6b7280; }
    .cs-phone { font-size: 1.15rem; font-weight: 700; color: #166534; }
    .cs-phone.invalid { color: #991b1b; }
    .cs-group-title { margin-top: 1.4rem; font-weight: 700; font-size: .95rem; color: #374151; }
    .cs-invoice { margin-top: .7rem; font-size: .82rem; }
    .cs-invoice-title { font-weight: 600; color: #374151; }
</style>
@endpush

@php
    $fmtMoney = fn ($n) => '$' . number_format($n, 0, ',', '.');
    $fmtQty = fn ($n) => rtrim(rtrim(number_format($n, 2, ',', '.'), '0'), ',');
@endphp

@section('content')
<div class="su-wrap">

    <div class="su-card">
        <div class="excel-upload-title">Clientes por venta</div>
        <div class="excel-upload-subtitle">
            Consulta qué clientes compraron en una fecha, con su teléfono y lo que compraron.
            Sirve, por ejemplo, para ver quién compró este mismo día el año pasado.
            Se consulta un solo día. El token de Siigo solo valida tu acceso.
        </div>

        @if ($errors->any())
            <div class="excel-status su-error">{{ $errors->first() }}</div>
        @endif

        <form action="{{ route('siigo.customer_sales_search') }}" method="POST" id="csForm">
            @csrf
            <div class="cs-form">
                <div style="grid-column: 1 / -1;">
                    <label for="token">Token de Siigo (valida tu acceso) <span class="cs-req" title="Obligatorio">*</span></label>
                    <input type="text" id="token" name="token" value="{{ request('token') }}" placeholder="Pega aquí el token" autocomplete="off" required>
                </div>
                <div>
                    <label for="date">Fecha <span class="cs-req" title="Obligatorio">*</span></label>
                    <input type="date" id="date" name="date" value="{{ old('date', request('date', now()->subYear()->format('Y-m-d'))) }}" required>
                </div>
                <div>
                    <label for="cost_center">Centro de costo (tienda)</label>
                    <select id="cost_center" name="cost_center">
                        <option value="">Todos</option>
                        @foreach ($costCenters as $center)
                            <option value="{{ $center['id'] }}" {{ (string) old('cost_center', request('cost_center')) === (string) $center['id'] ? 'selected' : '' }}>{{ $center['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <button type="submit" class="excel-submit-btn" id="csBtn">Consultar</button>
                </div>
            </div>
        </form>
        <div class="cs-hint"><span class="cs-req">*</span> Campo obligatorio</div>
    </div>

    @if (is_array($customers))
        @php
            // Primero los clientes con teléfono válido y después los demás
            $groups = [
                'Teléfonos válidos' => array_values(array_filter($customers, fn ($c) => $c['phone_valid'])),
                'Teléfonos no válidos o sin teléfono' => array_values(array_filter($customers, fn ($c) => !$c['phone_valid'])),
            ];
        @endphp

        <div class="su-card">
            <div class="excel-upload-title">
                {{ count($customers) }} {{ count($customers) === 1 ? 'cliente' : 'clientes' }}
            </div>

            @foreach ($groups as $title => $group)
                @continue(empty($group))

                <div class="cs-group-title">{{ $title }} ({{ count($group) }})</div>

                @foreach ($group as $customer)
                    <div class="cs-customer">
                        <div class="cs-head">
                            <div>
                                <div class="cs-name">{{ $customer['name'] }}</div>
                                <div class="cs-phone {{ $customer['phone_valid'] ? '' : 'invalid' }}">{{ $customer['phone'] ?: 'Sin teléfono' }}</div>
                                @if ($customer['email'])
                                    <div class="cs-meta">{{ $customer['email'] }}</div>
                                @endif
                            </div>
                            <div class="cs-meta">{{ count($customer['invoices']) }} {{ count($customer['invoices']) === 1 ? 'factura' : 'facturas' }}</div>
                        </div>

                        @foreach ($customer['invoices'] as $invoice)
                            <div class="cs-invoice">
                                <div class="cs-invoice-title">
                                    {{ $invoice['name'] }} · {{ \Carbon\Carbon::parse($invoice['date'])->format('d/m/Y') }} · {{ $fmtMoney($invoice['total']) }}
                                </div>
                                <div class="su-table-wrap">
                                    <table class="su-table">
                                        <thead>
                                            <tr><th>Referencia</th><th>Descripción</th><th class="num">Cantidad</th></tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($invoice['items'] as $item)
                                                <tr>
                                                    <td>{{ $item['code'] }}</td>
                                                    <td>{{ $item['description'] }}</td>
                                                    <td class="num">{{ $fmtQty($item['quantity']) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            @endforeach

            @if (empty($customers))
                <div class="su-empty">No hay ventas para esa fecha y centro de costo.</div>
            @endif
        </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
    // Evita lanzar la consulta dos veces mientras Siigo responde
    document.getElementById('csForm').addEventListener('submit', () => {
        const btn = document.getElementById('csBtn');
        btn.disabled = true;
        btn.textContent = 'Consultando…';
    });
</script>
@endpush
