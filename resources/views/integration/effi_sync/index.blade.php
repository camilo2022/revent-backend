@extends('layouts.integration')

@section('title', 'Sincronizar Effi con Siigo')

@push('styles')
<link rel="stylesheet" href="{{ asset('integration/css/effi-sync.css') }}">
@endpush

@section('content')
<div class="ef-wrap">

    <div class="ef-card">
        <div class="excel-upload-title">Sincronizar Effi con Siigo</div>
        <div class="excel-upload-subtitle">
            Compara, código por código, el inventario de la bodega <strong>WEB</strong> de Siigo (bodega {{ $siigoWarehouse }}) con la bodega de Effi.
            La sincronización dejará Effi exactamente igual a Siigo con un ajuste de inventario por la diferencia.
        </div>

        <div class="excel-btn-row">
            <button type="button" class="excel-submit-btn" id="btnCompare">
                <span class="btn-spinner"></span>
                <span class="btn-label">Consultar inventario y diferencias</span>
            </button>
            <button type="button" class="excel-submit-btn excel-submit-btn--ghost" disabled title="Se activa cuando se apruebe el cuadre">
                Sincronizar con Effi
            </button>
        </div>

        <div class="ef-note">
            Por ahora esta pantalla solo consulta: no hace ajustes en Effi. La consulta puede tardar unos 20 segundos.
        </div>

        <div class="excel-error" id="errorBox"></div>
    </div>

    <div class="ef-card ef-hidden" id="resultCard">
        <div class="ef-meta" id="meta"></div>

        <div class="ef-warn ef-hidden" id="stockWarning">
            Effi no está entregando el stock de los artículos: el usuario de la API key necesita permiso de consulta sobre
            <strong>«Artículos - Stock disponible» (función 156)</strong>. Mientras tanto se ve el inventario de Siigo y el cruce de códigos con Effi, sin las unidades de Effi.
        </div>

        <div class="ef-tiles" id="tiles"></div>

        <div class="ef-toolbar">
            <div class="ef-tabs" id="tabs"></div>
            <input type="search" class="excel-field-input ef-search" id="search" placeholder="Buscar código o descripción">
        </div>

        <div class="ef-table-wrap">
            <table class="ef-table">
                <thead>
                    <tr>
                        <th>Código Siigo</th>
                        <th>Descripción Siigo</th>
                        <th class="num">Siigo</th>
                        <th class="num">Effi</th>
                        <th class="num">Diferencia</th>
                        <th>Artículo en Effi</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody id="rows"></tbody>
            </table>
        </div>
        <div class="ef-count" id="rowsCount"></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const STATUS = {
        igual: 'Igual',
        diferencia: 'Diferencia',
        no_existe: 'No existe en Effi',
        sin_control: 'Effi no controla stock por bodega',
        sin_stock_effi: 'Sin stock de Effi',
        repetida: 'Referencia repetida en Effi',
    };
    const btn = document.getElementById('btnCompare');
    const label = btn.querySelector('.btn-label');
    const errorBox = document.getElementById('errorBox');
    const fmt = n => n === null || n === undefined ? '—' : Number(n).toLocaleString('es-CO');
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    let data = null, filter = 'todos';

    btn.addEventListener('click', async () => {
        btn.disabled = true;
        btn.classList.add('is-loading');
        label.textContent = 'Consultando Siigo y Effi…';
        errorBox.classList.remove('show');

        try {
            const res = await fetch(@json(route('siigo.effi_sync_compare')), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
            });
            const json = await res.json();
            if (!json.ok) throw new Error(json.message || 'Error desconocido');
            data = json.data;
            render();
        } catch (e) {
            errorBox.textContent = e.message;
            errorBox.classList.add('show');
        } finally {
            btn.disabled = false;
            btn.classList.remove('is-loading');
            label.textContent = 'Consultar inventario y diferencias';
        }
    });

    document.getElementById('search').addEventListener('input', renderRows);

    function render() {
        const r = data.resumen;
        const stock = data.stock_effi_disponible;

        document.getElementById('resultCard').classList.remove('ef-hidden');
        document.getElementById('meta').textContent =
            `Consulta del ${data.fecha} · Siigo: bodega WEB (${data.bodega_siigo}) · Effi: ${data.bodega_effi}`;
        document.getElementById('stockWarning').classList.toggle('ef-hidden', stock);

        const tiles = [
            ['Unidades en Siigo', fmt(r.unidades_siigo), `${fmt(r.codigos_siigo)} códigos con saldo`],
            ['Unidades en Effi', stock ? fmt(r.unidades_effi) : 'Sin permiso', 'misma bodega'],
            ['Códigos iguales', stock ? fmt(r.iguales) : '—', ''],
            ['Con diferencia', stock ? fmt(r.con_diferencia) : '—', ''],
            ['No existen en Effi', fmt(r.no_existen), `${fmt(r.unidades_no_existen)} unidades de Siigo`],
            ['Sin control por bodega', fmt(r.sin_control), 'no se pueden ajustar así'],
        ];
        document.getElementById('tiles').innerHTML = tiles.map(([l, v, h]) =>
            `<div class="ef-tile"><div class="ef-tile-label">${l}</div><div class="ef-tile-value">${v}</div><div class="ef-tile-hint">${h}</div></div>`).join('');

        const counts = data.filas.reduce((acc, f) => (acc[f.estado] = (acc[f.estado] || 0) + 1, acc), {});
        const tabs = [['todos', `Todos (${data.filas.length})`], ...Object.keys(STATUS).filter(k => counts[k]).map(k => [k, `${STATUS[k]} (${counts[k]})`])];
        if (!tabs.some(([k]) => k === filter)) filter = 'todos';
        document.getElementById('tabs').innerHTML = tabs.map(([k, l]) =>
            `<button type="button" class="ef-tab ${k === filter ? 'active' : ''}" data-k="${k}">${l}</button>`).join('');
        document.querySelectorAll('.ef-tab').forEach(t => t.addEventListener('click', () => { filter = t.dataset.k; render(); }));

        renderRows();
    }

    function renderRows() {
        const q = document.getElementById('search').value.trim().toUpperCase();
        const rows = data.filas.filter(f => (filter === 'todos' || f.estado === filter)
            && (!q || f.codigo.includes(q) || String(f.descripcion).toUpperCase().includes(q) || String(f.effi_referencia || '').toUpperCase().includes(q)));
        const shown = rows.slice(0, 1500);

        document.getElementById('rows').innerHTML = shown.length ? shown.map(f => {
            const diff = f.diferencia === null ? '—'
                : `<span class="${f.diferencia > 0 ? 'ef-pos' : (f.diferencia < 0 ? 'ef-neg' : '')}">${f.diferencia > 0 ? '+' : ''}${fmt(f.diferencia)}</span>`;
            const art = f.effi_id
                ? `${esc(f.effi_referencia)}<div class="ef-detail">#${esc(f.effi_id)}${f.cruce === 'descripcion' ? ' · por descripción' : ''} · ${esc(f.effi_descripcion)}</div>`
                : '<span class="ef-detail">—</span>';
            return `<tr>
                <td>${esc(f.codigo)}</td>
                <td>${esc(f.descripcion)}</td>
                <td class="num">${fmt(f.siigo)}</td>
                <td class="num">${fmt(f.effi)}</td>
                <td class="num">${diff}</td>
                <td>${art}</td>
                <td><span class="ef-badge ${f.estado}">${STATUS[f.estado] || f.estado}</span></td>
            </tr>`;
        }).join('') : '<tr><td colspan="7" class="ef-empty">Sin filas para este filtro.</td></tr>';

        document.getElementById('rowsCount').textContent = rows.length > shown.length
            ? `Mostrando ${shown.length} de ${rows.length} filas; usa el buscador para acotar.` : `${rows.length} filas.`;
    }
})();
</script>
@endpush
