<!-- resources/views/integration/account_payable.blade.php -->
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<title>Cuentas por pagar</title>
<style>
    * { box-sizing: border-box; }

    body {
        background: #f3f4f6;
        font-family: 'Segoe UI', system-ui, sans-serif;
        margin: 0;
        padding: 2rem 1rem;
    }

    .wrapper {
        max-width: 1280px;
        margin: 0 auto;
    }

    .card {
        background: #ffffff;
        border: 1px solid #eef0f2;
        border-radius: 16px;
        padding: 1.9rem 2.1rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    }

    .title {
        font-size: 1.2rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 0.3rem;
    }

    .subtitle {
        font-size: 0.85rem;
        color: #6b7280;
        margin-bottom: 1.5rem;
    }

    /* ---- Combobox proveedor ---- */
    .field-group {
        margin-bottom: 1.4rem;
        position: relative;
        max-width: 460px;
    }

    .field-label {
        display: block;
        font-size: 0.82rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.4rem;
    }

    .combo-input-wrap {
        position: relative;
    }

    .combo-input {
        width: 100%;
        padding: 0.65rem 2.2rem 0.65rem 0.85rem;
        font-size: 0.88rem;
        color: #1f2937;
        background: #f9fafb;
        border: 1px solid #d1d5db;
        border-radius: 10px;
        transition: border-color 0.2s ease, background 0.2s ease;
    }

    .combo-input::placeholder { color: #9ca3af; }

    .combo-input:focus {
        outline: none;
        border-color: #16a34a;
        background: #ffffff;
    }

    .combo-clear {
        position: absolute;
        right: 0.6rem;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #9ca3af;
        cursor: pointer;
        font-size: 1.1rem;
        line-height: 1;
        display: none;
        padding: 0.15rem;
    }

    .combo-clear.show { display: block; }
    .combo-clear:hover { color: #ef4444; }

    .combo-list {
        display: none;
        position: absolute;
        z-index: 30;
        top: calc(100% + 6px);
        left: 0;
        right: 0;
        max-height: 300px;
        overflow-y: auto;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    .combo-list.show { display: block; }

    .combo-option {
        padding: 0.6rem 0.85rem;
        font-size: 0.85rem;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.6rem;
        border-bottom: 1px solid #f6f7f8;
    }

    .combo-option:last-child { border-bottom: none; }
    .combo-option:hover, .combo-option.active { background: #f0fdf4; }

    .combo-option-name {
        color: #1f2937;
        font-weight: 500;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .combo-option-id {
        color: #9ca3af;
        font-size: 0.75rem;
        white-space: nowrap;
        flex-shrink: 0;
    }

    .combo-empty {
        padding: 0.85rem;
        font-size: 0.82rem;
        color: #9ca3af;
        text-align: center;
    }

    /* ---- Leyenda de clasificación ---- */
    .legend {
        display: none;
        flex-wrap: wrap;
        gap: 0.55rem 1rem;
        margin-bottom: 1.1rem;
        padding: 0.75rem 1rem;
        background: #f9fafb;
        border: 1px solid #f1f3f5;
        border-radius: 10px;
    }

    .legend.show { display: flex; }

    .legend-item {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.72rem;
        color: #374151;
        white-space: nowrap;
        font-weight: bold;
    }

    .legend-dot {
        width: 15px;
        height: 15px;
        border-radius: 3px;
        display: inline-block;
        flex-shrink: 0;
    }

    /* ---- Tarjetas resumen ---- */
    .summary-grid {
        display: none;
        grid-template-columns: repeat(6, 1fr);
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 1.6rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .summary-grid.show { display: grid; }

    .summary-card {
        padding: 1rem 1.2rem;
        color: #ffffff;
    }

    .summary-card .amount {
        font-size: 1.2rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .summary-card .label {
        font-size: 0.74rem;
        opacity: 0.92;
        margin-top: 0.2rem;
    }

    .summary-deuda     { background: #0d9488; }
    .summary-favor     { background: #84cc16; }
    .summary-saldo     { background: #16a34a; }
    .summary-vencido   { background: #dc2626; }
    .summary-porvencer { background: #f59e0b; }
    .summary-documents { background: #0bdaf5; }

    /* ---- Tabla de documentos ---- */
    .docs-wrap { display: none; }
    .docs-wrap.show { display: block; }

    .docs-scroll {
        overflow-x: auto;
        border: 1px solid #f1f3f5;
        border-radius: 12px;
    }

    .docs-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.83rem;
        min-width: 720px;
    }

    .docs-table thead th {
        text-align: left;
        font-size: 0.7rem;
        font-weight: 700;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        padding: 0.7rem 0.8rem;
        background: #f9fafb;
        border-bottom: 1px solid #f1f3f5;
        position: sticky;
        top: 0;
    }

    .docs-table td {
        padding: 0.65rem 0.8rem;
        border-bottom: 1px solid #f1f3f5;
        color: #374151;
        vertical-align: middle;
    }

    .docs-table tbody tr:last-child td { border-bottom: none; }
    .docs-table tbody tr.selectable { cursor: pointer; }
    .docs-table tbody tr.selectable:hover { filter: brightness(0.98); }

    .row-nov1 { background: #fecaca; }
    .row-nov2 { background: #bbf7d0; }
    .row-nov3 { background: #bfdbfe; }
    .row-nov4 { background: #fed7aa; }
    .row-nov5 { background: #fef08a; }
    .row-des1 { background: #e9d5ff; }

    .prefix-tag {
        display: inline-flex;
        padding: 0.15rem 0.5rem;
        border-radius: 6px;
        font-size: 0.7rem;
        font-weight: 700;
        background: #f3f4f6;
        color: #374151;
        white-space: nowrap;
    }
    .row-nov1 .prefix-tag { background: #fca5a5; color: #7f1d1d; }
    .row-nov2 .prefix-tag { background: #86efac; color: #14532d; }
    .row-nov3 .prefix-tag { background: #93c5fd; color: #1e3a8a; }
    .row-nov4 .prefix-tag { background: #fdba74; color: #7c2d12; }
    .row-nov5 .prefix-tag { background: #fde047; color: #713f12; }
    .row-des1 .prefix-tag { background: #d8b4fe; color: #581c87; }

    .badge {
        display: inline-flex;
        padding: 0.2rem 0.55rem;
        border-radius: 999px;
        font-size: 0.7rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .badge-ok        { background: #f0fdf4; color: #166534; }
    .badge-porvencer { background: #fff7ed; color: #c2410c; }
    .badge-v1        { background: #fef9c3; color: #854d0e; }
    .badge-v2        { background: #ffedd5; color: #9a3412; }
    .badge-v3        { background: #fee2e2; color: #991b1b; }
    .badge-v4        { background: #fecaca; color: #7f1d1d; }

    input.row-check {
        width: 16px;
        height: 16px;
        cursor: pointer;
    }

    input.row-check:disabled {
        cursor: not-allowed;
        opacity: 0.3;
    }

    /* ---- Barra de totales ---- */
    .totals-bar {
        display: none;
        position: sticky;
        bottom: 1rem;
        margin-top: 1.3rem;
        background: #111827;
        color: #fff;
        border-radius: 12px;
        padding: 0.95rem 1.4rem;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 12px 28px rgba(0, 0, 0, 0.25);
    }

    .totals-bar.show { display: flex; }

    .totals-bar .count {
        font-size: 0.8rem;
        color: #d1d5db;
    }

    .totals-bar .amount {
        font-size: 1.15rem;
        font-weight: 700;
    }

    .empty-state {
        text-align: center;
        color: #9ca3af;
        font-size: 0.85rem;
        padding: 2.75rem 0;
    }

    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.85rem;
        font-weight: 600;
        color: #4f46e5;
        text-decoration: none;
        margin-top: 1.4rem;
    }

    .back-link:hover { text-decoration: underline; }
    .back-link svg { width: 15px; height: 15px; }

    @media (max-width: 768px) {
        .wrapper { padding: 0; }

        .card {
            padding: 1.3rem 1.1rem;
            border-radius: 12px;
        }

        .title { font-size: 1.05rem; }
        .subtitle { font-size: 0.8rem; margin-bottom: 1.2rem; }

        .field-group { max-width: 100%; }

        /* 5 tarjetas → 2 columnas, se acomodan en filas */
        .summary-grid {
            grid-template-columns: repeat(2, 1fr);
            border-radius: 10px;
        }

        .summary-card { padding: 0.85rem 0.7rem; }
        .summary-card .amount { font-size: 1.05rem; }
        .summary-card .label { font-size: 0.7rem; }

        .legend {
            gap: 0.45rem 0.7rem;
            padding: 0.65rem 0.8rem;
        }

        .legend-item { font-size: 0.68rem; }
        .legend-dot { width: 12px; height: 12px; }

        /* La tabla sigue con scroll horizontal (ya lo tenías con .docs-scroll) */
        .docs-table { min-width: 640px; font-size: 0.8rem; }

        .docs-table thead th {
            font-size: 0.72rem;
            padding: 0.6rem 0.6rem;
        }

        .docs-table td { padding: 0.55rem 0.6rem; }

        /* Barra de totales apilada, ocupa todo el ancho */
        .totals-bar {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.35rem;
            bottom: 0.5rem;
            border-radius: 10px;
            padding: 0.8rem 1.1rem;
        }

        .totals-bar .amount { font-size: 1.05rem; }
    }

    @media (max-width: 480px) {
        body { padding: 1rem 0.6rem; }

        /* En pantallas muy chicas, 1 columna por tarjeta */
        .summary-grid { grid-template-columns: repeat(2, 1fr); }

        .summary-card .amount { font-size: 0.95rem; }

        .combo-input { font-size: 0.85rem; }

        .docs-table { min-width: 560px; }
    }
</style>
</head>
<body>

<div class="wrapper">
    <div class="card">
        <div class="title">Cuentas por pagar</div>
        <div class="subtitle">
            Busca un proveedor para ver su saldo general y el detalle de facturas, novedades y descuentos.
        </div>

        <div class="field-group">
            <label class="field-label" for="providerSearch">Proveedor</label>
            <div class="combo-input-wrap">
                <input
                    type="text"
                    id="providerSearch"
                    class="combo-input"
                    placeholder="Busca por nombre o identificación..."
                    autocomplete="off"
                >
                <button type="button" class="combo-clear" id="providerClear">&times;</button>
                <div class="combo-list" id="providerList"></div>
            </div>
        </div>

        <div class="legend" id="legend">
            <span class="legend-item"><span class="legend-dot" style="background:#dc2626"></span>NOV1 · Faltantes</span>
            <span class="legend-item"><span class="legend-dot" style="background:#16a34a"></span>NOV2 · Sobrantes</span>
            <span class="legend-item"><span class="legend-dot" style="background:#2563eb"></span>NOV3 · Trocados</span>
            <span class="legend-item"><span class="legend-dot" style="background:#f97316"></span>NOV4 · Corrección de factura</span>
            <span class="legend-item"><span class="legend-dot" style="background:#eab308"></span>NOV5 · Mcia. mal estado</span>
            <span class="legend-item"><span class="legend-dot" style="background:#a855f7"></span>DES1 · Descuento</span>
        </div>

        <div class="summary-grid" id="summaryGrid">
            <div class="summary-card summary-deuda">
                <div class="amount" id="sumDeuda">$0</div>
                <div class="label">Deuda por pagar</div>
            </div>
            <div class="summary-card summary-favor">
                <div class="amount" id="sumFavor">$0</div>
                <div class="label">Valor a favor</div>
            </div>
            <div class="summary-card summary-saldo">
                <div class="amount" id="sumSaldo">$0</div>
                <div class="label">Saldo proveedor</div>
            </div>
            <div class="summary-card summary-vencido">
                <div class="amount" id="sumVencido">$0</div>
                <div class="label">Vencido</div>
            </div>
            <div class="summary-card summary-porvencer">
                <div class="amount" id="sumPorVencer">$0</div>
                <div class="label">Por vencer</div>
            </div>
            <div class="summary-card summary-documents">
                <div class="amount" id="sumDocumentos">0</div>
                <div class="label">Documentos</div>
            </div>
        </div>

        <div class="docs-wrap" id="docsWrap">
            <div class="docs-scroll">
                <table class="docs-table">
                    <thead>
                        <tr>
                            <th style="width:34px;"><input type="checkbox" id="checkAll" class="row-check"></th>
                            <th>Tipo</th>
                            <th>Documento</th>
                            <th>Fecha vence</th>
                            <th>Estado</th>
                            <th style="text-align:right;">Deuda</th>
                            <th style="text-align:right;">Saldo</th>
                        </tr>
                    </thead>
                    <tbody id="docsBody"></tbody>
                </table>
            </div>
        </div>

        <div id="emptyState" class="empty-state">Selecciona un proveedor para ver sus cuentas.</div>
    </div>

    <div class="totals-bar" id="totalsBar">
        <div class="count"><span id="selCount">0</span> documento(s) seleccionado(s)</div>
        <div class="amount">Total a pagar: <span id="selTotal">$0</span></div>
    </div>

    <a href="{{ route('home') }}" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Volver a acciones disponibles
    </a>
</div>

<script>
const PROVIDERS = @json(array_values($providers ?? []), JSON_UNESCAPED_UNICODE);

(function () {
    const providers    = PROVIDERS;
    const searchInput  = document.getElementById('providerSearch');
    const clearBtn     = document.getElementById('providerClear');
    const listEl       = document.getElementById('providerList');
    const legend       = document.getElementById('legend');
    const summaryGrid  = document.getElementById('summaryGrid');
    const docsWrap     = document.getElementById('docsWrap');
    const docsBody     = document.getElementById('docsBody');
    const emptyState   = document.getElementById('emptyState');
    const totalsBar    = document.getElementById('totalsBar');
    const checkAll     = document.getElementById('checkAll');

    let filtered = [];
    let activeIndex = -1;

    // Prefijos que colorean la fila (clasificación de novedades)
    const ROW_CLASS_BY_PREFIX = {
        NOV1: 'row-nov1',
        NOV2: 'row-nov2',
        NOV3: 'row-nov3',
        NOV4: 'row-nov4',
        NOV5: 'row-nov5',
        DES1: 'row-des1',
    };

    function escapeHtml(str) {
        return String(str ?? '').replace(/[&<>"']/g, (m) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
        }[m]));
    }

    function formatMoney(value) {
        value = Number(value) || 0;
        return value.toLocaleString('es-CO', {
            style: 'currency',
            currency: 'COP',
            maximumFractionDigits: 2,
        });
    }

    function formatDate(value) {
        if (!value) return '-';
        const d = new Date(value);
        if (isNaN(d)) return value;
        return d.toLocaleDateString('es-CO');
    }

    // Solo FC (facturas) y DES* (descuentos) pueden marcarse; las NOV* son informativas
    function isSelectable(prefix) {
        prefix = (prefix || '').toUpperCase();
        return prefix === 'FC' || prefix.startsWith('DES');
    }

    function rowClassFor(prefix) {
        return ROW_CLASS_BY_PREFIX[(prefix || '').toUpperCase()] || '';
    }

    function estadoBadge(doc) {
        if (Number(doc.VencidoMasDe90) > 0)  return '<span class="badge badge-v4">Vencido +90</span>';
        if (Number(doc.VencidoDe61a90) > 0)  return '<span class="badge badge-v3">Vencido 61-90</span>';
        if (Number(doc.VencidoDe31a60) > 0)  return '<span class="badge badge-v2">Vencido 31-60</span>';
        if (Number(doc.VencidoDe1a30) > 0)   return '<span class="badge badge-v1">Vencido 1-30</span>';
        if (Number(doc.PorVencer) > 0)       return '<span class="badge badge-porvencer">Por vencer</span>';
        return '<span class="badge badge-ok">Al día</span>';
    }

    // ---- Combobox ----
    function openList() {
        const q = searchInput.value.trim().toLowerCase();
        const items = q
            ? providers.filter((p) =>
                (p.FullName || '').toLowerCase().includes(q) ||
                String(p.Identification || '').toLowerCase().includes(q)
              )
            : providers;

        renderList(items.slice(0, 50));
    }

    function renderList(items) {
        filtered = items;
        activeIndex = -1;

        if (!items.length) {
            listEl.innerHTML = '<div class="combo-empty">Sin resultados</div>';
            listEl.classList.add('show');
            return;
        }

        listEl.innerHTML = items.map((p, i) => `
            <div class="combo-option" data-index="${i}">
                <span class="combo-option-name">${escapeHtml(p.FullName)}</span>
                <span class="combo-option-id">${escapeHtml(p.Identification)}</span>
            </div>
        `).join('');

        listEl.classList.add('show');
    }

    function highlight() {
        const options = listEl.querySelectorAll('.combo-option');
        options.forEach((o, i) => o.classList.toggle('active', i === activeIndex));
        if (options[activeIndex]) options[activeIndex].scrollIntoView({ block: 'nearest' });
    }

    searchInput.addEventListener('focus', openList);

    searchInput.addEventListener('input', () => {
        clearBtn.classList.toggle('show', searchInput.value.length > 0);
        openList();
    });

    searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIndex = Math.min(activeIndex + 1, filtered.length - 1);
            highlight();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIndex = Math.max(activeIndex - 1, 0);
            highlight();
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (activeIndex >= 0 && filtered[activeIndex]) selectProvider(filtered[activeIndex]);
        } else if (e.key === 'Escape') {
            listEl.classList.remove('show');
        }
    });

    listEl.addEventListener('click', (e) => {
        const opt = e.target.closest('.combo-option');
        if (!opt) return;
        selectProvider(filtered[Number(opt.dataset.index)]);
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.field-group')) listEl.classList.remove('show');
    });

    clearBtn.addEventListener('click', () => {
        searchInput.value = '';
        clearBtn.classList.remove('show');
        listEl.classList.remove('show');
        resetView();
    });

    function selectProvider(provider) {
        if (!provider) return;
        searchInput.value = provider.FullName;
        clearBtn.classList.add('show');
        listEl.classList.remove('show');
        renderProvider(provider);
    }

    // ---- Render del proveedor seleccionado ----
    function resetView() {
        summaryGrid.classList.remove('show');
        legend.classList.remove('show');
        docsWrap.classList.remove('show');
        totalsBar.classList.remove('show');
        emptyState.style.display = 'block';
        docsBody.innerHTML = '';
    }

    function renderProvider(provider) {
        emptyState.style.display = 'none';

        const vencido = (Number(provider.Expired1to30) || 0)
            + (Number(provider.Expired31to60) || 0)
            + (Number(provider.Expired61to90) || 0)
            + (Number(provider.ExpiredMoreTo91) || 0);

        const saldo = (Number(provider.TotalBalance) || 0) - (Number(provider.BalanceInFavor) || 0);

        document.getElementById('sumDeuda').textContent     = formatMoney(provider.TotalBalance);
        document.getElementById('sumFavor').textContent     = formatMoney(provider.BalanceInFavor);
        document.getElementById('sumSaldo').textContent     = formatMoney(saldo);
        document.getElementById('sumVencido').textContent   = formatMoney(vencido);
        document.getElementById('sumPorVencer').textContent = formatMoney(provider.BalanceToExpire);
        document.getElementById('sumDocumentos').textContent = provider.Documents.length;

        summaryGrid.classList.add('show');
        legend.classList.add('show');
        docsWrap.classList.add('show');

        const docs = provider.Documents || [];

        if (!docs.length) {
            docsBody.innerHTML = '<tr><td colspan="7" class="empty-state">Este proveedor no tiene documentos.</td></tr>';
        } else {
            docsBody.innerHTML = docs.map((doc) => {
                const selectable = isSelectable(doc.DuePrefix);
                const rowClass = rowClassFor(doc.DuePrefix);

                return `
                    <tr class="${rowClass} ${selectable ? 'selectable' : ''}" data-saldo="${Number(doc.Saldo) || 0}">
                        <td><input type="checkbox" class="row-check doc-check" ${selectable ? '' : 'disabled'}></td>
                        <td><span class="prefix-tag">${escapeHtml(doc.DuePrefix)}</span></td>
                        <td>${escapeHtml(doc.DueName)}</td>
                        <td>${formatDate(doc.DueDate)}</td>
                        <td>${estadoBadge(doc)}</td>
                        <td style="text-align:right;">${formatMoney(doc.Deuda)}</td>
                        <td style="text-align:right;">${formatMoney(doc.Saldo)}</td>
                    </tr>
                `;
            }).join('');
        }

        checkAll.checked = false;
        updateTotals();
    }

    // ---- Selección y totales ----
    docsBody.addEventListener('click', (e) => {
        const row = e.target.closest('tr.selectable');
        if (!row || e.target.classList.contains('doc-check')) return;

        const cb = row.querySelector('.doc-check');
        if (cb && !cb.disabled) {
            cb.checked = !cb.checked;
            updateTotals();
        }
    });

    docsBody.addEventListener('change', (e) => {
        if (e.target.classList.contains('doc-check')) updateTotals();
    });

    checkAll.addEventListener('change', () => {
        docsBody.querySelectorAll('.doc-check:not(:disabled)').forEach((cb) => {
            cb.checked = checkAll.checked;
        });
        updateTotals();
    });

    function updateTotals() {
        const checked = docsBody.querySelectorAll('.doc-check:checked');
        let total = 0;

        checked.forEach((cb) => {
            const row = cb.closest('tr');
            total += Number(row.dataset.saldo) || 0;
        });

        document.getElementById('selCount').textContent = checked.length;
        document.getElementById('selTotal').textContent = formatMoney(total);
        totalsBar.classList.toggle('show', checked.length > 0);
    }

    resetView();
})();
</script>

</body>
</html>
