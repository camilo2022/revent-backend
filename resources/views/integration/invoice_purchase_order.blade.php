<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Órdenes de compra</title>
<style>
    * { box-sizing: border-box; }

    body {
        background: #f3f4f6;
        font-family: 'Segoe UI', system-ui, sans-serif;
        margin: 0;
        padding: 2rem 1rem;
    }

    .wrapper { max-width: 95%; margin: 0 auto; }

    .card {
        background: #ffffff;
        border: 1px solid #eef0f2;
        border-radius: 16px;
        padding: 1.9rem 2.1rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    }

    .title { font-size: 1.5rem; font-weight: 700; color: #1f2937; margin-bottom: 0.3rem; }
    .subtitle { font-size: 0.85rem; color: #6b7280; margin-bottom: 1.5rem; }

    /* ---- Toolbar ---- */
    .toolbar {
        display: flex;
        gap: 0.8rem;
        margin-bottom: 1.1rem;
        align-items: center;
        flex-wrap: wrap;
    }

    .combo-input {
        padding: 0.65rem 0.85rem;
        font-size: 0.88rem;
        color: #1f2937;
        background: #f9fafb;
        border: 1px solid #d1d5db;
        border-radius: 10px;
        transition: border-color 0.2s ease, background 0.2s ease;
    }

    .combo-input::placeholder { color: #9ca3af; }
    .combo-input:focus { outline: none; border-color: #16a34a; background: #ffffff; }
    .toolbar input.combo-input { width: 100%; max-width: 480px; }
    .toolbar select.combo-input { max-width: 220px; cursor: pointer; }

    .btn-toggle-all {
        background: #eef2ff;
        border: 1px solid #c7d2fe;
        border-radius: 10px;
        padding: 0.62rem 0.95rem;
        font-size: 0.82rem;
        font-weight: 600;
        color: #4338ca;
        cursor: pointer;
        white-space: nowrap;
    }

    .btn-toggle-all:hover { background: #e0e7ff; }

    .result-count { margin-left: auto; font-size: 0.8rem; color: #6b7280; }

    /* ---- Tarjetas resumen ---- */
    .summary-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 1.6rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .summary-card { padding: 1rem 1.2rem; color: #ffffff; }
    .summary-card .amount { font-size: 1.2rem; font-weight: 700; white-space: nowrap; }
    .summary-card .label { font-size: 0.74rem; opacity: 0.92; margin-top: 0.2rem; }

    .summary-orders    { background: #283593; }
    .summary-requested { background: #3F51B5; }
    .summary-received  { background: #5C6BC0; }
    .summary-pending   { background: #7986CB; }

    /* ---- Contenedor con scroll (los encabezados sticky se calculan contra este) ---- */
    .docs-scroll {
        overflow: auto;
        border: 1px solid #f1f3f5;
        border-radius: 12px;
    }

    .docs-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.83rem;
    }

    .main-table { min-width: 1750px; }

    .docs-table td {
        padding: 0.65rem 0.8rem;
        border-bottom: 1px solid #f1f3f5;
        color: #374151;
        vertical-align: middle;
        background: #ffffff;
    }

    /* ---- Encabezado principal: 2 niveles ---- */
    /* Se pega TODO el thead como bloque (en vez de cada <tr> por separado):
       con celdas rowspan="2", pegar cada fila con un "top" calculado por JS
       hace que el grupo (PROVEEDOR/CANTIDAD/FACTURAS) se desalinee y desaparezca
       al hacer scroll. Pegando el thead completo se evita ese problema. */
    .main-table thead {
        position: sticky;
        top: 0;
        z-index: 5;
    }

    .main-table thead th {
        text-align: center;
        font-weight: 700;
        letter-spacing: 0.03em;
        text-transform: uppercase;
        padding: 0.7rem 0.8rem;
        vertical-align: middle;
        border-right: 1px solid #e5e7eb;
        box-shadow: inset 0 -1px 0 #e5e7eb;
    }

    .main-table thead th:last-child { border-right: none; }

    /* Nivel 1: gris oscuro */
    .main-table thead tr:first-child th {
        font-size: 0.7rem;
        color: #374151;
        background: #f3f4f6;
    }

    /* Nivel 2 */
    .main-table thead tr:nth-child(2) th {
        font-size: 0.7rem;
        color: #374151;
        background: #f3f4f6;
    }

    /* Cada OC es un <tbody> */
    tbody.oc-group { border-top: 2px solid #e5e7eb; }
    tbody.oc-group:first-of-type { border-top: none; }
    tbody.oc-group:hover > tr:not(.oc-details-row) > td { background: #f0fdf4; }

    .invoice-empty {
        text-align: center;
        color: #9ca3af !important;
        font-size: 0.78rem;
        font-style: italic;
    }

    .oc-details-row > td { background: #f9fafb; padding: 1rem 1.2rem 1.2rem 3rem; }

    /* ---- Botón desplegar ---- */
    .btn-expand {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        padding: 0;
        border: none;
        outline: none;
        background: transparent;
        color: #64748b;
        cursor: pointer;
        border-radius: 50%;
        transition: all 0.2s ease;
    }

    .btn-expand:hover { background: #f1f5f9; color: #2563eb; }
    .btn-expand .expand-icon { width: 20px; height: 20px; transition: transform 0.2s ease; }
    .btn-expand.open .expand-icon { transform: rotate(90deg); }

    /* ---- Tabla de items (dentro del detalle) ---- */
    .items-title { font-size: 0.8rem; font-weight: 700; color: #374151; margin-bottom: 0.6rem; }

    .items-scroll {
        overflow-x: auto;
        border: 1px solid #f1f3f5;
        border-radius: 10px;
        background: #ffffff;
    }

    .items-table { min-width: 1200px; font-size: 0.8rem; }

    .items-table thead th {
        text-align: center;
        font-size: 0.7rem;
        font-weight: 700;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        padding: 0.7rem 0.8rem;
        background: #f9fafb;
        border-bottom: 1px solid #f1f3f5;
    }

    .items-table td { text-align: center; }
    .items-table td.text-right { text-align: right; }
    .items-table tr.item-start > td { border-top: 2px solid #eef0f2; }
    .items-table tbody tr:first-child.item-start > td { border-top: none; }

    /* ---- Links / tags / badges ---- */
    .document-link {
        color: #2563eb;
        text-decoration: underline;
        cursor: pointer;
        font-weight: 600;
        white-space: nowrap;
    }

    .document-link:hover { color: #1d4ed8; }

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

    .badge {
        display: inline-flex;
        padding: 0.2rem 0.55rem;
        border-radius: 999px;
        font-size: 0.7rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .badge-ok      { background: #f0fdf4; color: #166534; }
    .badge-invalid { background: #fee2e2; color: #991b1b; }

    /* Cantidad recibida: igual = verde, menor (≠0) = naranja, mayor = azul, 0 = gris */
    .cover-full    { color: #16a34a !important; font-weight: 700; }
    .cover-partial { color: #c2410c !important; font-weight: 700; }
    .cover-over    { color: #2563eb !important; font-weight: 700; }
    .cover-none    { color: #9ca3af !important; font-weight: 600; }

    .obs-text {
        white-space: pre-line;
        font-size: 0.7rem;
        color: #6b7280;
        width: 220px;
        max-width: 220px;
        max-height: 120px;
        overflow-y: auto;
        overflow-x: hidden;
        padding-right: 6px;
    }

    .obs-text::-webkit-scrollbar { width: 5px; }
    .obs-text::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    .obs-text::-webkit-scrollbar-track { background: transparent; }

    .provider-name { font-weight: 600; color: #374151; }
    .provider-company { font-size: 0.72rem; color: #9ca3af; }

    .text-center { text-align: center !important; }
    .text-right { text-align: right !important; }
    .nowrap { white-space: nowrap; }
    .quantity { font-weight: 600; text-align: center; }

    .empty-state {
        text-align: center;
        color: #9ca3af;
        font-size: 0.85rem;
        padding: 2.75rem 0;
    }

    /* ---- Loading / error (igual que en la tabla de proveedores) ---- */
    .loading-state {
        display: none;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.9rem;
        padding: 3.5rem 0;
    }

    .loading-state.show { display: flex; }

    .spinner {
        width: 34px;
        height: 34px;
        border: 3px solid #e5e7eb;
        border-top-color: #16a34a;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin { to { transform: rotate(360deg); } }

    .loading-text {
        font-size: 0.85rem;
        color: #6b7280;
        font-weight: 500;
    }

    .error-state {
        text-align: center;
        color: #dc2626;
        font-size: 0.85rem;
        padding: 2.75rem 0;
    }

    .btn-secondary {
        background: #f3f4f6;
        color: #374151;
        border: 1px solid #e5e7eb;
        padding: 0.5rem 0.9rem;
        border-radius: 8px;
        font-size: 0.78rem;
        font-weight: 600;
        cursor: pointer;
    }

    .btn-secondary:hover { background: #e5e7eb; }

    /* ---- Paginación (igual que en la tabla de proveedores) ---- */
    .pagination {
        display: none;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.8rem;
        margin-top: 1rem;
        font-size: 0.8rem;
        color: #6b7280;
    }

    .pagination-buttons { display: flex; gap: 0.3rem; align-items: center; }

    .page-btn {
        min-width: 34px;
        height: 34px;
        padding: 0 0.5rem;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        font-size: 0.8rem;
        font-weight: 600;
        color: #374151;
        cursor: pointer;
    }

    .page-btn:hover:not(:disabled) {
        background: #f0fdf4;
        border-color: #16a34a;
        color: #16a34a;
    }

    .page-btn.active { background: #16a34a; border-color: #16a34a; color: #fff; }
    .page-btn:disabled { opacity: 0.4; cursor: not-allowed; }
    .page-dots { padding: 0 0.3rem; color: #9ca3af; }

    .btn-toggle-all {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #eef2ff;
        border: 1px solid #c7d2fe;
        border-radius: 10px;
        padding: 0.62rem 0.7rem;
        color: #4338ca;
        cursor: pointer;
        transition: background 0.2s ease;
    }

    .btn-toggle-all:hover { background: #e0e7ff; }

    .btn-toggle-all svg {
        width: 15px;
        height: 15px;
        flex-shrink: 0;
    }

    @media (max-width: 768px) {
        body { padding: 1rem 0.6rem; }
        .card { padding: 1.3rem 1.1rem; border-radius: 12px; }
        .title { font-size: 1.3rem; }
        .summary-grid { grid-template-columns: repeat(2, 1fr); }
        .summary-card .amount { font-size: 1rem; }
        .main-table { min-width: 1500px; }
        .result-count { margin-left: 0; }
    }
</style>
</head>
<body>

<div class="wrapper">
    <div class="card">

        <div class="title">Órdenes de compra</div>
        <div class="subtitle">Órdenes de compra y facturas donde se recibió cada cantidad.</div>

        <div class="summary-grid">
            <div class="summary-card summary-orders">
                <div class="amount" id="sumOrders">0</div>
                <div class="label">Órdenes de compra</div>
            </div>
            <div class="summary-card summary-requested">
                <div class="amount" id="sumRequested">0</div>
                <div class="label">Cantidad solicitada</div>
            </div>
            <div class="summary-card summary-received">
                <div class="amount" id="sumReceived">0</div>
                <div class="label">Cantidad recibida</div>
            </div>
            <div class="summary-card summary-pending">
                <div class="amount" id="sumPending">0</div>
                <div class="label">Cantidad pendiente</div>
            </div>
        </div>

        <div class="toolbar">
            <input type="text" id="ocSearch" class="combo-input" placeholder="Filtrar por OC, proveedor, identificación o factura..." autocomplete="off">

            <select id="ocStatus" class="combo-input">
                <option value="">Todos los estados</option>
                <option value="completa">Recibidas completas</option>
                <option value="pendiente">Con pendientes</option>
                <option value="sin_factura">Sin factura</option>
            </select>

            <select id="ocPageSize" class="combo-input" style="max-width: 110px;">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>

            <button type="button" class="btn-toggle-all" id="btnToggleAll" title="Expandir todo" aria-label="Expandir todo">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="7 13 12 18 17 13"/>
                    <polyline points="7 6 12 11 17 6"/>
                </svg>
            </button>
        </div>

        <div class="loading-state" id="ocLoadingState">
            <div class="spinner"></div>
            <div class="loading-text">Cargando órdenes de compra...</div>
        </div>

        <div class="error-state" id="ocErrorState" style="display:none;">
            No se pudieron cargar las órdenes de compra.
            <button type="button" class="btn-secondary" id="ocRetryBtn" style="margin-left:.6rem;">Reintentar</button>
        </div>

        <div class="docs-scroll" id="ocScroll" style="display:none;">
            <table class="docs-table main-table" id="ocTable">
                <thead>
                    <tr>
                        <th rowspan="2" style="width:50px;"></th>
                        <th rowspan="2">N° Orden</th>
                        <th rowspan="2">Valida</th>
                        <th rowspan="2">Fecha</th>
                        <th rowspan="2">Usuario</th>
                        <th colspan="3">Proveedor</th>
                        <th rowspan="2">Fecha Límite</th>
                        <th rowspan="2">Observación</th>
                        <th rowspan="2">Bodega</th>
                        <th colspan="2">Cantidad</th>
                        <th colspan="4">Facturas</th>
                    </tr>
                    <tr>
                        <th>Razón Social</th>
                        <th>Nombre Comercial</th>
                        <th>Identificación</th>
                        <th>Solicitada</th>
                        <th>Recibida</th>
                        <th>N° Factura</th>
                        <th>Documento</th>
                        <th>Fecha</th>
                        <th>Usuario</th>
                    </tr>
                </thead>
            </table>
        </div>

        <div class="pagination" id="ocPagination">
            <span id="ocPaginationInfo"></span>
            <div class="pagination-buttons" id="ocPages"></div>
        </div>

    </div>
</div>

<script>
(function () {
    const DOCUMENTS_URL = @json(route('siigo.invoice_purchase_order_documents'));

    const table          = document.getElementById('ocTable');
    const scrollWrap     = document.getElementById('ocScroll');
    const loadingStateEl = document.getElementById('ocLoadingState');
    const errorStateEl   = document.getElementById('ocErrorState');
    const retryBtn       = document.getElementById('ocRetryBtn');
    const searchInput    = document.getElementById('ocSearch');
    const statusSel      = document.getElementById('ocStatus');
    const pageSizeSel    = document.getElementById('ocPageSize');
    const btnAll         = document.getElementById('btnToggleAll');
    const paginationEl   = document.getElementById('ocPagination');
    const paginationInfo = document.getElementById('ocPaginationInfo');
    const pagesEl        = document.getElementById('ocPages');

    const sumOrdersEl    = document.getElementById('sumOrders');
    const sumRequestedEl = document.getElementById('sumRequested');
    const sumReceivedEl  = document.getElementById('sumReceived');
    const sumPendingEl   = document.getElementById('sumPending');

    let groups = [];   // tbody.oc-group actuales en el DOM
    let allOpen = false;
    let ocPage = 1;
    let ocPageSize = Number(pageSizeSel.value) || 10;

    /* ---------------- Helpers de formato (equivalentes a los de Blade) ---------------- */

    function esc(v) {
        if (v === null || v === undefined) return '';
        return String(v)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatThousands(n) {
        const neg = n < 0;
        n = Math.abs(Math.round(n));
        const s = String(n).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        return neg ? '-' + s : s;
    }

    function money(v) {
        return '$' + formatThousands(Number(v) || 0);
    }

    function qtyFmt(v) {
        return formatThousands(Number(v) || 0);
    }

    function dateFmt(v) {
        if (!v) return '-';
        const isoMatch = String(v).match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (isoMatch) return `${isoMatch[3]}/${isoMatch[2]}/${isoMatch[1]}`;
        const d = new Date(v);
        if (isNaN(d.getTime())) return '-';
        const dd = String(d.getDate()).padStart(2, '0');
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        return `${dd}/${mm}/${d.getFullYear()}`;
    }

    // 0 = gris | menor y distinto de 0 = naranja | igual = verde | mayor = azul
    function receivedClass(received, requested) {
        received = Number(received) || 0;
        requested = Number(requested) || 0;
        if (received <= 0) return 'cover-none';
        if (received < requested) return 'cover-partial';
        if (received > requested) return 'cover-over';
        return 'cover-full';
    }

    function pendingClass(pending) {
        pending = Number(pending) || 0;
        if (pending > 0) return 'cover-partial';
        if (pending < 0) return 'cover-over';
        return 'cover-full';
    }

    /* ---------------- Construcción de filas ---------------- */

    function renderInvoiceCells(invoice) {
        if (invoice) {
            return `
                <td>
                    <a href="${esc(invoice.Link || '#')}" target="_blank" rel="noopener noreferrer" class="document-link" title="Abrir factura en Siigo">
                        ${esc(invoice.DocName || '-')}
                    </a>
                </td>
                <td class="nowrap"><strong>${esc(invoice.ExternalDocumentNumber || '-')}</strong></td>
                <td class="nowrap">${dateFmt(invoice.DocDate)}</td>
                <td class="nowrap">${esc(invoice.User)}</td>
            `;
        }
        return `<td colspan="4" class="invoice-empty">Sin factura asociada</td>`;
    }

    function renderOrderMainCells(order, ocIndex, rowspan, requested, confirmed) {
        const validBadge = !order.IsAnnulled
            ? `<span class="badge badge-ok">Válida</span>`
            : `<span class="badge badge-invalid">Anulada</span>`;

        return `
            <td rowspan="${rowspan}" class="text-center">
                <button type="button" class="btn-expand" data-target="oc-details-${ocIndex}" title="Mostrar items" aria-label="Mostrar items">
                    <svg class="expand-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </td>
            <td rowspan="${rowspan}">
                <a href="${esc(order.Link || '#')}" target="_blank" rel="noopener noreferrer" class="document-link" title="Abrir orden de compra en Siigo">
                    ${esc(order.DocName || '-')}
                </a>
            </td>
            <td rowspan="${rowspan}" class="text-center">${validBadge}</td>
            <td rowspan="${rowspan}" class="nowrap">${dateFmt(order.DocDate)}</td>
            <td rowspan="${rowspan}" class="nowrap">${esc(order.User)}</td>
            <td rowspan="${rowspan}"><div class="provider-name">${esc(order.FullName || '-')}</div></td>
            <td rowspan="${rowspan}"><div class="provider-company">${order.CompanyName ? esc(order.CompanyName) : '-'}</div></td>
            <td rowspan="${rowspan}" class="nowrap">${esc(order.Identification || '-')}</td>
            <td rowspan="${rowspan}" class="nowrap">${dateFmt(order.DeadlineDate)}</td>
            <td rowspan="${rowspan}">${order.Observations ? `<div class="obs-text">${esc(order.Observations)}</div>` : '-'}</td>
            <td rowspan="${rowspan}">${order.Warehouse ? esc(order.Warehouse) : '-'}</td>
            <td rowspan="${rowspan}" class="quantity">${qtyFmt(requested)}</td>
            <td rowspan="${rowspan}" class="quantity"><span class="${receivedClass(confirmed, requested)}">${qtyFmt(confirmed)}</span></td>
        `;
    }

    function buildItemsBody(items) {
        if (!items.length) {
            return `<tr><td colspan="12" class="empty-state">Esta orden no tiene items.</td></tr>`;
        }

        let html = '';

        items.forEach((item) => {
            const itemInvoices = Array.isArray(item.Invoices) ? item.Invoices : [];
            const itemRows = itemInvoices.length ? itemInvoices : [null];
            const itemSpan = itemRows.length;

            const itemQty = Number(item.Quantity) || 0;
            const itemConfirmed = Number(item.Confirmed) || 0;
            const itemPending = (item.Pending !== undefined && item.Pending !== null)
                ? Number(item.Pending)
                : (itemQty - itemConfirmed);

            const pClass = pendingClass(itemPending);

            itemRows.forEach((itemInvoice, idx) => {
                const rowClass = idx === 0 ? 'item-start' : '';
                let cells = '';

                if (idx === 0) {
                    cells += `
                        <td rowspan="${itemSpan}"><span class="prefix-tag">${esc(item.Reference || '-')}</span></td>
                        <td rowspan="${itemSpan}">${esc(item.Color || '-')}</td>
                        <td rowspan="${itemSpan}">${esc(item.Category || '-')}</td>
                        <td rowspan="${itemSpan}">${esc(item.Size || '-')}</td>
                        <td rowspan="${itemSpan}">${item.Warehouse ? esc(item.Warehouse) : '-'}</td>
                        <td rowspan="${itemSpan}" class="text-right">${money(item.UnitValue)}</td>
                        <td rowspan="${itemSpan}" class="text-right">${money(item.Value)}</td>
                        <td rowspan="${itemSpan}" class="quantity">${qtyFmt(itemQty)}</td>
                        <td rowspan="${itemSpan}" class="quantity"><span class="${receivedClass(itemConfirmed, itemQty)}">${qtyFmt(itemConfirmed)}</span></td>
                        <td rowspan="${itemSpan}" class="quantity"><span class="${pClass}">${qtyFmt(itemPending)}</span></td>
                    `;
                }

                if (itemInvoice) {
                    cells += `
                        <td><a href="${esc(itemInvoice.Link || '#')}" target="_blank" rel="noopener noreferrer" class="document-link">${esc(itemInvoice.DocName || '-')}</a></td>
                        <td class="quantity cover-full">${qtyFmt(itemInvoice.Quantity)}</td>
                    `;
                } else {
                    cells += `<td colspan="2" class="invoice-empty">Sin recibir</td>`;
                }

                html += `<tr class="${rowClass}">${cells}</tr>`;
            });
        });

        return html;
    }

    function buildDetailsRow(order, ocIndex, items) {
        return `
            <tr class="oc-details-row" id="oc-details-${ocIndex}" style="display:none;">
                <td colspan="17">
                    <div class="items-title">Items de ${esc(order.DocName || '')} · ${items.length} referencia(s)</div>
                    <div class="items-scroll">
                        <table class="docs-table items-table">
                            <thead>
                                <tr>
                                    <th>Referencia</th><th>Color</th><th>Categoría</th><th>Talla</th><th>Bodega</th>
                                    <th>Valor unit.</th><th>Valor total</th><th>Solicitada</th><th>Recibida</th><th>Pendiente</th>
                                    <th>Factura</th><th>Cant. en factura</th>
                                </tr>
                            </thead>
                            <tbody>${buildItemsBody(items)}</tbody>
                        </table>
                    </div>
                </td>
            </tr>
        `;
    }

    function buildGroupHtml(order, ocIndex) {
        const invoices = Array.isArray(order.Invoices) ? order.Invoices : [];
        const invoiceRows = invoices.length ? invoices : [null];
        const rowspan = invoiceRows.length;

        const requested = Number(order.TotalQuantity) || 0;
        const confirmed = Number(order.TotalConfirmed) || 0;

        const status = invoices.length === 0
            ? 'sin_factura'
            : (confirmed >= requested ? 'completa' : 'pendiente');

        const searchText = [
            order.DocName,
            order.FullName,
            order.CompanyName,
            order.Identification,
            invoices.map(i => i.DocName).filter(Boolean).join(' '),
            invoices.map(i => i.ExternalDocumentNumber).filter(Boolean).join(' '),
        ].filter(Boolean).join(' ').toLowerCase();

        const items = Array.isArray(order.Items) ? order.Items : [];

        let rowsHtml = '';
        invoiceRows.forEach((invoice, idx) => {
            if (idx === 0) {
                rowsHtml += `<tr class="oc-row">${renderOrderMainCells(order, ocIndex, rowspan, requested, confirmed)}${renderInvoiceCells(invoice)}</tr>`;
            } else {
                rowsHtml += `<tr class="oc-row">${renderInvoiceCells(invoice)}</tr>`;
            }
        });

        const detailsHtml = buildDetailsRow(order, ocIndex, items);

        return `<tbody class="oc-group" data-search="${esc(searchText)}" data-status="${status}">${rowsHtml}${detailsHtml}</tbody>`;
    }

    /* ---------------- Resumen ---------------- */

    function renderSummary(purchaseOrders) {
        const totalOrders = purchaseOrders.length;
        let totalQuantity = 0;
        let totalConfirmed = 0;
        let totalPending = 0;

        purchaseOrders.forEach((o) => {
            const req = Number(o.TotalQuantity) || 0;
            const conf = Number(o.TotalConfirmed) || 0;
            totalQuantity += req;
            totalConfirmed += conf;
            totalPending += Math.max(req - conf, 0);
        });

        sumOrdersEl.textContent    = qtyFmt(totalOrders);
        sumRequestedEl.textContent = qtyFmt(totalQuantity);
        sumReceivedEl.textContent  = qtyFmt(totalConfirmed);
        sumPendingEl.textContent   = qtyFmt(totalPending);
    }

    /* ---------------- Render principal ---------------- */

    function clearGroups() {
        table.querySelectorAll('tbody').forEach((el) => el.remove());
    }

    function renderOrders(purchaseOrders) {
        clearGroups();
        renderSummary(purchaseOrders);

        if (!purchaseOrders.length) {
            table.insertAdjacentHTML('beforeend', `
                <tbody>
                    <tr><td colspan="17" class="empty-state">No se encontraron órdenes de compra.</td></tr>
                </tbody>
            `);
            groups = [];
            ocPage = 1;
            applyFilters();
            return;
        }

        const groupsHtml = purchaseOrders.map((order, idx) => buildGroupHtml(order, idx)).join('');

        table.insertAdjacentHTML('beforeend', groupsHtml + `
            <tbody id="ocNoResults" style="display:none;">
                <tr><td colspan="17" class="empty-state">Sin resultados.</td></tr>
            </tbody>
        `);

        groups = Array.from(table.querySelectorAll('tbody.oc-group'));
        ocPage = 1;
        applyFilters();
    }

    /* ---------------- Carga AJAX ---------------- */

    async function loadPurchaseOrders() {
        loadingStateEl.classList.add('show');
        errorStateEl.style.display = 'none';
        scrollWrap.style.display = 'none';
        paginationEl.style.display = 'none';
        clearGroups();

        try {
            const response = await fetch(DOCUMENTS_URL, {
                method: 'GET',
                headers: { 'Accept': 'application/json' },
            });

            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }

            const data = await response.json();

            const purchaseOrders = Array.isArray(data)
                ? data
                : (Array.isArray(data.purchase_orders) ? data.purchase_orders : (Array.isArray(data.data) ? data.data : []));

            renderOrders(purchaseOrders);

            loadingStateEl.classList.remove('show');
            scrollWrap.style.display = 'block';
            paginationEl.style.display = 'flex';
        } catch (err) {
            loadingStateEl.classList.remove('show');
            errorStateEl.style.display = 'block';
        }
    }

    retryBtn.addEventListener('click', loadPurchaseOrders);

    /* ---------------- Expandir / contraer ---------------- */

    function setOpen(btn, open) {
        const row = document.getElementById(btn.dataset.target);
        if (!row) return;
        row.style.display = open ? 'table-row' : 'none';
        btn.classList.toggle('open', open);
    }

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-expand');
        if (!btn) return;
        setOpen(btn, !btn.classList.contains('open'));
    });

    btnAll.addEventListener('click', () => {
        allOpen = !allOpen;
        groups.forEach((g) => {
            if (g.style.display === 'none') return;
            const btn = g.querySelector('.btn-expand');
            if (btn) setOpen(btn, allOpen);
        });
        btnAll.innerHTML = allOpen
            ? `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="7 11 12 6 17 11"/>
                    <polyline points="7 18 12 13 17 18"/>
                </svg>`
            : `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="7 13 12 18 17 13"/>
                    <polyline points="7 6 12 11 17 6"/>
                </svg>`;
        btnAll.title = allOpen ? 'Contraer todo' : 'Expandir todo';
    });

    /* ---------------- Filtros ---------------- */

    function applyFilters() {
        const q = searchInput.value.trim().toLowerCase();
        const status = statusSel.value;

        const matched = groups.filter((g) => (!q || g.dataset.search.includes(q))
            && (!status || g.dataset.status === status));

        const totalPages = Math.max(1, Math.ceil(matched.length / ocPageSize));
        if (ocPage > totalPages) ocPage = totalPages;
        if (ocPage < 1) ocPage = 1;

        const start = (ocPage - 1) * ocPageSize;
        const pageItems = new Set(matched.slice(start, start + ocPageSize));

        groups.forEach((g) => {
            const isMatch = matched.includes(g);
            g.style.display = (isMatch && pageItems.has(g)) ? '' : 'none';
        });

        const noResults = document.getElementById('ocNoResults');
        if (noResults) noResults.style.display = groups.length && !matched.length ? '' : 'none';

        paginationInfo.textContent = matched.length
            ? `Mostrando ${start + 1}-${start + pageItems.size} de ${matched.length}`
            : '0 resultados';

        renderPagination(totalPages);
    }

    function renderPagination(totalPages) {
        const pages = [];
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || Math.abs(i - ocPage) <= 2) pages.push(i);
            else if (pages[pages.length - 1] !== '…') pages.push('…');
        }

        pagesEl.innerHTML =
            `<button type="button" class="page-btn" data-page="${ocPage - 1}" ${ocPage === 1 ? 'disabled' : ''}>‹</button>` +
            pages.map((p) => p === '…'
                ? '<span class="page-dots">…</span>'
                : `<button type="button" class="page-btn ${p === ocPage ? 'active' : ''}" data-page="${p}">${p}</button>`
            ).join('') +
            `<button type="button" class="page-btn" data-page="${ocPage + 1}" ${ocPage === totalPages ? 'disabled' : ''}>›</button>`;
    }

    pagesEl.addEventListener('click', (e) => {
        const btn = e.target.closest('.page-btn');
        if (!btn || btn.disabled) return;
        ocPage = Number(btn.dataset.page);
        applyFilters();
    });

    searchInput.addEventListener('input', () => { ocPage = 1; applyFilters(); });
    statusSel.addEventListener('change', () => { ocPage = 1; applyFilters(); });
    pageSizeSel.addEventListener('change', () => {
        ocPageSize = Number(pageSizeSel.value) || 10;
        ocPage = 1;
        applyFilters();
    });

    /* ---------------- Init ---------------- */

    loadPurchaseOrders();
})();
</script>

</body>
</html>
