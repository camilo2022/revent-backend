<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
        .toolbar input[type="date"].combo-input { width: auto; max-width: 170px; }
        .toolbar select.combo-input { max-width: 220px; cursor: pointer; }

        .filter-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #6b7280;
        }

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

        /* Antigüedad de la OC: tonos suaves, compatibles con el hover verde original. */
        tbody.oc-group.age-green > tr:not(.oc-details-row) > td { background: #f0fdf4; }
        tbody.oc-group.age-orange > tr:not(.oc-details-row) > td { background: #fff7ed; }
        tbody.oc-group.age-red > tr:not(.oc-details-row) > td { background: #fef2f2; }
        tbody.oc-group.age-blue > tr:not(.oc-details-row) > td { background: #eff6ff; }

        tbody.oc-group.age-green:hover > tr:not(.oc-details-row) > td { background: #dcfce7; }
        tbody.oc-group.age-orange:hover > tr:not(.oc-details-row) > td { background: #ffedd5; }
        tbody.oc-group.age-red:hover > tr:not(.oc-details-row) > td { background: #fee2e2; }
        tbody.oc-group.age-blue:hover > tr:not(.oc-details-row) > td { background: #dbeafe; }

        .aging-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 5px;
            vertical-align: middle;
        }
        .aging-indicator.age-green { background: #16a34a; }
        .aging-indicator.age-orange { background: #f97316; }
        .aging-indicator.age-red { background: #dc2626; }
        .aging-indicator.age-blue { background: #2563eb; }

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
        /* Item que coincide con los filtros de referencia / modelo / color / talla */
        .items-table tr.item-match > td { background: #e5e7eb; }
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

        .btn-download-missing {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #dcfce7;
            border: 1px solid #bbf7d0;
            border-radius: 10px;
            padding: 0.62rem 0.7rem;
            color: #15803d;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .btn-download-missing:hover { background: #bbf7d0; }

        .btn-download-missing svg {
            width: 15px;
            height: 15px;
            flex-shrink: 0;
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
            body { padding: 1rem 0.6rem; }
            .card { padding: 1.3rem 1.1rem; border-radius: 12px; }
            .title { font-size: 1.3rem; }
            .summary-grid { grid-template-columns: repeat(2, 1fr); }
            .summary-card .amount { font-size: 1rem; }
            .main-table { min-width: 1500px; }
            .result-count { margin-left: 0; }
        }

        /* ---- Buscador con ícono + botón Filtros ---- */
        .search-box {
            position: relative;
            flex: 1 1 320px;
            min-width: 240px;
        }

        .search-icon {
            position: absolute;
            left: 0.9rem;
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            pointer-events: none;
        }

        .search-input {
            width: 100%;
            height: 44px;
            padding: 0 1rem 0 2.6rem;
            font-size: 0.88rem;
            color: #1f2937;
            background: #f9fafb;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            transition: border-color 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
        }

        .search-input::placeholder { color: #9ca3af; }
        .search-input:focus {
            outline: none;
            border-color: #16a34a;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.12);
        }

        .btn-filters {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            height: 44px;
            padding: 0 1.1rem;
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 700;
            color: #111827;
            cursor: pointer;
            white-space: nowrap;
            transition: background 0.2s ease, border-color 0.2s ease;
        }

        .btn-filters:hover { background: #f9fafb; border-color: #9ca3af; }
        .btn-filters svg { width: 16px; height: 16px; flex-shrink: 0; }

        .filters-badge {
            align-items: center;
            justify-content: center;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            border-radius: 999px;
            background: #16a34a;
            color: #ffffff;
            font-size: 0.7rem;
            font-weight: 700;
        }

        /* ---- Modal de filtros ---- */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 50;
            background: rgba(17, 24, 39, 0.45);
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .modal-overlay.show { display: flex; }

        .modal-box {
            display: flex;
            flex-direction: column;
            width: 100%;
            max-width: 720px;
            max-height: 90vh;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25);
            overflow: hidden;
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.1rem 1.4rem;
            border-bottom: 1px solid #eef0f2;
        }

        .modal-title { margin: 0; font-size: 1.05rem; font-weight: 700; color: #1f2937; }

        .modal-close {
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 50%;
            background: transparent;
            color: #6b7280;
            font-size: 1rem;
            cursor: pointer;
        }

        .modal-close:hover { background: #f3f4f6; color: #111827; }

        .modal-body {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem 1.2rem;
            padding: 1.3rem 1.4rem;
            overflow-y: auto;
        }

        .filtro-bloque.full { grid-column: 1 / -1; }

        .filtro-label {
            margin: 0 0 0.35rem;
            font-size: 0.74rem;
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .filtro-bloque .combo-input { width: 100%; }

        .filtro-row {
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr;
            gap: 0.7rem;
            align-items: end;
        }

        .filtro-inline { display: flex; flex-direction: column; gap: 0.25rem; }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 0.7rem;
            padding: 1rem 1.4rem;
            border-top: 1px solid #eef0f2;
            background: #fafafa;
        }

        .modal-footer .btn-secondary,
        .modal-footer .btn-primary {
            padding: 0.65rem 1.2rem;
            border-radius: 10px;
            font-size: 0.85rem;
        }

        .btn-primary {
            background: #16a34a;
            color: #ffffff;
            border: 1px solid #16a34a;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-primary:hover { background: #15803d; border-color: #15803d; }

        @media (max-width: 640px) {
            .modal-body { grid-template-columns: 1fr; }
            .filtro-row { grid-template-columns: 1fr; }
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

        <!-- Buscador + botón de filtros (los filtros avanzados están en el modal) -->
        <div class="toolbar">
            <div class="search-box">
                <svg class="search-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <defs>
                        <linearGradient id="searchGrad" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0%" stop-color="#06b6d4"/>
                            <stop offset="100%" stop-color="#a855f7"/>
                        </linearGradient>
                    </defs>
                    <circle cx="10.5" cy="10.5" r="6.5" stroke="url(#searchGrad)" stroke-width="2.6"/>
                    <line x1="15.5" y1="15.5" x2="21" y2="21" stroke="url(#searchGrad)" stroke-width="2.8" stroke-linecap="round"/>
                </svg>
                <input type="text" id="ocSearch" class="search-input" placeholder="Busca por OC, factura, usuario, proveedor, referencia, modelo, color, talla, bodega..." autocomplete="off">
            </div>

            <button type="button" class="btn-filters" id="btnOpenFilters" aria-haspopup="dialog" aria-controls="filtersModal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="4" y1="6" x2="20" y2="6"/>
                    <line x1="7" y1="12" x2="17" y2="12"/>
                    <line x1="10" y1="18" x2="14" y2="18"/>
                </svg>
                Filtros
                <span class="filters-badge" id="filtersBadge" style="display:none;">0</span>
            </button>

            <select id="ocPageSize" class="combo-input" style="max-width: 110px;" title="Registros por página">
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
            <button type="button" class="btn-download-missing" title="Descargar facturas faltantes" aria-label="Descargar facturas faltantes" onclick="window.location.href='{{ route('siigo.invoice_purchase_order_missing_download') }}'">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
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

    <a href="{{ route('home') }}" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Volver a acciones disponibles
    </a>
</div>

<!-- Modal de filtros avanzados -->
<div class="modal-overlay" id="filtersModal" role="dialog" aria-modal="true" aria-labelledby="filtersModalTitle">
    <div class="modal-box">

        <div class="modal-header">
            <h3 class="modal-title" id="filtersModalTitle">Filtros avanzados</h3>
            <button type="button" class="modal-close" id="filtersClose" aria-label="Cerrar">✕</button>
        </div>

        <div class="modal-body">

            <div class="filtro-bloque">
                <p class="filtro-label">Estado de recepción</p>
                <select id="ocStatus" class="combo-input">
                    <option value="">Todos los estados</option>
                    <option value="completa">Completo</option>
                    <option value="faltantes">Faltantes</option>
                </select>
            </div>

            <div class="filtro-bloque">
                <p class="filtro-label">Validez de la OC</p>
                <select id="ocValid" class="combo-input">
                    <option value="">Todas las OC</option>
                    <option value="valida">Válida</option>
                    <option value="anulada">Anulada</option>
                </select>
            </div>

            <div class="filtro-bloque full">
                <p class="filtro-label">Antigüedad</p>
                <select id="ocAging" class="combo-input">
                    <option value="">Todas las antigüedades</option>
                    <option value="age-red">🔴 Vencido · Más de 4 meses</option>
                    <option value="age-orange">🟠 Por vencer · Más de 1 mes y hasta 4 meses</option>
                    <option value="age-green">🟢 Reciente · Hasta 1 mes</option>
                    <option value="age-blue">🔵 Sin fecha</option>
                </select>
            </div>

            <div class="filtro-bloque full">
                <p class="filtro-label">Rango de fechas</p>
                <div class="filtro-row">
                    <select id="ocDateField" class="combo-input" title="Fecha a filtrar">
                        <option value="order">Fecha de orden de compra</option>
                        <option value="invoice">Fecha de factura</option>
                    </select>
                    <div class="filtro-inline">
                        <span class="filter-label">Desde</span>
                        <input type="date" id="ocDateFrom" class="combo-input">
                    </div>
                    <div class="filtro-inline">
                        <span class="filter-label">Hasta</span>
                        <input type="date" id="ocDateTo" class="combo-input">
                    </div>
                </div>
            </div>

            <div class="filtro-bloque">
                <p class="filtro-label">Referencia</p>
                <select id="ocRef" class="combo-input" title="Referencia">
                    <option value="">Todas las referencias</option>
                </select>
            </div>

            <div class="filtro-bloque">
                <p class="filtro-label">Modelo</p>
                <select id="ocModel" class="combo-input" title="Modelo">
                    <option value="">Todos los modelos</option>
                </select>
            </div>

            <div class="filtro-bloque">
                <p class="filtro-label">Color</p>
                <select id="ocColor" class="combo-input" title="Color">
                    <option value="">Todos los colores</option>
                </select>
            </div>

            <div class="filtro-bloque">
                <p class="filtro-label">Talla</p>
                <select id="ocSize" class="combo-input" title="Talla">
                    <option value="">Todas las tallas</option>
                </select>
            </div>

            <div class="filtro-bloque">
                <p class="filtro-label">Bodega</p>
                <select id="ocWarehouse" class="combo-input" title="Bodega">
                    <option value="">Todas las bodegas</option>
                </select>
            </div>

            <div class="filtro-bloque">
                <p class="filtro-label">Usuario</p>
                <select id="ocUser" class="combo-input" title="Usuario (de la OC o de sus facturas)">
                    <option value="">Todos los usuarios</option>
                </select>
            </div>

        </div>

        <div class="modal-footer">
            <button type="button" class="btn-secondary" id="ocFiltersClear">Limpiar</button>
            <button type="button" class="btn-primary" id="filtersApply">Ver resultados</button>
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
    const validSel       = document.getElementById('ocValid');
    const agingSel       = document.getElementById('ocAging');
    const pageSizeSel    = document.getElementById('ocPageSize');
    const btnAll         = document.getElementById('btnToggleAll');
    const paginationEl   = document.getElementById('ocPagination');
    const paginationInfo = document.getElementById('ocPaginationInfo');
    const pagesEl        = document.getElementById('ocPages');

    // Nuevos filtros
    const dateFieldSel   = document.getElementById('ocDateField');
    const dateFromInput  = document.getElementById('ocDateFrom');
    const dateToInput    = document.getElementById('ocDateTo');
    const refSel         = document.getElementById('ocRef');
    const modelSel       = document.getElementById('ocModel');
    const colorSel       = document.getElementById('ocColor');
    const sizeSel        = document.getElementById('ocSize');
    const warehouseSel   = document.getElementById('ocWarehouse');
    const userSel        = document.getElementById('ocUser');
    const modalEl        = document.getElementById('filtersModal');
    const btnOpenFilters = document.getElementById('btnOpenFilters');
    const btnCloseFilters = document.getElementById('filtersClose');
    const btnApplyFilters = document.getElementById('filtersApply');
    const filtersBadge   = document.getElementById('filtersBadge');
    const clearBtn       = document.getElementById('ocFiltersClear');

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

    function pendingFmt(value) {
        value = Number(value) || 0;
        return value > 0 ? '+' + qtyFmt(value) : qtyFmt(value);
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

    // Devuelve la fecha como 'YYYY-MM-DD' (comparable con <input type="date">) o '' si no es válida.
    function isoDate(v) {
        if (!v) return '';
        const isoMatch = String(v).match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (isoMatch) return `${isoMatch[1]}-${isoMatch[2]}-${isoMatch[3]}`;
        const d = new Date(v);
        if (isNaN(d.getTime())) return '';
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const dd = String(d.getDate()).padStart(2, '0');
        return `${d.getFullYear()}-${mm}-${dd}`;
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
        if (pending > 0) return 'cover-over';
        if (pending < 0) return 'cover-partial';
        return 'cover-full';
    }

    /* ---------------- Helpers de filtros por item ---------------- */

    function cleanValue(v) {
        if (v === null || v === undefined) return '';
        return String(v).replace(/\|/g, ' ').trim();
    }

    const WAREHOUSE_EMPTY = '-1 SIN ASIGNAR';

    // Bodega vacía o en blanco => "-1 SIN ASIGNAR"
    function warehouseValue(v) {
        return cleanValue(v) || WAREHOUSE_EMPTY;
    }

    // Minúsculas y sin tildes, para que "razon" encuentre "Razón"
    function normalizeText(v) {
        return String(v === null || v === undefined ? '' : v)
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase();
    }

    function uniqueValues(list) {
        return Array.from(new Set(list.map(cleanValue).filter((v) => v !== '')));
    }

    function fillSelect(select, placeholder, values) {
        const sorted = values.slice().sort((a, b) =>
            a.localeCompare(b, 'es', { numeric: true, sensitivity: 'base' })
        );

        select.innerHTML = '';

        const first = document.createElement('option');
        first.value = '';
        first.textContent = placeholder;
        select.appendChild(first);

        sorted.forEach((v) => {
            const opt = document.createElement('option');
            opt.value = v;
            opt.textContent = v;
            select.appendChild(opt);
        });

        select.value = '';
    }

    // Llena los selects con los valores que realmente existen en los datos.
    function populateItemFilters(purchaseOrders) {
        const refs = [], models = [], colors = [], sizes = [], warehouses = [];

        purchaseOrders.forEach((order) => {
            const items = Array.isArray(order.Items) ? order.Items : [];
            items.forEach((item) => {
                refs.push(item.Reference);
                models.push(item.Model);
                colors.push(item.Color);
                sizes.push(item.Size);
                warehouses.push(warehouseValue(item.Warehouse));
            });
        });

        fillSelect(warehouseSel, 'Todas las bodegas', uniqueValues(warehouses));

        // Usuarios: el de la OC y los de sus facturas
        const users = [];
        purchaseOrders.forEach((order) => {
            users.push(order.User);
            (Array.isArray(order.Invoices) ? order.Invoices : []).forEach((inv) => users.push(inv.User));
        });
        fillSelect(userSel, 'Todos los usuarios', uniqueValues(users));

        fillSelect(refSel,   'Todas las referencias', uniqueValues(refs));
        fillSelect(modelSel, 'Todos los modelos',     uniqueValues(models));
        fillSelect(colorSel, 'Todos los colores',     uniqueValues(colors));
        fillSelect(sizeSel,  'Todas las tallas',      uniqueValues(sizes));
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
            <td rowspan="${rowspan}">${esc(warehouseValue(order.Warehouse))}</td>
            <td rowspan="${rowspan}" class="quantity">${qtyFmt(requested)}</td>
            <td rowspan="${rowspan}" class="quantity"><span class="${receivedClass(confirmed, requested)}">${qtyFmt(confirmed)}</span></td>
        `;
    }

    function buildItemsBody(items) {
        if (!items.length) {
            return `<tr><td colspan="12" class="empty-state">Esta orden no tiene items.</td></tr>`;
        }

        let html = '';

        items.forEach((item, itemIdx) => {
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
                        <td rowspan="${itemSpan}">${esc(item.Model || '-')}</td>
                        <td rowspan="${itemSpan}">${esc(item.Color || '-')}</td>
                        <td rowspan="${itemSpan}">${esc(item.Category || '-')}</td>
                        <td rowspan="${itemSpan}">${esc(item.Size || '-')}</td>
                        <td rowspan="${itemSpan}">${esc(warehouseValue(item.Warehouse))}</td>
                        <td rowspan="${itemSpan}" class="text-right">${money(item.UnitValue)}</td>
                        <td rowspan="${itemSpan}" class="text-right">${money(item.Value)}</td>
                        <td rowspan="${itemSpan}" class="quantity">${qtyFmt(itemQty)}</td>
                        <td rowspan="${itemSpan}" class="quantity"><span class="${receivedClass(itemConfirmed, itemQty)}">${qtyFmt(itemConfirmed)}</span></td>
                        <td rowspan="${itemSpan}" class="quantity"><span class="${pClass}">${pendingFmt(itemPending)}</span></td>
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

                html += `<tr class="${rowClass}" data-item-index="${itemIdx}">${cells}</tr>`;
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
                                    <th>Referencia</th>
                                    <th>Modelo</th>
                                    <th>Color</th>
                                    <th>Categoría</th>
                                    <th>Talla</th>
                                    <th>Bodega</th>
                                    <th>Valor unit.</th>
                                    <th>Valor total</th>
                                    <th>Solicitada</th>
                                    <th>Recibida</th>
                                    <th>Pendiente</th>
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

        // Todo lo que no está "completa" (sin_factura o pendiente) cae
        // bajo el filtro "Faltantes" del select de estado.
        const validState = order.IsAnnulled ? 'anulada' : 'valida';
        const agingState = ['age-red', 'age-orange', 'age-green'].includes(order.AgingStatus)
            ? order.AgingStatus
            : 'age-green';

        const items = Array.isArray(order.Items) ? order.Items : [];

        // Fechas para el filtro por rango
        const orderDate = isoDate(order.DocDate);
        const invoiceDates = invoices.map((i) => isoDate(i.DocDate)).filter(Boolean).join(' ');

        // Todo lo que se puede buscar por coincidencia (se excluyen las fechas).
        const searchParts = [
            order.DocName,
            order.User,
            order.FullName,
            order.CompanyName,
            order.Identification,
            order.Observations,
            warehouseValue(order.Warehouse),
        ];

        invoices.forEach((inv) => {
            searchParts.push(inv.DocName, inv.ExternalDocumentNumber, inv.User);
        });

        items.forEach((it) => {
            searchParts.push(
                it.Reference,
                it.Model,
                it.Color,
                it.Category,
                it.Size,
                warehouseValue(it.Warehouse)
            );
            (Array.isArray(it.Invoices) ? it.Invoices : []).forEach((inv) => {
                searchParts.push(inv.DocName);
            });
        });

        const searchText = normalizeText(
            searchParts.filter((v) => v !== null && v !== undefined && String(v).trim() !== '').join(' ')
        );

        let rowsHtml = '';
        invoiceRows.forEach((invoice, idx) => {
            if (idx === 0) {
                rowsHtml += `<tr class="oc-row">${renderOrderMainCells(order, ocIndex, rowspan, requested, confirmed)}${renderInvoiceCells(invoice)}</tr>`;
            } else {
                rowsHtml += `<tr class="oc-row">${renderInvoiceCells(invoice)}</tr>`;
            }
        });

        const detailsHtml = buildDetailsRow(order, ocIndex, items);

        return `<tbody class="oc-group ${agingState}"
            data-search="${esc(searchText)}"
            data-status="${status}"
            data-valid="${validState}"
            data-aging="${agingState}"
            data-order-date="${esc(orderDate)}"
            data-invoice-dates="${esc(invoiceDates)}">${rowsHtml}${detailsHtml}</tbody>`;
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
            totalPending += conf - req;
        });

        sumOrdersEl.textContent    = qtyFmt(totalOrders);
        sumRequestedEl.textContent = qtyFmt(totalQuantity);
        sumReceivedEl.textContent  = qtyFmt(totalConfirmed);
        sumPendingEl.textContent   = pendingFmt(totalPending);
    }

    /* ---------------- Render principal ---------------- */

    function clearGroups() {
        table.querySelectorAll('tbody').forEach((el) => el.remove());
    }

    function renderOrders(purchaseOrders) {
        clearGroups();
        renderSummary(purchaseOrders);
        populateItemFilters(purchaseOrders);

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

        // Valores de cada item (mismo orden que data-item-index) para filtrar y resaltar.
        groups.forEach((g, i) => {
            const items = Array.isArray(purchaseOrders[i].Items) ? purchaseOrders[i].Items : [];

            const orderInvoices = Array.isArray(purchaseOrders[i].Invoices) ? purchaseOrders[i].Invoices : [];
            g._users = [purchaseOrders[i].User, ...orderInvoices.map((inv) => inv.User)]
                .map(cleanValue)
                .filter(Boolean);

            g._items = items.map((it) => ({
                ref:   cleanValue(it.Reference),
                model: cleanValue(it.Model),
                color: cleanValue(it.Color),
                size:  cleanValue(it.Size),
                warehouse: warehouseValue(it.Warehouse),
            }));
        });

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

    // Un item coincide si cumple TODOS los filtros de item que estén activos.
    function itemMatches(item, f) {
        return (!f.ref   || item.ref   === f.ref)
            && (!f.model || item.model === f.model)
            && (!f.color || item.color === f.color)
            && (!f.size  || item.size  === f.size)
            && (!f.warehouse || item.warehouse === f.warehouse);
    }

    // Pinta de gris las filas de los items que coinciden; los demás items se siguen mostrando.
    function highlightItems(g, f, active) {
        g.querySelectorAll('tr[data-item-index]').forEach((tr) => {
            const item = g._items && g._items[Number(tr.dataset.itemIndex)];
            tr.classList.toggle('item-match', !!(active && item && itemMatches(item, f)));
        });
    }

    function matchesDateRange(g, field, from, to) {
        if (!from && !to) return true;

        const dates = field === 'invoice'
            ? (g.dataset.invoiceDates ? g.dataset.invoiceDates.split(' ') : [])
            : (g.dataset.orderDate ? [g.dataset.orderDate] : []);

        // Si hay rango y la OC no tiene fecha (o no tiene facturas), no coincide.
        return dates.some((d) => (!from || d >= from) && (!to || d <= to));
    }

    function applyFilters() {
        const q = normalizeText(searchInput.value.trim());
        const status = statusSel.value;
        const validValue = validSel.value;
        const agingValue = agingSel.value;

        const dateField = dateFieldSel.value;
        const dateFrom = dateFromInput.value;
        const dateTo = dateToInput.value;
        const userValue = userSel.value;

        const itemFilter = {
            ref:   refSel.value,
            model: modelSel.value,
            color: colorSel.value,
            size:  sizeSel.value,
            warehouse: warehouseSel.value,
        };
        const itemFilterActive = !!(itemFilter.ref || itemFilter.model || itemFilter.color || itemFilter.size || itemFilter.warehouse);

        const matched = groups.filter((g) => {
            const matchesSearch = !q || g.dataset.search.includes(q);

            // "completa" filtra exacto; "faltantes" agrupa todo lo que
            // no está completo (pendiente + sin_factura).
            const matchesStatus = !status
                || (status === 'faltantes' ? g.dataset.status !== 'completa' : g.dataset.status === status);

            const matchesValid = !validValue || g.dataset.valid === validValue;
            const matchesAging = !agingValue || g.dataset.aging === agingValue;

            const matchesDate  = matchesDateRange(g, dateField, dateFrom, dateTo);
            const matchesUser  = !userValue || (g._users || []).includes(userValue);

            const matchesItems = !itemFilterActive
                || (g._items || []).some((it) => itemMatches(it, itemFilter));

            return matchesSearch && matchesStatus && matchesValid && matchesAging
                && matchesDate && matchesUser && matchesItems;
        });

        const totalPages = Math.max(1, Math.ceil(matched.length / ocPageSize));
        if (ocPage > totalPages) ocPage = totalPages;
        if (ocPage < 1) ocPage = 1;

        const start = (ocPage - 1) * ocPageSize;
        const pageItems = new Set(matched.slice(start, start + ocPageSize));
        const matchedSet = new Set(matched);

        groups.forEach((g) => {
            const visible = matchedSet.has(g) && pageItems.has(g);
            g.style.display = visible ? '' : 'none';
            if (visible) highlightItems(g, itemFilter, itemFilterActive);
        });

        const noResults = document.getElementById('ocNoResults');
        if (noResults) noResults.style.display = groups.length && !matched.length ? '' : 'none';

        paginationInfo.textContent = matched.length
            ? `Mostrando ${start + 1}-${start + pageItems.size} de ${matched.length}`
            : '0 resultados';

        renderPagination(totalPages);
        updateFilterUi(matched.length);
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

    function onFilterChange() { ocPage = 1; applyFilters(); }

    searchInput.addEventListener('input', onFilterChange);
    statusSel.addEventListener('change', onFilterChange);
    validSel.addEventListener('change', onFilterChange);
    agingSel.addEventListener('change', onFilterChange);

    dateFieldSel.addEventListener('change', onFilterChange);
    dateFromInput.addEventListener('change', onFilterChange);
    dateToInput.addEventListener('change', onFilterChange);
    refSel.addEventListener('change', onFilterChange);
    modelSel.addEventListener('change', onFilterChange);
    colorSel.addEventListener('change', onFilterChange);
    sizeSel.addEventListener('change', onFilterChange);
    warehouseSel.addEventListener('change', onFilterChange);
    userSel.addEventListener('change', onFilterChange);

    pageSizeSel.addEventListener('change', () => {
        ocPageSize = Number(pageSizeSel.value) || 10;
        ocPage = 1;
        applyFilters();
    });

    // Limpiar todos los filtros (no cambia el tamaño de página)
    clearBtn.addEventListener('click', () => {
        searchInput.value = '';
        statusSel.value = '';
        validSel.value = '';
        agingSel.value = '';
        dateFieldSel.value = 'order';
        dateFromInput.value = '';
        dateToInput.value = '';
        refSel.value = '';
        modelSel.value = '';
        colorSel.value = '';
        sizeSel.value = '';
        warehouseSel.value = '';
        userSel.value = '';
        ocPage = 1;
        applyFilters();
    });

    /* ---------------- Modal de filtros ---------------- */

    function openFilters() {
        modalEl.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeFilters() {
        modalEl.classList.remove('show');
        document.body.style.overflow = '';
    }

    function countActiveFilters() {
        let n = 0;
        [statusSel, validSel, agingSel, refSel, modelSel, colorSel, sizeSel, warehouseSel, userSel]
            .forEach((sel) => { if (sel.value) n++; });
        if (dateFromInput.value || dateToInput.value) n++;
        return n;
    }

    // Actualiza el contador del botón "Filtros" y el texto "Ver N resultados" del modal.
    function updateFilterUi(count) {
        const active = countActiveFilters();
        filtersBadge.textContent = active;
        filtersBadge.style.display = active ? 'inline-flex' : 'none';
        btnApplyFilters.textContent = `Ver ${count} ${count === 1 ? 'resultado' : 'resultados'}`;
    }

    btnOpenFilters.addEventListener('click', openFilters);
    btnCloseFilters.addEventListener('click', closeFilters);
    btnApplyFilters.addEventListener('click', closeFilters);

    // Clic fuera de la caja cierra el modal
    modalEl.addEventListener('click', (e) => {
        if (e.target === modalEl) closeFilters();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modalEl.classList.contains('show')) closeFilters();
    });

    /* ---------------- Init ---------------- */

    loadPurchaseOrders();
})();
</script>

</body>
</html>
