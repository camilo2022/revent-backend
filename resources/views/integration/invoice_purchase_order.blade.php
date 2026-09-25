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
        max-height: 75vh;
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
    .main-table thead th {
        text-align: center;
        font-weight: 700;
        letter-spacing: 0.03em;
        text-transform: uppercase;
        padding: 0.7rem 0.8rem;
        vertical-align: middle;
        position: sticky;
        top: 0;
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

    /* Nivel 2: tono índigo, claramente distinto del nivel 1 (el top lo fija el JS) */
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

    .execution-info {
        margin-top: 1rem;
        display: flex;
        justify-content: flex-end;
        font-size: 0.72rem;
        color: #9ca3af;
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

@php
    $purchaseOrders = collect($purchase_orders ?? []);

    $totalOrders    = $purchaseOrders->count();
    $totalQuantity  = $purchaseOrders->sum(fn ($o) => $o['TotalQuantity'] ?? 0);
    $totalConfirmed = $purchaseOrders->sum(fn ($o) => $o['TotalConfirmed'] ?? 0);
    $totalPending   = $purchaseOrders->sum(
        fn ($o) => max(($o['TotalQuantity'] ?? 0) - ($o['TotalConfirmed'] ?? 0), 0)
    );

    $money = fn ($v) => '$' . number_format((float) $v, 0, ',', '.');
    $qty   = fn ($v) => number_format((float) $v, 0, ',', '.');
    $date  = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('d/m/Y') : '-';

    // 0 = gris | menor y distinto de 0 = naranja | igual = verde | mayor = azul
    $receivedClass = fn ($received, $requested) => $received <= 0
        ? 'cover-none'
        : ($received < $requested
            ? 'cover-partial'
            : ($received > $requested ? 'cover-over' : 'cover-full'));
@endphp

<div class="wrapper">
    <div class="card">

        <div class="title">Órdenes de compra</div>
        <div class="subtitle">Órdenes de compra y facturas donde se recibió cada cantidad.</div>

        <div class="summary-grid">
            <div class="summary-card summary-orders">
                <div class="amount">{{ $qty($totalOrders) }}</div>
                <div class="label">Órdenes de compra</div>
            </div>
            <div class="summary-card summary-requested">
                <div class="amount">{{ $qty($totalQuantity) }}</div>
                <div class="label">Cantidad solicitada</div>
            </div>
            <div class="summary-card summary-received">
                <div class="amount">{{ $qty($totalConfirmed) }}</div>
                <div class="label">Cantidad recibida</div>
            </div>
            <div class="summary-card summary-pending">
                <div class="amount">{{ $qty($totalPending) }}</div>
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

            <button type="button" class="btn-toggle-all" id="btnToggleAll">Expandir todo</button>

            <span class="result-count" id="ocCount"></span>
        </div>

        <div class="docs-scroll">
            <table class="docs-table main-table">
                <thead>
                    <tr>
                        <th rowspan="2" style="width:50px;"></th>
                        <th rowspan="2">OC</th>
                        <th rowspan="2">Valida</th>
                        <th rowspan="2">Fecha OC</th>
                        <th colspan="3">Proveedor</th>
                        <th rowspan="2">Fecha Límite</th>
                        <th rowspan="2">Observación</th>
                        <th rowspan="2">Bodega</th>
                        <th colspan="2">Cantidad</th>
                        <th colspan="3">Facturas</th>
                    </tr>
                    <tr>
                        <th>Razón Social</th>
                        <th>Nombre Comercial</th>
                        <th>Identificación</th>
                        <th>Solicitada</th>
                        <th>Recibida</th>
                        <th>Documento</th>
                        <th>Fecha</th>
                        <th>N° Factura</th>
                    </tr>
                </thead>

                @forelse($purchaseOrders as $order)

                    @php
                        $ocIndex = $loop->index;

                        $invoices = collect($order['Invoices'] ?? [])->values();
                        $invoiceRows = $invoices->isEmpty() ? collect([null]) : $invoices;
                        $rowspan = $invoiceRows->count(); // 1 por defecto; N si la OC tiene N facturas

                        $requested = $order['TotalQuantity'] ?? 0;
                        $confirmed = $order['TotalConfirmed'] ?? 0;

                        $status = $invoices->isEmpty()
                            ? 'sin_factura'
                            : ($confirmed >= $requested ? 'completa' : 'pendiente');

                        $searchText = mb_strtolower(implode(' ', array_filter([
                            $order['DocName'] ?? '',
                            $order['FullName'] ?? '',
                            $order['CompanyName'] ?? '',
                            $order['Identification'] ?? '',
                            $invoices->pluck('DocName')->implode(' '),
                            $invoices->pluck('ExternalDocumentNumber')->implode(' '),
                        ])));

                        $items = collect($order['Items'] ?? []);
                    @endphp

                    <tbody class="oc-group" data-search="{{ $searchText }}" data-status="{{ $status }}">

                        @foreach($invoiceRows as $invoice)
                            <tr class="oc-row">

                                @if($loop->first)
                                    <td rowspan="{{ $rowspan }}" class="text-center">
                                        <button type="button" class="btn-expand" data-target="oc-details-{{ $ocIndex }}" title="Mostrar items" aria-label="Mostrar items">
                                            <svg class="expand-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </button>
                                    </td>

                                    <td rowspan="{{ $rowspan }}">
                                        <a href="{{ $order['Link'] ?? '#' }}" target="_blank" rel="noopener noreferrer" class="document-link" title="Abrir orden de compra en Siigo">
                                            {{ $order['DocName'] ?? '-' }}
                                        </a>
                                    </td>

                                    <td rowspan="{{ $rowspan }}" class="text-center">
                                        @if(!($order['IsAnnulled'] ?? false))
                                            <span class="badge badge-ok">Válida</span>
                                        @else
                                            <span class="badge badge-invalid">Anulada</span>
                                        @endif
                                    </td>

                                    <td rowspan="{{ $rowspan }}" class="nowrap">{{ $date($order['DocDate'] ?? null) }}</td>

                                    <td rowspan="{{ $rowspan }}">
                                        <div class="provider-name">{{ $order['FullName'] ?? '-' }}</div>
                                    </td>

                                    <td rowspan="{{ $rowspan }}">
                                        <div class="provider-company">{{ !empty($order['CompanyName']) ? $order['CompanyName'] : '-' }}</div>
                                    </td>

                                    <td rowspan="{{ $rowspan }}" class="nowrap">{{ $order['Identification'] ?? '-' }}</td>

                                    <td rowspan="{{ $rowspan }}" class="nowrap">{{ $date($order['DeadlineDate'] ?? null) }}</td>

                                    <td rowspan="{{ $rowspan }}">
                                        @if(!empty($order['Observations']))
                                            <div class="obs-text">{{ $order['Observations'] }}</div>
                                        @else
                                            -
                                        @endif
                                    </td>

                                    <td rowspan="{{ $rowspan }}">{{ !empty($order['Warehouse']) ? $order['Warehouse'] : '-' }}</td>

                                    <td rowspan="{{ $rowspan }}" class="quantity">{{ $qty($requested) }}</td>

                                    <td rowspan="{{ $rowspan }}" class="quantity">
                                        <span class="{{ $receivedClass($confirmed, $requested) }}">
                                            {{ $qty($confirmed) }}
                                        </span>
                                    </td>
                                @endif

                                @if($invoice)
                                    <td>
                                        <a href="{{ $invoice['Link'] ?? '#' }}" target="_blank" rel="noopener noreferrer" class="document-link" title="Abrir factura en Siigo">
                                            {{ $invoice['DocName'] ?? '-' }}
                                        </a>
                                    </td>
                                    <td class="nowrap">{{ $date($invoice['DocDate'] ?? null) }}</td>
                                    <td class="nowrap"><strong>{{ $invoice['ExternalDocumentNumber'] ?? '-' }}</strong></td>
                                @else
                                    <td colspan="3" class="invoice-empty">Sin factura asociada</td>
                                @endif

                            </tr>
                        @endforeach

                        {{-- ============ DETALLE DESPLEGABLE: ITEMS ============ --}}
                        <tr class="oc-details-row" id="oc-details-{{ $ocIndex }}" style="display:none;">
                            <td colspan="15">

                                <div class="items-title">
                                    Items de {{ $order['DocName'] ?? '' }} · {{ $items->count() }} referencia(s)
                                </div>

                                <div class="items-scroll">
                                    <table class="docs-table items-table">
                                        <thead>
                                            <tr>
                                                <th>Referencia</th>
                                                <th>Color</th>
                                                <th>Categoría</th>
                                                <th>Talla</th>
                                                <th>Bodega</th>
                                                <th>Valor unit.</th>
                                                <th>Valor total</th>
                                                <th>Solicitada</th>
                                                <th>Recibida</th>
                                                <th>Pendiente</th>
                                                <th>Factura</th>
                                                <th>Cant. en factura</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            @forelse($items as $item)
                                                @php
                                                    $itemInvoices = collect($item['Invoices'] ?? [])->values();
                                                    $itemRows = $itemInvoices->isEmpty() ? collect([null]) : $itemInvoices;
                                                    $itemSpan = $itemRows->count(); // 1 por defecto; N si se recibió en N facturas

                                                    $itemQty = $item['Quantity'] ?? 0;
                                                    $itemConfirmed = $item['Confirmed'] ?? 0;
                                                    $itemPending = $item['Pending'] ?? ($itemQty - $itemConfirmed);

                                                    $pendingClass = $itemPending > 0
                                                        ? 'cover-partial'
                                                        : ($itemPending < 0 ? 'cover-over' : 'cover-full');
                                                @endphp

                                                @foreach($itemRows as $itemInvoice)
                                                    <tr class="{{ $loop->first ? 'item-start' : '' }}">

                                                        @if($loop->first)
                                                            <td rowspan="{{ $itemSpan }}"><span class="prefix-tag">{{ $item['Reference'] ?? '-' }}</span></td>
                                                            <td rowspan="{{ $itemSpan }}">{{ $item['Color'] ?? '-' }}</td>
                                                            <td rowspan="{{ $itemSpan }}">{{ $item['Category'] ?? '-' }}</td>
                                                            <td rowspan="{{ $itemSpan }}">{{ $item['Size'] ?? '-' }}</td>
                                                            <td rowspan="{{ $itemSpan }}">{{ !empty($item['Warehouse']) ? $item['Warehouse'] : '-' }}</td>
                                                            <td rowspan="{{ $itemSpan }}" class="text-right">{{ $money($item['UnitValue'] ?? 0) }}</td>
                                                            <td rowspan="{{ $itemSpan }}" class="text-right">{{ $money($item['Value'] ?? 0) }}</td>
                                                            <td rowspan="{{ $itemSpan }}" class="quantity">{{ $qty($itemQty) }}</td>
                                                            <td rowspan="{{ $itemSpan }}" class="quantity">
                                                                <span class="{{ $receivedClass($itemConfirmed, $itemQty) }}">
                                                                    {{ $qty($itemConfirmed) }}
                                                                </span>
                                                            </td>
                                                            <td rowspan="{{ $itemSpan }}" class="quantity">
                                                                <span class="{{ $pendingClass }}">
                                                                    {{ $qty($itemPending) }}
                                                                </span>
                                                            </td>
                                                        @endif

                                                        @if($itemInvoice)
                                                            <td>
                                                                <a href="{{ $itemInvoice['Link'] ?? '#' }}" target="_blank" rel="noopener noreferrer" class="document-link">
                                                                    {{ $itemInvoice['DocName'] ?? '-' }}
                                                                </a>
                                                            </td>
                                                            <td class="quantity cover-full">{{ $qty($itemInvoice['Quantity'] ?? 0) }}</td>
                                                        @else
                                                            <td colspan="2" class="invoice-empty">Sin recibir</td>
                                                        @endif

                                                    </tr>
                                                @endforeach

                                            @empty
                                                <tr>
                                                    <td colspan="12" class="empty-state">Esta orden no tiene items.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                            </td>
                        </tr>

                    </tbody>

                @empty

                    <tbody>
                        <tr>
                            <td colspan="15" class="empty-state">No se encontraron órdenes de compra.</td>
                        </tr>
                    </tbody>

                @endforelse

                <tbody id="ocNoResults" style="display:none;">
                    <tr>
                        <td colspan="15" class="empty-state">Sin resultados.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        @if(isset($hora_inicio, $hora_fin))
            <div class="execution-info">
                Tiempo de ejecución: {{ abs($hora_fin->timestamp - $hora_inicio->timestamp) }} s
            </div>
        @endif

    </div>
</div>

<script>
(function () {
    const groups      = Array.from(document.querySelectorAll('tbody.oc-group'));
    const searchInput = document.getElementById('ocSearch');
    const statusSel   = document.getElementById('ocStatus');
    const countEl     = document.getElementById('ocCount');
    const noResults   = document.getElementById('ocNoResults');
    const btnAll      = document.getElementById('btnToggleAll');

    // ---- Desplegar / contraer una OC ----
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

    // ---- Expandir / contraer todo (solo las OC visibles) ----
    let allOpen = false;

    btnAll.addEventListener('click', () => {
        allOpen = !allOpen;
        groups.forEach((g) => {
            if (g.style.display === 'none') return;
            const btn = g.querySelector('.btn-expand');
            if (btn) setOpen(btn, allOpen);
        });
        btnAll.textContent = allOpen ? 'Contraer todo' : 'Expandir todo';
    });

    // ---- Filtros ----
    function applyFilters() {
        const q = searchInput.value.trim().toLowerCase();
        const status = statusSel.value;
        let visible = 0;

        groups.forEach((g) => {
            const show = (!q || g.dataset.search.includes(q))
                && (!status || g.dataset.status === status);

            g.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        noResults.style.display = groups.length && !visible ? '' : 'none';
        countEl.textContent = groups.length
            ? `Mostrando ${visible} de ${groups.length}`
            : '';
    }

    searchInput.addEventListener('input', applyFilters);
    statusSel.addEventListener('change', applyFilters);

    // ---- Sticky: el 2do nivel del encabezado se pega justo debajo del 1ro (altura real) ----
    function fixStickyHeaderOffset() {
        const firstRow  = document.querySelector('.main-table thead tr:first-child');
        const secondRow = document.querySelector('.main-table thead tr:nth-child(2)');
        if (!firstRow || !secondRow) return;

        const height = firstRow.getBoundingClientRect().height;
        secondRow.querySelectorAll('th').forEach((th) => {
            th.style.top = `${height}px`;
        });
    }

    window.addEventListener('load', fixStickyHeaderOffset);
    window.addEventListener('resize', fixStickyHeaderOffset);

    applyFilters();
    fixStickyHeaderOffset();
})();
</script>

</body>
</html>