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
        max-width: 1500px;
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
        max-width: 650px;
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

    select.combo-input {
        padding: 0.65rem 0.85rem;
        cursor: pointer;
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

    /* ---- Toggle "información completa" ---- */
    .all-data-toggle {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 1.2rem;
        font-size: 0.82rem;
        color: #374151;
        cursor: pointer;
        user-select: none;
    }

    .all-data-toggle input {
        width: 16px;
        height: 16px;
        cursor: pointer;
    }

    .all-data-toggle .hint {
        color: #9ca3af;
        font-size: 0.75rem;
    }

    /* ---- Leyenda de clasificación ---- */
    .legend {
        display: none;
        grid-template-columns: repeat(7, 1fr);
        gap: 0.6rem;
        margin-bottom: 1.1rem;
        padding: 0.85rem 1rem;
        background: #f9fafb;
        border: 1px solid #f1f3f5;
        border-radius: 10px;
    }

    .legend.show { display: grid; }

    .legend-item {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 0.4rem;
        font-size: 0.72rem;
        color: #374151;
        white-space: normal;
        font-weight: bold;
        text-align: left;
        min-width: 0;
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

    .summary-deuda     { background: #283593; }
    .summary-favor     { background: #303F9F; }
    .summary-saldo     { background: #3F51B5; }
    .summary-vencido   { background: #5C6BC0; }
    .summary-porvencer { background: #7986CB; }
    .summary-documents { background: #9FA8DA; }

    /* ---- Loading de documentos ---- */
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

    /* Columnas que solo existen cuando se pide "información completa" */
    .docs-table th.col-extra,
    .docs-table td.col-extra {
        display: none;
    }

    .docs-table.show-extra th.col-extra,
    .docs-table.show-extra td.col-extra {
        display: table-cell;
    }

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

    .obs-text::-webkit-scrollbar {
        width: 5px;
    }

    .obs-text::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }

    .obs-text::-webkit-scrollbar-track {
        background: transparent;
    }

    .document-link {
        color: #2563eb;
        text-decoration: underline;
        cursor: pointer;
    }

    .document-link:hover {
        color: #1d4ed8;
    }

    /* NOV1 - Faltantes */
    .row-nov1 { background: #fee2e2; }
    .row-nov1 .prefix-tag { background: #fca5a5; color: #991b1b; }

    /* NOV2 - Sobrantes */
    .row-nov2 { background: #dcfce7; }
    .row-nov2 .prefix-tag { background: #86efac; color: #166534; }

    /* NOV3 - Trocados */
    .row-nov3 { background: #dbeafe; }
    .row-nov3 .prefix-tag { background: #93c5fd; color: #1e40af; }

    /* NOV4 - Corrección de factura */
    .row-nov4 { background: #ffedd5; }
    .row-nov4 .prefix-tag { background: #fdba74; color: #9a3412; }

    /* NOV5 - Mcia. mal estado / Material o accesorios */
    .row-nov5 { background: #fef9c3; }
    .row-nov5 .prefix-tag { background: #fde047; color: #854d0e; }

    /* DES1 - Descuento */
    .row-des1 { background: #f3e8ff; }
    .row-des1 .prefix-tag { background: #d8b4fe; color: #6b21a8; }

    /* RP - Recibo de pago */
    .row-rp { background: #f3f4f6; }
    .row-rp .prefix-tag { background: #d1d5db; color: #374151; }

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

    .badge-ok        { background: #f0fdf4; color: #166534; }
    .badge-porvencer { background: #fff7ed; color: #c2410c; }
    .badge-v1        { background: #fef9c3; color: #854d0e; }
    .badge-v2        { background: #ffedd5; color: #9a3412; }
    .badge-v3        { background: #fee2e2; color: #991b1b; }
    .badge-v4        { background: #fecaca; color: #7f1d1d; }
    .badge-v5        { background: #e5e7eb; color: #374151; }

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
        flex-wrap: wrap;
        gap: 0.8rem;
        box-shadow: 0 12px 28px rgba(0, 0, 0, 0.25);
    }

    .totals-bar.show { display: flex; }

    .totals-info {
        display: flex;
        flex-direction: column;
        gap: 0.15rem;
    }

    .totals-bar .count {
        font-size: 0.8rem;
        color: #d1d5db;
    }

    .totals-bar .amount {
        font-size: 1.15rem;
        font-weight: 700;
    }

    .btn-payment {
        background: #16a34a;
        color: #fff;
        border: none;
        padding: 0.65rem 1.2rem;
        border-radius: 10px;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s ease;
        white-space: nowrap;
    }

    .btn-payment:hover { background: #15803d; }

    .empty-state {
        text-align: center;
        color: #9ca3af;
        font-size: 0.85rem;
        padding: 2.75rem 0;
    }

    .error-state {
        text-align: center;
        color: #dc2626;
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

    /* ---- Modal de recibo de pago ---- */
    .modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(17, 24, 39, 0.55);
        align-items: center;
        justify-content: center;
        padding: 1rem;
        z-index: 100;
    }

    .modal-overlay.show { display: flex; }

    .modal {
        background: #ffffff;
        border-radius: 16px;
        width: 100%;
        max-width: 920px;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3);
    }

    .modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1.2rem 1.5rem;
        border-bottom: 1px solid #f1f3f5;
        flex-shrink: 0;
    }

    .modal-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: #1f2937;
    }

    .modal-close {
        background: none;
        border: none;
        font-size: 1.4rem;
        color: #9ca3af;
        cursor: pointer;
        line-height: 1;
        padding: 0.2rem;
    }

    .modal-close:hover { color: #ef4444; }

    .modal-body {
        padding: 1.4rem 1.5rem;
        overflow-y: auto;
    }

    .modal-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        margin-bottom: 1.4rem;
    }

    .modal-grid .field-group {
        margin-bottom: 0;
        max-width: none;
    }

    .modal-table-wrap {
        border: 1px solid #f1f3f5;
        border-radius: 10px;
        overflow: auto;
        max-height: 260px;
        margin-bottom: 1rem;
    }

    .modal-table-wrap table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.82rem;
    }

    .modal-table-wrap th {
        text-align: left;
        font-size: 0.68rem;
        font-weight: 700;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        padding: 0.6rem 0.7rem;
        background: #f9fafb;
        position: sticky;
        top: 0;
        border-bottom: 1px solid #f1f3f5;
    }

    .modal-table-wrap td {
        padding: 0.55rem 0.7rem;
        border-bottom: 1px solid #f1f3f5;
        color: #374151;
    }

    .modal-table-wrap tbody tr:last-child td { border-bottom: none; }

    .modal-total {
        display: flex;
        justify-content: flex-end;
        align-items: baseline;
        gap: 0.5rem;
        font-size: 1rem;
        color: #1f2937;
    }

    .modal-total span { font-weight: 700; font-size: 1.15rem; }

    .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 0.7rem;
        padding: 1.1rem 1.5rem;
        border-top: 1px solid #f1f3f5;
        flex-shrink: 0;
    }

    .btn-secondary {
        background: #f3f4f6;
        color: #374151;
        border: 1px solid #e5e7eb;
        padding: 0.65rem 1.2rem;
        border-radius: 10px;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
    }

    .btn-secondary:hover { background: #e5e7eb; }

    .btn-primary {
        background: #16a34a;
        color: #fff;
        border: none;
        padding: 0.65rem 1.2rem;
        border-radius: 10px;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
    }

    .btn-primary:hover { background: #15803d; }

    .modal-field-group {
        margin-bottom: 1.4rem;
    }

    .modal-field-group textarea.combo-input {
        width: 100%;
        resize: vertical;
        min-height: 70px;
        font-family: inherit;
        padding: 0.65rem 0.85rem;
    }

    /* ---- Dropzone de comprobante ---- */
    .payment-dropzone {
        position: relative;
        border: 2px dashed #cbd5e1;
        border-radius: 12px;
        padding: 1.5rem 1rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s ease;
        background: #f9fafb;
    }

    .payment-dropzone:hover {
        border-color: #16a34a;
        background: #f0fdf4;
    }

    .payment-dropzone.dragover {
        border-color: #16a34a;
        background: #ecfdf5;
    }

    .payment-dropzone.hidden { display: none; }

    .payment-dropzone-icon {
        width: 42px;
        height: 42px;
        margin: 0 auto 0.6rem;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #dcfce7;
        border-radius: 50%;
    }

    .payment-dropzone-icon svg {
        width: 22px;
        height: 22px;
        stroke: #16a34a;
    }

    .payment-dropzone-text {
        font-size: 0.85rem;
        color: #374151;
        font-weight: 500;
    }

    .payment-dropzone-text span {
        color: #16a34a;
        text-decoration: underline;
    }

    .payment-dropzone-hint {
        font-size: 0.72rem;
        color: #9ca3af;
        margin-top: 0.2rem;
    }

    .payment-file-input { display: none; }

    .payment-file-preview {
        display: none;
        align-items: center;
        gap: 0.85rem;
        margin-top: 0.7rem;
        padding: 0.65rem 0.85rem;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 10px;
    }

    .payment-file-preview.show { display: flex; }

    .payment-file-preview img {
        width: 46px;
        height: 46px;
        object-fit: cover;
        border-radius: 8px;
        flex-shrink: 0;
        border: 1px solid #d1fae5;
    }

    .payment-file-info { flex: 1; min-width: 0; }

    .payment-file-name {
        font-size: 0.82rem;
        font-weight: 600;
        color: #1f2937;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .payment-file-size {
        font-size: 0.72rem;
        color: #6b7280;
    }

    .payment-file-remove {
        background: none;
        border: none;
        cursor: pointer;
        color: #ef4444;
        font-size: 1.1rem;
        line-height: 1;
        padding: 0.2rem;
        border-radius: 6px;
        flex-shrink: 0;
    }

    .payment-file-remove:hover { background: #fee2e2; }

    .payment-file-error {
        display: none;
        margin-top: 0.5rem;
        font-size: 0.78rem;
        color: #dc2626;
        background: #fef2f2;
        border: 1px solid #fecaca;
        padding: 0.45rem 0.7rem;
        border-radius: 8px;
    }

    .payment-file-error.show { display: block; }

    .favor-alert {
        display: none;
        align-items: flex-start;
        gap: 0.7rem;
        margin-bottom: 1.3rem;
        padding: 0.85rem 1.1rem;
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-radius: 10px;
        color: #92400e;
        font-size: 0.82rem;
        line-height: 1.4;
    }

    .favor-alert.show { display: flex; }

    .favor-alert svg {
        width: 18px;
        height: 18px;
        flex-shrink: 0;
        margin-top: 0.1rem;
        stroke: #d97706;
    }

    .favor-alert strong { font-weight: 700; }

    @media (max-width: 768px) {
        .wrapper { padding: 0; }

        .card {
            padding: 1.3rem 1.1rem;
            border-radius: 12px;
        }

        .title { font-size: 1.05rem; }
        .subtitle { font-size: 0.8rem; margin-bottom: 1.2rem; }

        .field-group { max-width: 100%; }

        .summary-grid {
            grid-template-columns: repeat(2, 1fr);
            border-radius: 10px;
        }

        .summary-card { padding: 0.85rem 0.7rem; }
        .summary-card .amount { font-size: 1.05rem; }
        .summary-card .label { font-size: 0.7rem; }

        .legend {
            grid-template-columns: repeat(3, 1fr);
            gap: 0.5rem;
            padding: 0.7rem 0.8rem;
        }

        .legend-item { font-size: 0.66rem; }
        .legend-dot { width: 12px; height: 12px; }

        .docs-table { min-width: 640px; font-size: 0.8rem; }

        .docs-table thead th {
            font-size: 0.72rem;
            padding: 0.6rem 0.6rem;
        }

        .docs-table td { padding: 0.55rem 0.6rem; }

        .totals-bar {
            flex-direction: column;
            align-items: stretch;
            gap: 0.7rem;
            bottom: 0.5rem;
            border-radius: 10px;
            padding: 0.8rem 1.1rem;
        }

        .totals-bar .amount { font-size: 1.05rem; }
        .btn-payment { width: 100%; }

        .modal-grid { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 480px) {
        body { padding: 1rem 0.6rem; }

        .summary-grid { grid-template-columns: repeat(2, 1fr); }
        .summary-card .amount { font-size: 0.95rem; }

        .combo-input { font-size: 0.85rem; }

        .docs-table { min-width: 560px; }

        .legend { grid-template-columns: repeat(2, 1fr); }

        .modal-grid { grid-template-columns: 1fr; }
        .modal-body { padding: 1.1rem 1.1rem; }
        .modal-footer { padding: 1rem 1.1rem; flex-direction: column-reverse; }
        .btn-primary, .btn-secondary { width: 100%; }

        .legend {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.5rem;
            padding: 0.7rem 0.8rem;
        }

        .legend-item {
            font-size: 0.66rem;
            min-width: 0;
            overflow-wrap: break-word;
        }

        .legend-dot {
            width: 12px;
            height: 12px;
            flex-shrink: 0;
        }
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

        <label class="all-data-toggle">
            <input type="checkbox" id="allDataCheck">
            Cargar información completa (orden de compra, bodegas, observaciones)
            <span class="hint">— consulta más lenta</span>
        </label>

        <div class="legend" id="legend">
            <span class="legend-item"><span class="legend-dot" style="background:#fca5a5"></span>NOV1 · Faltantes</span>
            <span class="legend-item"><span class="legend-dot" style="background:#86efac"></span>NOV2 · Sobrantes</span>
            <span class="legend-item"><span class="legend-dot" style="background:#93c5fd"></span>NOV3 · Trocados</span>
            <span class="legend-item"><span class="legend-dot" style="background:#fdba74"></span>NOV4 · Corrección de factura</span>
            <span class="legend-item"><span class="legend-dot" style="background:#fde047"></span>NOV5 · Mercancia mal estado</span>
            <span class="legend-item"><span class="legend-dot" style="background:#d8b4fe"></span>DES1 · Descuento</span>
            <span class="legend-item"><span class="legend-dot" style="background:#d1d5db"></span>RP · Recibo de pago</span>
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

        <div class="favor-alert" id="favorAlert">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/>
                <line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
            <div>
                Este proveedor tiene un saldo a favor de <strong id="favorAlertAmount">$0</strong>. Si vas a realizar el pago, puedes hacerlo normalmente; en caso de que quiera usar este saldo a favor, notifica a contabilidad para que haga el respectivo descuento a esas facturas.
            </div>
        </div>

        <div class="docs-wrap" id="docsWrap">

            <div class="loading-state" id="loadingState">
                <div class="spinner"></div>
                <div class="loading-text">Espera un momento, cargando los documentos del proveedor...</div>
            </div>

            <div class="docs-scroll" id="docsScroll" style="display:none;">
                <table class="docs-table" id="docsTable">
                    <thead>
                        <tr>
                            <th style="width:34px;"><input type="checkbox" id="checkAll" class="row-check"></th>
                            <th>Tipo</th>
                            <th>Documento</th>
                            <th style="min-width: 110px;">Factura</th>
                            <th style="min-width: 110px;" class="col-extra">Orden</th>
                            <th class="col-extra">Observaciones</th>
                            <th style="min-width: 150px;" class="col-extra">Bodegas</th>
                            <th class="col-extra">Cantidad</th>
                            <th>Valor</th>
                            <th style="min-width: 110px;">Fecha vence</th>
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
        <div class="totals-info">
            <div class="count"><span id="selCount">0</span> documento(s) seleccionado(s)</div>
            <div class="amount">Total a pagar: <span id="selTotal">$0</span></div>
        </div>
        <button type="button" class="btn-payment" id="btnPayment">Realizar recibo de pago</button>
    </div>

    <a href="{{ route('home') }}" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Volver a acciones disponibles
    </a>
</div>

<!-- ---- Modal: Realizar recibo de pago ---- -->
<div class="modal-overlay" id="paymentModalOverlay">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">Realizar recibo de pago</div>
            <button type="button" class="modal-close" id="modalClose">&times;</button>
        </div>

        <div class="modal-body">
            <div class="modal-grid">
                <div class="field-group">
                    <label class="field-label" for="paymentTipo">Tipo</label>
                    <select class="combo-input" id="paymentTipo">
                        <option value="">Selecciona...</option>
                        @if ($type_payment_receipts)
                            <option value="{{ $type_payment_receipts['ERPDocumentTypeId'] }}">
                                {{ $type_payment_receipts['DocClass'] }}-{{ $type_payment_receipts['Code'] }} · {{ $type_payment_receipts['Title'] }}
                            </option>
                        @endif
                    </select>
                </div>

                <div class="field-group">
                    <label class="field-label" for="paymentAction">Realizar un</label>
                    <select class="combo-input" id="paymentAction">
                        <option value="">Selecciona...</option>
                        @foreach ($types as $value => $text)
                            <option value="{{ $value }}">{{ $text }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field-group">
                    <label class="field-label" for="paymentSource">De donde sale el dinero</label>
                    <select class="combo-input" id="paymentSource">
                        <option value="">Selecciona...</option>
                        @foreach ($bank_accounts as $bank_account)
                            <option value="{{ $bank_account['ACPaymentMeanID'] }}">{{ $bank_account['PaymentMeanAccount'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field-group">
                    <label class="field-label" for="paymentDate">Fecha de elaboración</label>
                    <input type="date" class="combo-input" id="paymentDate">
                </div>

                <div class="modal-field-group">
                    <label class="field-label" for="paymentObservations">Observaciones</label>
                    <textarea class="combo-input" id="paymentObservations" rows="5" placeholder="Escribe cualquier observación sobre este recibo de pago..."></textarea>
                </div>

                <div class="modal-field-group">
                    <label class="field-label">Comprobante</label>

                    <div class="payment-dropzone" id="paymentDropzone">
                        <div class="payment-dropzone-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="2"/>
                                <circle cx="8.5" cy="8.5" r="1.5"/>
                                <polyline points="21 15 16 10 5 21"/>
                            </svg>
                        </div>
                        <div class="payment-dropzone-text">
                            Arrastra una imagen aquí o <span>selecciónala</span>
                        </div>
                        <div class="payment-dropzone-hint">JPG o PNG (máx. 8 MB)</div>
                        <input type="file" id="paymentFileInput" class="payment-file-input" accept=".jpg,.jpeg,.png">
                    </div>

                    <div class="payment-file-preview" id="paymentFilePreview">
                        <img id="paymentFileImg" alt="Vista previa">
                        <div class="payment-file-info">
                            <div class="payment-file-name" id="paymentFileName"></div>
                            <div class="payment-file-size" id="paymentFileSize"></div>
                        </div>
                        <button type="button" class="payment-file-remove" id="paymentFileRemove">&times;</button>
                    </div>

                    <div class="payment-file-error" id="paymentFileError"></div>
                </div>
            </div>

            <div class="modal-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th style="min-width: 110px;">Documento</th>
                            <th style="min-width: 110px;">Factura</th>
                            <th style="min-width: 110px;">Fecha vence</th>
                            <th style="text-align:right;">Saldo</th>
                        </tr>
                    </thead>
                    <tbody id="modalDocsBody"></tbody>
                </table>
            </div>

            <div class="modal-total">
                Total a pagar: <span id="modalTotal">$0</span>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn-secondary" id="modalCancel">Cancelar</button>
            <button type="button" class="btn-primary" id="modalConfirm">Confirmar recibo de pago</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const PROVIDERS = @json(array_values($providers ?? []), JSON_UNESCAPED_UNICODE);
const DOCUMENTS_URL_TEMPLATE = "{{ route('siigo.account_payable.documents', ['accountId' => '__ID__']) }}";

(function () {
    const providers      = PROVIDERS;
    const searchInput    = document.getElementById('providerSearch');
    const clearBtn       = document.getElementById('providerClear');
    const listEl         = document.getElementById('providerList');
    const allDataCheck   = document.getElementById('allDataCheck');
    const legend         = document.getElementById('legend');
    const summaryGrid    = document.getElementById('summaryGrid');
    const docsWrap       = document.getElementById('docsWrap');
    const loadingState   = document.getElementById('loadingState');
    const docsScroll     = document.getElementById('docsScroll');
    const docsTable      = document.getElementById('docsTable');
    const docsBody       = document.getElementById('docsBody');
    const emptyState     = document.getElementById('emptyState');
    const totalsBar      = document.getElementById('totalsBar');
    const checkAll       = document.getElementById('checkAll');
    const btnPayment     = document.getElementById('btnPayment');

    // Modal
    const modalOverlay   = document.getElementById('paymentModalOverlay');
    const modalClose     = document.getElementById('modalClose');
    const modalCancel    = document.getElementById('modalCancel');
    const modalConfirm   = document.getElementById('modalConfirm');
    const modalDocsBody  = document.getElementById('modalDocsBody');
    const modalTotalEl   = document.getElementById('modalTotal');
    const paymentTipo    = document.getElementById('paymentTipo');
    const paymentAction  = document.getElementById('paymentAction');
    const paymentSource  = document.getElementById('paymentSource');
    const paymentDate    = document.getElementById('paymentDate');

    const paymentObservations = document.getElementById('paymentObservations');
    const paymentDropzone     = document.getElementById('paymentDropzone');
    const paymentFileInput    = document.getElementById('paymentFileInput');
    const paymentFilePreview  = document.getElementById('paymentFilePreview');
    const paymentFileImg      = document.getElementById('paymentFileImg');
    const paymentFileName     = document.getElementById('paymentFileName');
    const paymentFileSize     = document.getElementById('paymentFileSize');
    const paymentFileRemove   = document.getElementById('paymentFileRemove');
    const paymentFileError    = document.getElementById('paymentFileError');

    let paymentFile = null;

    const ALLOWED_IMAGE_EXT = ['jpg', 'jpeg', 'png'];
    const MAX_IMAGE_MB = 8;

    paymentDropzone.addEventListener('click', () => paymentFileInput.click());

    ['dragover', 'dragenter'].forEach((evt) => {
        paymentDropzone.addEventListener(evt, (e) => {
            e.preventDefault();
            paymentDropzone.classList.add('dragover');
        });
    });

    ['dragleave', 'dragend'].forEach((evt) => {
        paymentDropzone.addEventListener(evt, () => paymentDropzone.classList.remove('dragover'));
    });

    paymentDropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        paymentDropzone.classList.remove('dragover');
        if (e.dataTransfer.files.length) handlePaymentFile(e.dataTransfer.files[0]);
    });

    paymentFileInput.addEventListener('change', () => {
        if (paymentFileInput.files.length) handlePaymentFile(paymentFileInput.files[0]);
    });

    paymentFileRemove.addEventListener('click', (e) => {
        e.stopPropagation();
        resetPaymentFile();
    });

    function handlePaymentFile(file) {
        const ext = file.name.split('.').pop().toLowerCase();
        paymentFileError.classList.remove('show');

        if (!ALLOWED_IMAGE_EXT.includes(ext)) {
            showPaymentFileError('Solo se permiten imágenes JPG o PNG');
            resetPaymentFile();
            return;
        }

        if (file.size / (1024 * 1024) > MAX_IMAGE_MB) {
            showPaymentFileError(`La imagen supera el tamaño máximo de ${MAX_IMAGE_MB} MB`);
            resetPaymentFile();
            return;
        }

        paymentFile = file;
        paymentFileName.textContent = file.name;
        paymentFileSize.textContent = formatFileSize(file.size);
        paymentFileImg.src = URL.createObjectURL(file);

        paymentFilePreview.classList.add('show');
        paymentDropzone.classList.add('hidden');
    }

    function resetPaymentFile() {
        paymentFile = null;
        paymentFileInput.value = '';
        paymentFilePreview.classList.remove('show');
        paymentDropzone.classList.remove('hidden');
    }

    function showPaymentFileError(msg) {
        paymentFileError.textContent = msg;
        paymentFileError.classList.add('show');
    }

    function formatFileSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    let filtered = [];
    let activeIndex = -1;
    let requestToken = 0; // evita que una respuesta vieja pise la selección actual
    let currentProvider = null;
    let currentDocs = [];   // documentos actualmente renderizados en la tabla principal
    let selectedDocs = [];  // documentos elegidos al abrir el modal

    // Prefijos que colorean la fila (clasificación de novedades)
    const ROW_CLASS_BY_PREFIX = {
        'NOV1': 'row-nov1',
        'NOV2': 'row-nov2',
        'NOV3': 'row-nov3',
        'NOV4': 'row-nov4',
        'NOV5': 'row-nov5',
        'DES1': 'row-des1',
        'RP': 'row-rp',
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

    function formatQuantity(value) {
        const n = Number(value) || 0;
        return n.toLocaleString('es-CO', { maximumFractionDigits: 2 });
    }

    function isSelectable(doc) {
        let prefix = (doc.DuePrefix || '').toUpperCase();
        return (prefix === 'FC' || prefix.startsWith('DES')) && !doc.IsAnnulled;
    }

    function rowClassFor(doc) {
        const prefix = (doc.DuePrefix || '').toUpperCase();

        if (prefix.startsWith('RP')) {
            return ROW_CLASS_BY_PREFIX['RP'] || '';
        }

        return ROW_CLASS_BY_PREFIX[prefix] || '';
    }

    function estadoBadge(doc) {
        if (doc.IsAnnulled)                  return '<span class="badge badge-v5">Anulado</span>';
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
                (p.CompanyName || '').toLowerCase().includes(q) ||
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
                <span class="combo-option-name">${escapeHtml(p.FullName)} ${p.CompanyName ? ('(' + escapeHtml(p.CompanyName) + ')') : ''}</span>
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
        requestToken++; // invalida cualquier fetch en curso
        currentProvider = null;
        resetView();
    });

    // Si el usuario cambia el checkbox y ya hay un proveedor cargado, se vuelve a consultar
    allDataCheck.addEventListener('change', () => {
        docsTable.classList.toggle('show-extra', allDataCheck.checked);
        if (currentProvider) loadProvider(currentProvider);
    });

    function selectProvider(provider) {
        if (!provider) return;
        searchInput.value = `${provider.FullName} ${provider.CompanyName ? ('(' + provider.CompanyName + ')') : ''}`;
        clearBtn.classList.add('show');
        listEl.classList.remove('show');
        currentProvider = provider;
        loadProvider(provider);
    }

    // ---- Reset ----
    function resetView() {
        summaryGrid.classList.remove('show');
        legend.classList.remove('show');
        docsWrap.classList.remove('show');
        loadingState.classList.remove('show');
        docsScroll.style.display = 'none';
        totalsBar.classList.remove('show');
        emptyState.style.display = 'block';
        docsBody.innerHTML = '';
        currentDocs = [];
    }

    // ---- Pinta el resumen de inmediato (no depende de los documentos) ----
    function renderSummary(provider) {
        const vencido = (Number(provider.Expired1to30) || 0)
            + (Number(provider.Expired31to60) || 0)
            + (Number(provider.Expired61to90) || 0)
            + (Number(provider.ExpiredMoreTo91) || 0);


        document.getElementById('sumDeuda').textContent      = formatMoney(provider.BalanceToExpire + vencido);
        document.getElementById('sumFavor').textContent      = formatMoney(provider.BalanceInFavor);
        document.getElementById('sumSaldo').textContent      = formatMoney(provider.TotalBalance);
        document.getElementById('sumVencido').textContent    = formatMoney(vencido);
        document.getElementById('sumPorVencer').textContent  = formatMoney(provider.BalanceToExpire);
        document.getElementById('sumDocumentos').textContent = '…';

        const favorAlert = document.getElementById('favorAlert');
        const balanceInFavor = Number(provider.BalanceInFavor) || 0;

        if (balanceInFavor > 0) {
            document.getElementById('favorAlertAmount').textContent = formatMoney(balanceInFavor);
            favorAlert.classList.add('show');
        } else {
            favorAlert.classList.remove('show');
        }

        summaryGrid.classList.add('show');
        legend.classList.add('show');
    }

    // ---- Trae los documentos del proveedor por AJAX y muestra loading mientras tanto ----
    async function loadProvider(provider) {
        const myToken = ++requestToken;
        const allData = allDataCheck.checked;

        emptyState.style.display = 'none';
        renderSummary(provider);

        docsWrap.classList.add('show');
        loadingState.classList.add('show');
        docsScroll.style.display = 'none';
        totalsBar.classList.remove('show');
        checkAll.checked = false;
        docsBody.innerHTML = '';

        let url = DOCUMENTS_URL_TEMPLATE.replace('__ID__', encodeURIComponent(provider.AccountID));
        if (allData) url += (url.includes('?') ? '&' : '?') + 'all_data=1';

        try {
            const response = await fetch(url, {
                headers: { 'Accept': 'application/json' },
            });

            if (myToken !== requestToken) return; // el usuario ya seleccionó otro proveedor

            if (!response.ok) throw new Error('request_failed');

            const data = await response.json();
            const docs = data.documents || [];

            renderDocuments(docs, allData);
            document.getElementById('sumDocumentos').textContent = docs.length;
        } catch (err) {
            if (myToken !== requestToken) return;

            docsBody.innerHTML = '<tr><td colspan="12" class="error-state">No se pudieron cargar los documentos de este proveedor. Intenta de nuevo.</td></tr>';
            docsScroll.style.display = 'block';
            document.getElementById('sumDocumentos').textContent = '-';
        } finally {
            if (myToken === requestToken) {
                loadingState.classList.remove('show');
                docsScroll.style.display = 'block';
            }
        }
    }

    function renderDocuments(docs, allData) {
        docsTable.classList.toggle('show-extra', allData);
        currentDocs = docs;

        if (!docs.length) {
            docsBody.innerHTML = '<tr><td colspan="12" class="empty-state">Este proveedor no tiene documentos.</td></tr>';
        } else {
            docsBody.innerHTML = docs.map((doc, i) => {
                const selectable = isSelectable(doc);
                const rowClass = rowClassFor(doc);

                let extraCells = '';
                if (allData) {
                    const purchaseEntry = doc.PurchaseEntry || {};
                    const detail = doc.PurchaseEntryDetail || {};

                    extraCells = `
                        <td>${doc.Links?.PurchaseOrder
                            ? `<a href="${escapeHtml(doc.Links.PurchaseOrder)}" target="_blank" rel="noopener noreferrer" class="document-link">
                                    ${escapeHtml(purchaseEntry.docName)}
                                </a>`
                            : (escapeHtml(purchaseEntry.docName) || '-')
                        }</td>
                        <td>${detail.Observations ? `<div class="obs-text">${escapeHtml(detail.Observations)}</div>` : '-'}</td>
                        <td>${detail.WarehouseCodes ? `<div>${escapeHtml(detail.WarehouseCodes)}</div>` : ''}</td>
                        <td style="text-align:right;">${detail.Quantity ? formatQuantity(detail.Quantity) : '-'}</td>
                    `;
                }

                return `
                    <tr class="${rowClass} ${selectable ? 'selectable' : ''}" data-saldo="${Number(doc.Saldo) || 0}" data-documento="${escapeHtml(doc.DueName)}" data-doc-index="${i}">
                        <td><input type="checkbox" class="row-check doc-check" ${selectable ? '' : 'disabled'}></td>
                        <td><span class="prefix-tag">${escapeHtml(doc.DuePrefix)}</span></td>
                        <td>${escapeHtml(doc.DueName)}</td>
                        <td>${doc.Links?.PurchaseInvoice
                            ? `<a href="${escapeHtml(doc.Links.PurchaseInvoice)}" target="_blank" rel="noopener noreferrer" class="document-link">
                                    ${escapeHtml(doc.DocName)}
                                </a>`
                            : (escapeHtml(doc.DocName) || '-')
                        }</td>
                        ${extraCells}
                        <td>${formatMoney(doc.TotalValue)}</td>
                        <td>${formatDate(doc.DueDate)}</td>
                        <td>${estadoBadge(doc)}</td>
                        <td style="text-align:right;">${formatMoney(doc.Deuda)}</td>
                        <td style="text-align:right;">${formatMoney(doc.Saldo)}</td>
                    </tr>
                `;
            }).join('');
        }

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

    // ---- Modal: Realizar recibo de pago ----
    function getSelectedDocs() {
        const checked = docsBody.querySelectorAll('.doc-check:checked');

        return Array.from(checked)
            .map((cb) => currentDocs[Number(cb.closest('tr').dataset.docIndex)])
            .filter(Boolean);
    }

    function openPaymentModal() {
        selectedDocs = getSelectedDocs();
        if (!selectedDocs.length) return;

        let total = 0;

        modalDocsBody.innerHTML = selectedDocs.map((doc) => {
            total += Number(doc.Saldo) || 0;

            return `
                <tr data-saldo="${Number(doc.Saldo) || 0}" data-documento="${escapeHtml(doc.DueName)}" data-json='${JSON.stringify(doc)}'>
                    <td><span class="prefix-tag">${escapeHtml(doc.DuePrefix)}</span></td>
                    <td>${escapeHtml(doc.DueName)}</td>
                    <td>${escapeHtml(doc.DocName)}</td>
                    <td>${formatDate(doc.DueDate)}</td>
                    <td style="text-align:right;">${formatMoney(doc.Saldo)}</td>
                </tr>
            `;
        }).join('');

        modalTotalEl.textContent = formatMoney(total);

        paymentTipo.value = '';
        paymentAction.value = '';
        paymentSource.value = '';
        paymentDate.value = new Intl.DateTimeFormat('en-CA', { timeZone: 'America/Bogota' }).format(new Date());
        paymentObservations.value = '';
        resetPaymentFile(); // limpia foto de una carga anterior

        modalOverlay.classList.add('show');
    }

    function closePaymentModal() {
        modalOverlay.classList.remove('show');
    }

    btnPayment.addEventListener('click', openPaymentModal);
    modalClose.addEventListener('click', closePaymentModal);
    modalCancel.addEventListener('click', closePaymentModal);

    modalOverlay.addEventListener('click', (e) => {
        if (e.target === modalOverlay) closePaymentModal();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modalOverlay.classList.contains('show')) closePaymentModal();
    });

    modalConfirm.addEventListener('click', async () => {
        if (!paymentTipo.value || !paymentAction.value || !paymentSource.value || !paymentDate.value || !paymentFile || !paymentObservations.value) {
            Swal.fire({
                icon: 'warning',
                title: 'Campos incompletos',
                text: 'Completa todos los campos antes de confirmar.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }

        const result = await Swal.fire({
            icon: 'warning',
            title: '¿Realizar pago?',
            text: 'Esta acción no se puede deshacer.',
            showCancelButton: true,
            confirmButtonText: 'Sí, realizar pago',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6'
        });
        if (!result.isConfirmed) return;

        const json = Array.from(document.querySelectorAll('#modalDocsBody tr')).map(tr => JSON.parse(tr.dataset.json));
        const documentos = Array.from(document.querySelectorAll('#modalDocsBody tr')).map(tr => tr.dataset.documento);
        const valor = Array.from(document.querySelectorAll('#modalDocsBody tr')).map(tr => Number(tr.dataset.saldo) || 0).reduce((sum, v) => sum + v, 0);

        const formData = new FormData();
        formData.append('proveedor', JSON.stringify(currentProvider));
        formData.append('tipo', paymentTipo.value);
        formData.append('accion', paymentAction.value);
        formData.append('origen', paymentSource.value);
        formData.append('fecha', paymentDate.value);
        formData.append('observaciones', paymentObservations.value);
        formData.append('valor', valor);
        formData.append('documentos', JSON.stringify(documentos));
        formData.append('json', JSON.stringify(json));
        if (paymentFile) formData.append('comprobante', paymentFile);

        // Guardamos el contenido original del boton para poder restaurarlo despues
        const originalConfirmHTML = modalConfirm.innerHTML;

        modalConfirm.disabled = true;
        modalConfirm.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>Procesando...`;

        Swal.fire({
            title: 'Procesando recibo de pago',
            text: 'Por favor espera, no cierres esta ventana...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        try {
            const response = await fetch('{{ route("siigo.accounts_payment") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: formData
            });

            let data;
            try {
                data = await response.json();
            } catch (parseError) {
                throw new Error('El servidor respondio de forma inesperada. Intenta nuevamente.');
            }

            if (!response.ok || !data.success) {
                if (data.errors) {
                    const primerError = Object.values(data.errors)[0][0];
                    throw new Error(primerError);
                }
                throw new Error(data.message || 'Ocurrio un error procesando el recibo de pago.');
            }

            await Swal.fire({
                icon: 'success',
                title: 'Listo',
                text: data.message || 'Recibo de pago procesado correctamente.',
                confirmButtonColor: '#3085d6'
            });
            closePaymentModal();
            window.location.reload();

        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'No se pudo procesar el pago',
                text: error.message || 'Ocurrio un error inesperado.',
                confirmButtonColor: '#d33'
            });
        } finally {
            modalConfirm.disabled = false;
            modalConfirm.innerHTML = originalConfirmHTML;
        }
    });

    resetView();
})();
</script>

</body>
</html>
