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
        font-size: 1.5rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 0.3rem;
    }

    .subtitle {
        font-size: 0.85rem;
        color: #6b7280;
        margin-bottom: 1.5rem;
    }

    /* ---- Inputs ---- */
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

    .combo-input {
        width: 100%;
        padding: 0.65rem 0.85rem;
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

    .btn-expand-payment {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        padding: 0;
        margin: 0;
        border: none;
        outline: none;
        background: transparent;
        color: #64748b;
        cursor: pointer;
        border-radius: 50%;
        transition: all 0.2s ease;
    }

    .btn-expand-payment:hover {
        background: #f1f5f9;
        color: #2563eb;
    }

    .btn-expand-payment:focus {
        outline: none;
        box-shadow: none;
    }

    .btn-expand-payment .expand-icon {
        width: 20px;
        height: 20px;
        transition: transform 0.2s ease;
    }

    .combo-input::placeholder { color: #9ca3af; }

    .combo-input:focus {
        outline: none;
        border-color: #16a34a;
        background: #ffffff;
    }

    /* ---- Tabla de proveedores ---- */
    .providers-toolbar {
        display: flex;
        gap: 0.8rem;
        margin-bottom: 1.1rem;
        align-items: center;
        flex-wrap: wrap;
    }

    .providers-toolbar .combo-input { max-width: 480px; }
    .providers-toolbar select.combo-input { max-width: 110px; }
    .providers-toolbar select#providerTypeFilter.combo-input { max-width: 220px; }

    .btn-sync-providers {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        background: #eef2ff;
        border: 1px solid #c7d2fe;
        border-radius: 10px;
        padding: 0.62rem 0.95rem;
        font-size: 0.82rem;
        font-weight: 600;
        color: #4338ca;
        cursor: pointer;
        white-space: nowrap;
        transition: background 0.2s ease, opacity 0.2s ease;
    }

    .btn-sync-providers:hover:not(:disabled) { background: #e0e7ff; }

    .btn-sync-providers:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .btn-sync-providers svg {
        width: 15px;
        height: 15px;
        flex-shrink: 0;
    }

    .provider-row { cursor: pointer; }
    .provider-row:hover td { background: #f0fdf4; }
    .provider-sub { font-size: 0.72rem; color: #9ca3af; margin-top: 0.1rem; }

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

    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 0.5rem 0.9rem;
        font-size: 0.82rem;
        font-weight: 600;
        color: #374151;
        cursor: pointer;
        margin-bottom: 1.2rem;
    }

    .btn-back:hover { background: #e5e7eb; }

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

    /* ---- Tabs Documentos / Pagos ---- */
    .tabs-bar {
        display: flex;
        gap: 0.4rem;
        border-bottom: 1px solid #e5e7eb;
        margin-bottom: 1.1rem;
    }

    .tab-btn {
        background: none;
        border: none;
        padding: 0.6rem 1.1rem;
        font-size: 0.85rem;
        font-weight: 600;
        color: #6b7280;
        cursor: pointer;
        border-bottom: 2px solid transparent;
        margin-bottom: -1px;
        transition: color 0.15s ease, border-color 0.15s ease;
    }

    .tab-btn:hover { color: #1f2937; }

    .tab-btn.active {
        color: #16a34a;
        border-bottom-color: #16a34a;
    }

    .tab-panel { display: none; }
    .tab-panel.show { display: block; }

    /* ---- Loading ---- */
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

    /* ---- Tablas ---- */
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

    /* Botón de "ver detalle" (ojito) en la tabla de pagos */
    .btn-icon-eye {
        background: none;
        border: none;
        cursor: pointer;
        color: #6b7280;
        padding: 0.3rem;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: color 0.15s ease, background 0.15s ease;
    }

    .btn-icon-eye svg { width: 17px; height: 17px; }

    .btn-icon-eye:hover {
        color: #16a34a;
        background: #f0fdf4;
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

    .totals-actions {
        display: flex;
        align-items: center;
        gap: 0.8rem;
        flex-wrap: wrap;
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

    .btn-conciliation {
        background: #2563eb;
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

    .btn-conciliation:hover { background: #1d4ed8; }

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

    /* ---- Botón anticipo (naranja) ---- */
    .btn-advance-wrap {
        display: none;
        margin-bottom: 1.3rem;
    }

    .btn-advance-wrap.show { display: block; }

    .btn-advance {
        background: #f97316;
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

    .btn-advance:hover { background: #ea580c; }

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

    /* ---- Modales ---- */
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
        display: flex;
        flex-direction: column;
        box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3);
    }

    .modal-document {
        max-width: 920px;
        max-height: 90vh;
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

    /* Botón primario del modal de anticipo, en naranja para diferenciarlo */
    .btn-primary-advance {
        background: #f97316;
    }

    .btn-primary-advance:hover { background: #ea580c; }

    .btn-primary-conciliation {
        background: #2563eb;
    }

    .btn-primary-conciliation:hover { background: #1d4ed8; }

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

    .cover-full { color: #16a34a !important; font-weight: 700; }
    .cover-partial { color: #c2410c !important; font-weight: 700; }
    .cover-expired { color: #c20c0c !important; font-weight: 700; }
    .cover-none { color: #9ca3af !important; font-weight: 600; }

    .conciliation-summary {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
        align-items: flex-end;
        font-size: 0.88rem;
        color: #374151;
    }

    .conciliation-summary .row {
        display: flex;
        gap: 0.5rem;
    }

    .conciliation-summary .row strong {
        font-size: 1rem;
        color: #1f2937;
    }

    @media (max-width: 768px) {
        .wrapper { padding: 0; }

        .card {
            padding: 1.3rem 1.1rem;
            border-radius: 12px;
        }

        .title { font-size: 1.5rem; }
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
        .btn-advance { width: 100%; }
        .btn-conciliation { width: 100%; }

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

        .tabs-bar { gap: 0.2rem; }
        .tab-btn { padding: 0.55rem 0.8rem; font-size: 0.8rem; }

        .providers-toolbar { flex-direction: column; align-items: stretch; }
        .providers-toolbar .combo-input,
        .providers-toolbar select.combo-input,
        .providers-toolbar select#providerTypeFilter.combo-input { max-width: 100%; }
        .btn-sync-providers { width: 100%; justify-content: center; }
    }

    .btn-sync-providers .spinning {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }
</style>
</head>
<body>

<div class="wrapper">
    <div class="card">

        <!-- ===== VISTA 1: listado de proveedores ===== -->
        <div id="providersView">
            <div class="title">Cuentas por pagar</div>
            <div class="subtitle">Selecciona un proveedor para gestionar sus documentos y pagos.</div>

            <div class="providers-toolbar">
                <input
                    type="text"
                    id="providerSearch"
                    class="combo-input"
                    placeholder="Filtrar por nombre o identificación..."
                    autocomplete="off"
                >
                <select id="providerTypeFilter" class="combo-input">
                    <option value="">Todos los tipos</option>
                </select>
                <select id="providerPageSize" class="combo-input">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <button type="button" class="btn-sync-providers" id="btnSyncProviders">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="23 4 23 10 17 10"/>
                        <polyline points="1 20 1 14 7 14"/>
                        <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                    </svg>
                </button>
            </div>

            <div class="loading-state" id="providersLoading">
                <div class="spinner"></div>
                <div class="loading-text">Cargando proveedores...</div>
            </div>

            <div class="error-state" id="providersError" style="display:none;">
                No se pudieron cargar los proveedores.
                <button type="button" class="btn-secondary" id="providersRetry" style="margin-left:.6rem;">Reintentar</button>
            </div>

            <div class="docs-scroll" id="providersScroll" style="display:none;">
                <table class="docs-table" style="min-width: 820px;">
                    <thead>
                        <tr>
                            <th>Proveedor</th>
                            <th>Identificación</th>
                            <th>Tipo</th>
                            <th style="text-align:right;">Por vencer</th>
                            <th style="text-align:right;">Vencido</th>
                            <th style="text-align:right;">A favor</th>
                            <th style="text-align:right;">Saldo</th>
                        </tr>
                    </thead>
                    <tbody id="providersBody"></tbody>
                </table>
            </div>

            <div class="pagination" id="providersPagination">
                <span id="providersInfo"></span>
                <div class="pagination-buttons" id="providersPages"></div>
            </div>
        </div>

        <!-- ===== VISTA 2: detalle del proveedor ===== -->
        <div id="detailView" style="display:none;">
            <button type="button" class="btn-back" id="btnBackToProviders">← Volver a proveedores</button>

            <div class="title" id="detailProviderName"></div>
            <div class="subtitle" id="detailProviderId"></div>

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
                    Este proveedor tiene un saldo a favor de <strong id="favorAlertAmount">$0</strong>. Si deseas realizar el pago, puedes hacerlo normalmente. Si prefieres utilizar este saldo a favor, puedes generar directamente desde el sistema el comprobante contable para aplicarlo a las facturas correspondientes.
                </div>
            </div>

            <div class="btn-advance-wrap" id="btnAdvanceWrap">
                <button type="button" class="btn-advance" id="btnAdvance">Realizar anticipo</button>
            </div>

            <div class="docs-wrap" id="docsWrap">

                <div class="tabs-bar" id="tabsBar">
                    <button type="button" class="tab-btn active" id="tabDocumentos">Documentos</button>
                    <button type="button" class="tab-btn" id="tabPagos">Pagos</button>
                </div>

                <!-- ---- Panel: Documentos ---- -->
                <div class="tab-panel show" id="panelDocumentos">

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

                <!-- ---- Panel: Pagos ---- -->
                <div class="tab-panel" id="panelPagos">

                    <div class="loading-state" id="paymentsLoadingState">
                        <div class="spinner"></div>
                        <div class="loading-text">Espera un momento, cargando los pagos del proveedor...</div>
                    </div>

                    <div class="docs-scroll" id="paymentsScroll" style="display:none;">
                        <table class="docs-table" id="paymentsTable">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Mes</th>
                                    <th>Pagos</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody id="paymentsBody"></tbody>
                        </table>
                    </div>

                </div>

            </div>
        </div>

    </div>

    <div class="totals-bar" id="totalsBar">
        <div class="totals-info">
            <div class="count"><span id="selCount">0</span> documento(s) seleccionado(s)</div>
            <div class="amount">Total a pagar: <span id="selTotal">$0</span></div>
        </div>
        <div class="totals-actions">
            <button type="button" class="btn-conciliation" id="btnConciliation">Realizar cruce contable</button>
            <button type="button" class="btn-payment" id="btnPayment">Realizar recibo de pago</button>
        </div>
    </div>

    <a href="{{ route('home') }}" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Volver a acciones disponibles
    </a>
</div>

<!-- ---- Modal: Detalle de pago ---- -->
<div class="modal-overlay" id="paymentDetailModalOverlay">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title" id="paymentDetailModalTitle">Detalle del pago</div>
            <button type="button" class="modal-close" id="paymentDetailModalClose">&times;</button>
        </div>

        <div class="modal-body" style="padding:0;">
            <iframe id="paymentDetailIframe" style="width:100%; height:65vh; border:none; display:block;"></iframe>
        </div>
    </div>
</div>

<!-- ---- Modal: Realizar recibo de pago ---- -->
<div class="modal-overlay" id="paymentModalOverlay">
    <div class="modal modal-document">
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
                        @if ($type_receipt)
                            <option value="{{ $type_receipt['ERPDocumentTypeId'] }}">
                                {{ $type_receipt['DocClass'] }}-{{ $type_receipt['Code'] }} · {{ $type_receipt['Title'] }}
                            </option>
                        @endif
                    </select>
                </div>

                <div class="field-group">
                    <label class="field-label" for="paymentAction">Realizar un</label>
                    <select class="combo-input" id="paymentAction">
                        <option value="">Selecciona...</option>
                        <option value="0" selected>Abono a deuda</option>
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

                <div class="field-group">
                    <label class="field-label" for="paymentTotalInput">Valor a pagar</label>
                    <input type="number" class="combo-input" id="paymentTotalInput" min="0" step="0.01" placeholder="0">
                </div>

                <div class="field-group"></div>

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
                            <th style="text-align:right;">Cubre</th>
                        </tr>
                    </thead>
                    <tbody id="modalDocsBody"></tbody>
                </table>
            </div>

            <div class="conciliation-summary">
                <div class="row"><span>Saldo total documentos:</span> <strong id="modalTotal">$0</strong></div>
                <div class="row"><span>Total cubierto:</span> <strong id="modalCoveredTotal">$0</strong></div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn-secondary" id="modalCancel">Cancelar</button>
            <button type="button" class="btn-primary" id="modalPaymentConfirm">Confirmar recibo de pago</button>
        </div>
    </div>
</div>

<!-- ---- Modal: Realizar anticipo ---- -->
<div class="modal-overlay" id="advanceModalOverlay">
    <div class="modal modal-document">
        <div class="modal-header">
            <div class="modal-title">Realizar anticipo</div>
            <button type="button" class="modal-close" id="advanceModalClose">&times;</button>
        </div>

        <div class="modal-body">
            <div class="modal-grid">

                <div class="field-group">
                    <label class="field-label" for="advanceTipo">Tipo</label>
                    <select class="combo-input" id="advanceTipo">
                        <option value="">Selecciona...</option>
                        @if ($type_receipt)
                            <option value="{{ $type_receipt['ERPDocumentTypeId'] }}">
                                {{ $type_receipt['DocClass'] }}-{{ $type_receipt['Code'] }} · {{ $type_receipt['Title'] }}
                            </option>
                        @endif
                    </select>
                </div>

                <div class="field-group">
                    <label class="field-label" for="advanceAction">Realizar un</label>
                    <select class="combo-input" id="advanceAction">
                        <option value="">Selecciona...</option>
                        <option value="1" selected>Anticipo</option>
                    </select>
                </div>

                <div class="field-group">
                    <label class="field-label" for="advanceSource">De donde sale el dinero</label>
                    <select class="combo-input" id="advanceSource">
                        <option value="">Selecciona...</option>
                        @foreach ($bank_accounts as $bank_account)
                            <option value="{{ $bank_account['ACPaymentMeanID'] }}">{{ $bank_account['PaymentMeanAccount'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field-group">
                    <label class="field-label" for="advanceDate">Fecha de elaboración</label>
                    <input type="date" class="combo-input" id="advanceDate">
                </div>

                <div class="field-group">
                    <label class="field-label" for="advanceValue">Valor pagado</label>
                    <input type="number" class="combo-input" id="advanceValue" min="0" step="0.01" placeholder="0">
                </div>

                <div class="field-group"></div>

                <div class="modal-field-group">
                    <label class="field-label" for="advanceObservations">Observaciones</label>
                    <textarea class="combo-input" id="advanceObservations" rows="5" placeholder="Escribe cualquier observación sobre este anticipo..."></textarea>
                </div>

                <div class="modal-field-group">
                    <label class="field-label">Comprobante</label>

                    <div class="payment-dropzone" id="advanceDropzone">
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
                        <input type="file" id="advanceFileInput" class="payment-file-input" accept=".jpg,.jpeg,.png">
                    </div>

                    <div class="payment-file-preview" id="advanceFilePreview">
                        <img id="advanceFileImg" alt="Vista previa">
                        <div class="payment-file-info">
                            <div class="payment-file-name" id="advanceFileName"></div>
                            <div class="payment-file-size" id="advanceFileSize"></div>
                        </div>
                        <button type="button" class="payment-file-remove" id="advanceFileRemove">&times;</button>
                    </div>

                    <div class="payment-file-error" id="advanceFileError"></div>
                </div>
            </div>

            <div class="modal-total">
                Total a anticipar: <span id="advanceModalTotal">$0</span>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn-secondary" id="advanceModalCancel">Cancelar</button>
            <button type="button" class="btn-primary btn-primary-advance" id="advanceModalConfirm">Confirmar anticipo</button>
        </div>
    </div>
</div>

<!-- ---- Modal: Realizar cruce contable (conciliación) ---- -->
<div class="modal-overlay" id="conciliationModalOverlay">
    <div class="modal modal-document">
        <div class="modal-header">
            <div class="modal-title">Realizar cruce contable</div>
            <button type="button" class="modal-close" id="conciliationModalClose">&times;</button>
        </div>

        <div class="modal-body">
            <div class="modal-grid">
                <!-- Columna izquierda -->
                <div class="modal-left">
                    <div class="field-group">
                        <label class="field-label" for="conciliationTipo">Tipo documento</label>
                        <select class="combo-input" id="conciliationTipo">
                            <option value="">Selecciona...</option>
                            @foreach ($type_documents as $type_document)
                                <option value="{{ $type_document['ERPDocumentTypeID'] }}">{{ "{$type_document['ERPDocClass']}-{$type_document['ERPDocCode']}-{$type_document['Name']}" }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field-group">
                        <label class="field-label" for="conciliationDate">Fecha de elaboración</label>
                        <input type="date" class="combo-input" id="conciliationDate">
                    </div>
                </div>

                <!-- Columna derecha -->
                <div class="modal-field-group">
                    <label class="field-label" for="conciliationObservations">
                        Observaciones
                    </label>

                    <textarea class="combo-input" id="conciliationObservations" rows="5" placeholder="Escribe cualquier observación sobre este cruce..."></textarea>
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
                            <th style="text-align:right;">Cubre</th>
                        </tr>
                    </thead>
                    <tbody id="conciliationDocsBody"></tbody>
                </table>
            </div>

            <div class="conciliation-summary">
                <div class="row"><span>Saldo a favor disponible (RP):</span> <strong id="conciliationRpTotal">$0</strong></div>
                <div class="row"><span>Total a conciliar:</span> <strong id="conciliationDebtTotal">$0</strong></div>
                <div class="row"><span>Total cubierto:</span> <strong id="conciliationCoveredTotal">$0</strong></div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn-secondary" id="conciliationModalCancel">Cancelar</button>
            <button type="button" class="btn-primary btn-primary-conciliation" id="conciliationModalConfirm">Confirmar cruce contable</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const PROVIDERS_URL = "{{ route('siigo.accounts_payable_providers') }}";
const DOCUMENTS_URL_TEMPLATE = "{{ route('siigo.account_payable.documents', ['accountId' => '__ID__']) }}";
const PAYMENT_URL = "{{ route('siigo.payment_html', ['acEntryId' => '__ID__']) }}";



(function () {
    // ---- Proveedores (lista) ----
    let providers                  = [];
    const searchInput              = document.getElementById('providerSearch');
    const providerTypeFilter       = document.getElementById('providerTypeFilter');
    const pageSizeSelect           = document.getElementById('providerPageSize');
    const btnSyncProviders         = document.getElementById('btnSyncProviders');
    const providersView            = document.getElementById('providersView');
    const detailView               = document.getElementById('detailView');
    const providersLoading         = document.getElementById('providersLoading');
    const providersError           = document.getElementById('providersError');
    const providersRetry           = document.getElementById('providersRetry');
    const providersScroll          = document.getElementById('providersScroll');
    const providersBody            = document.getElementById('providersBody');
    const providersPagination      = document.getElementById('providersPagination');
    const providersInfo            = document.getElementById('providersInfo');
    const providersPages           = document.getElementById('providersPages');
    const btnBackToProviders       = document.getElementById('btnBackToProviders');
    const detailProviderName       = document.getElementById('detailProviderName');
    const detailProviderId         = document.getElementById('detailProviderId');

    // ---- Detalle ----
    const allDataCheck             = document.getElementById('allDataCheck');
    const legend                   = document.getElementById('legend');
    const summaryGrid              = document.getElementById('summaryGrid');
    const favorAlert               = document.getElementById('favorAlert');
    const docsWrap                 = document.getElementById('docsWrap');
    const loadingState             = document.getElementById('loadingState');
    const docsScroll               = document.getElementById('docsScroll');
    const docsTable                = document.getElementById('docsTable');
    const docsBody                 = document.getElementById('docsBody');
    const totalsBar                = document.getElementById('totalsBar');
    const checkAll                 = document.getElementById('checkAll');
    const btnPayment               = document.getElementById('btnPayment');
    const btnAdvanceWrap           = document.getElementById('btnAdvanceWrap');

    // Tabs Documentos / Pagos
    const tabDocumentos            = document.getElementById('tabDocumentos');
    const tabPagos                 = document.getElementById('tabPagos');
    const panelDocumentos          = document.getElementById('panelDocumentos');
    const panelPagos               = document.getElementById('panelPagos');
    const paymentsLoadingState     = document.getElementById('paymentsLoadingState');
    const paymentsScroll           = document.getElementById('paymentsScroll');
    const paymentsBody             = document.getElementById('paymentsBody');

    // Modal detalle de pago
    const paymentDetailModalOverlay = document.getElementById('paymentDetailModalOverlay');
    const paymentDetailModalClose   = document.getElementById('paymentDetailModalClose');
    const paymentDetailModalTitle   = document.getElementById('paymentDetailModalTitle');
    const paymentDetailIframe       = document.getElementById('paymentDetailIframe');

    // Modal recibo de pago
    const modalOverlay             = document.getElementById('paymentModalOverlay');
    const modalClose               = document.getElementById('modalClose');
    const modalCancel              = document.getElementById('modalCancel');
    const modalPaymentConfirm      = document.getElementById('modalPaymentConfirm');
    const modalDocsBody            = document.getElementById('modalDocsBody');
    const modalTotalEl             = document.getElementById('modalTotal');
    const modalCoveredTotalEl      = document.getElementById('modalCoveredTotal');
    const paymentTotalInput        = document.getElementById('paymentTotalInput');
    const paymentTipo              = document.getElementById('paymentTipo');
    const paymentAction            = document.getElementById('paymentAction');
    const paymentSource            = document.getElementById('paymentSource');
    const paymentDate              = document.getElementById('paymentDate');

    const paymentObservations      = document.getElementById('paymentObservations');
    const paymentDropzone          = document.getElementById('paymentDropzone');
    const paymentFileInput         = document.getElementById('paymentFileInput');
    const paymentFilePreview       = document.getElementById('paymentFilePreview');
    const paymentFileImg           = document.getElementById('paymentFileImg');
    const paymentFileName          = document.getElementById('paymentFileName');
    const paymentFileSize          = document.getElementById('paymentFileSize');
    const paymentFileRemove        = document.getElementById('paymentFileRemove');
    const paymentFileError         = document.getElementById('paymentFileError');

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

    let providerPage = 1;
    let pageSize = 10;
    let providersRequestToken = 0; // evita que una respuesta vieja pise la lista de proveedores
    let requestToken = 0;          // idem, para documentos
    let paymentsRequestToken = 0;  // idem, para la pestaña de pagos
    let currentProvider = null;
    let currentDocs = [];    // documentos actualmente renderizados en la tabla principal
    let selectedDocs = [];   // documentos elegidos al abrir el modal de pago
    let paymentsData = null; // null = aún no se ha cargado para el proveedor actual
    let activeTab = 'documentos';

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

    function isValid(doc) {
        let prefix = (doc.DuePrefix || '').toUpperCase();
        return (prefix === 'FC' || prefix === 'RM' || prefix.startsWith('RP') || prefix.startsWith('DES')) && !doc.IsAnnulled;
    }

    function isSelectable(doc) {
        let prefix = (doc.DuePrefix || '').toUpperCase();
        return (prefix === 'FC' || prefix === 'RM' || prefix.startsWith('DES')) && !doc.IsAnnulled;
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

    // =====================================================================
    // Tabla de proveedores (paginada, se carga por endpoint)
    // =====================================================================
    function providerVencido(p) {
        return (Number(p.Expired1to30) || 0)
            + (Number(p.Expired31to60) || 0)
            + (Number(p.Expired61to90) || 0)
            + (Number(p.ExpiredMoreTo91) || 0);
    }

    // Recalcula las opciones del select de "Tipo" a partir de los proveedores cargados,
    // conservando la selección actual si sigue existiendo entre los tipos disponibles.
    function populateTypeFilter() {
        const currentValue = providerTypeFilter.value;

        const types = Array.from(new Set(
            providers.map((p) => (p.Type || '').trim()).filter(Boolean)
        )).sort((a, b) => a.localeCompare(b, 'es'));

        providerTypeFilter.innerHTML = '<option value="">Todos los tipos</option>' +
            types.map((t) => `<option value="${escapeHtml(t)}">${escapeHtml(t)}</option>`).join('');

        if (types.includes(currentValue)) {
            providerTypeFilter.value = currentValue;
        }
    }

    function getFilteredProviders() {
        const q = searchInput.value.trim().toLowerCase();
        const typeFilter = providerTypeFilter.value;

        return providers.filter((p) => {
            const matchesQuery = !q
                || (p.FullName || '').toLowerCase().includes(q)
                || (p.CompanyName || '').toLowerCase().includes(q)
                || String(p.Identification || '').toLowerCase().includes(q);

            const matchesType = !typeFilter || (p.Type || '') === typeFilter;

            return matchesQuery && matchesType;
        });
    }

    function renderProviders() {
        const list = getFilteredProviders();
        const totalPages = Math.max(1, Math.ceil(list.length / pageSize));
        if (providerPage > totalPages) providerPage = totalPages;

        const start = (providerPage - 1) * pageSize;
        const items = list.slice(start, start + pageSize);

        providersBody.innerHTML = items.length
            ? items.map((p) => {
                return `
                <tr style="font-weight: 700;" class="provider-row" data-account-id="${escapeHtml(p.AccountID)}">
                    <td>
                        ${escapeHtml(p.FullName)}
                        ${p.CompanyName ? `<div class="provider-sub">${escapeHtml(p.CompanyName)}</div>` : ''}
                    </td>
                    <td style="font-weight: 700;" class="provider-row">${escapeHtml(p.Identification)}</td>
                    <td>${escapeHtml(p.Type)}</td>
                    <td style="text-align:right;" class="cover-partial">${formatMoney(p.BalanceToExpire)}</td>
                    <td style="text-align:right;" class="cover-expired">${formatMoney(providerVencido(p))}</td>
                    <td style="text-align:right;" class="cover-full">${formatMoney(p.BalanceInFavor)}</td>
                    <td style="text-align:right;" class="cover-none">${formatMoney(p.TotalBalance)}</td>
                </tr>
            `;
            }).join('')
            : '<tr><td colspan="7" class="empty-state">Sin resultados.</td></tr>';

        providersInfo.textContent = list.length
            ? `Mostrando ${start + 1}-${start + items.length} de ${list.length}`
            : '0 resultados';

        renderPagination(totalPages);
    }

    function renderPagination(totalPages) {
        const pages = [];
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || Math.abs(i - providerPage) <= 2) pages.push(i);
            else if (pages[pages.length - 1] !== '…') pages.push('…');
        }

        providersPages.innerHTML =
            `<button type="button" class="page-btn" data-page="${providerPage - 1}" ${providerPage === 1 ? 'disabled' : ''}>‹</button>` +
            pages.map((p) => p === '…'
                ? '<span class="page-dots">…</span>'
                : `<button type="button" class="page-btn ${p === providerPage ? 'active' : ''}" data-page="${p}">${p}</button>`
            ).join('') +
            `<button type="button" class="page-btn" data-page="${providerPage + 1}" ${providerPage === totalPages ? 'disabled' : ''}>›</button>`;
    }

    // silent = true: no muestra loading ni errores (se usa para refrescar en segundo plano)
    // sync = true: le pide al backend que sincronice tipo y nombre comercial (consulta más lenta)
    async function loadProviders({ silent = false, sync = false } = {}) {
        const myToken = ++providersRequestToken;

        if (!silent) {
            providersLoading.classList.add('show');
            providersScroll.style.display = 'none';
            providersPagination.style.display = 'none';
            providersError.style.display = 'none';
        }

        let url = PROVIDERS_URL;
        if (sync) url += (url.includes('?') ? '&' : '?') + 'sync=true';

        try {
            const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (myToken !== providersRequestToken) return;
            if (!response.ok) throw new Error('request_failed');

            const data = await response.json();
            providers = Object.values(data.providers || []);

            populateTypeFilter();

            providersError.style.display = 'none';
            providersScroll.style.display = 'block';
            providersPagination.style.display = 'flex';
            renderProviders();
        } catch (err) {
            if (myToken !== providersRequestToken) return;
            if (!silent) providersError.style.display = 'block';
        } finally {
            if (myToken === providersRequestToken) providersLoading.classList.remove('show');
        }
    }

    searchInput.addEventListener('input', () => {
        providerPage = 1;
        renderProviders();
    });

    providerTypeFilter.addEventListener('change', () => {
        providerPage = 1;
        renderProviders();
    });

    pageSizeSelect.addEventListener('change', () => {
        pageSize = Number(pageSizeSelect.value) || 10;
        providerPage = 1;
        renderProviders();
    });

    providersPages.addEventListener('click', (e) => {
        const btn = e.target.closest('.page-btn');
        if (!btn || btn.disabled) return;
        providerPage = Number(btn.dataset.page);
        renderProviders();
    });

    providersRetry.addEventListener('click', () => loadProviders());

    // ---- Sincronización manual de proveedores (tipo y nombre comercial) ----
    btnSyncProviders.addEventListener('click', async () => {
        const result = await Swal.fire({
            icon: 'info',
            title: 'Sincronizar proveedores',
            text: 'Esta acción puede demorar varios minutos, ya que se está consultando el tipo de proveedor y el nombre comercial de cada uno. ¿Deseas continuar?',
            showCancelButton: true,
            confirmButtonText: 'Sí, sincronizar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#4338ca',
            cancelButtonColor: '#6b7280',
        });

        if (!result.isConfirmed) return;

        btnSyncProviders.disabled = true;
        btnSyncProviders.innerHTML = `<svg class="icon-sync spinning" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="23 4 23 10 17 10"/>
                <polyline points="1 20 1 14 7 14"/>
                <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
            </svg>`;

        Swal.fire({
            title: 'Sincronizando proveedores',
            text: 'Por favor espera, este proceso puede demorar varios minutos. No cierres esta ventana...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => Swal.showLoading(),
        });

        try {
            await loadProviders({ sync: true });

            await Swal.fire({
                icon: 'success',
                title: 'Sincronización completa',
                text: 'Los proveedores se sincronizaron correctamente.',
                confirmButtonColor: '#3085d6',
            });
        } catch (err) {
            await Swal.fire({
                icon: 'error',
                title: 'No se pudo sincronizar',
                text: 'Ocurrió un error al sincronizar los proveedores. Intenta de nuevo.',
                confirmButtonColor: '#d33',
            });
        } finally {
            // El botón queda deshabilitado de forma permanente tras la sincronización
            // para evitar que se dispare varias veces esta consulta pesada.
            btnSyncProviders.innerHTML = `
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
            `;
        }
    });

    providersBody.addEventListener('click', (e) => {
        const row = e.target.closest('.provider-row');
        if (!row) return;

        const provider = providers.find((p) => String(p.AccountID) === row.dataset.accountId);
        if (provider) showDetailView(provider);
    });

    // =====================================================================
    // Navegación lista <-> detalle
    // =====================================================================
    function showDetailView(provider) {
        currentProvider = provider;
        paymentsData = null;

        detailProviderName.textContent = provider.FullName || '';
        detailProviderId.textContent = [provider.CompanyName, provider.Identification].filter(Boolean).join(' · ');

        providersView.style.display = 'none';
        detailView.style.display = 'block';

        switchTab('documentos');
        loadProvider(provider);
    }

    function showProvidersView() {
        requestToken++;          // invalida fetches en curso
        paymentsRequestToken++;
        currentProvider = null;
        paymentsData = null;

        resetView();
        detailView.style.display = 'none';
        providersView.style.display = 'block';

        loadProviders({ silent: true }); // refresca los saldos de la tabla
    }

    btnBackToProviders.addEventListener('click', showProvidersView);

    // Si el usuario cambia el checkbox y ya hay un proveedor abierto, se vuelve a consultar
    allDataCheck.addEventListener('change', () => {
        docsTable.classList.toggle('show-extra', allDataCheck.checked);
        if (currentProvider) loadProvider(currentProvider);
    });

    // ---- Reset del detalle ----
    function resetView() {
        summaryGrid.classList.remove('show');
        legend.classList.remove('show');
        favorAlert.classList.remove('show');
        btnAdvanceWrap.classList.remove('show');
        docsWrap.classList.remove('show');
        loadingState.classList.remove('show');
        docsScroll.style.display = 'none';
        totalsBar.classList.remove('show');
        docsBody.innerHTML = '';
        currentDocs = [];

        paymentsScroll.style.display = 'none';
        paymentsLoadingState.classList.remove('show');
        paymentsBody.innerHTML = '';

        switchTab('documentos');
    }

    // ---- Tabs Documentos / Pagos ----
    function switchTab(tab) {
        activeTab = tab;
        tabDocumentos.classList.toggle('active', tab === 'documentos');
        tabPagos.classList.toggle('active', tab === 'pagos');
        panelDocumentos.classList.toggle('show', tab === 'documentos');
        panelPagos.classList.toggle('show', tab === 'pagos');

        // Solo consulta pagos la primera vez que se entra a esa pestaña para este proveedor
        if (tab === 'pagos' && paymentsData === null && currentProvider) {
            paymentsData = [];
            loadPayments(currentProvider);
        }
    }

    tabDocumentos.addEventListener('click', () => switchTab('documentos'));
    tabPagos.addEventListener('click', () => switchTab('pagos'));

    // ---- Resumen ----
    function calcularResumenDesdeDocumentos(docs) {
        return docs.reduce((acc, doc) => {
            if (!isValid(doc)) return acc; // ignora los que no son válidos

            acc.BalanceToExpire   += Number(doc.PorVencer) || 0;
            acc.TotalBalance      += Number(doc.Saldo) || 0;
            acc.Expired1to30      += Number(doc.VencidoDe1a30) || 0;
            acc.Expired31to60     += Number(doc.VencidoDe31a60) || 0;
            acc.Expired61to90     += Number(doc.VencidoDe61a90) || 0;
            acc.ExpiredMoreTo91   += Number(doc.VencidoMasDe90) || 0;
            acc.BalanceInFavor    += Number(doc.BalanceInFavor) || 0;

            return acc;
        }, {
            BalanceToExpire: 0,
            TotalBalance: 0,
            Expired1to30: 0,
            Expired31to60: 0,
            Expired61to90: 0,
            ExpiredMoreTo91: 0,
            BalanceInFavor: 0,
        });
    }

    function renderSummary(provider) {
        const vencido = providerVencido(provider);
        const balanceToExpire = Number(provider.BalanceToExpire) || 0;
        const balanceInFavor  = Number(provider.BalanceInFavor) || 0;

        document.getElementById('sumDeuda').textContent      = formatMoney(balanceToExpire + vencido);
        document.getElementById('sumFavor').textContent      = formatMoney(balanceInFavor);
        document.getElementById('sumSaldo').textContent      = formatMoney(provider.TotalBalance);
        document.getElementById('sumVencido').textContent    = formatMoney(vencido);
        document.getElementById('sumPorVencer').textContent  = formatMoney(balanceToExpire);
        document.getElementById('sumDocumentos').textContent = '…';

        if (balanceInFavor > 0) {
            document.getElementById('favorAlertAmount').textContent = formatMoney(balanceInFavor);
            favorAlert.classList.add('show');
        } else {
            favorAlert.classList.remove('show');
        }

        summaryGrid.classList.add('show');
        legend.classList.add('show');
        btnAdvanceWrap.classList.add('show');
    }

    // ---- Trae los documentos del proveedor por AJAX y muestra loading mientras tanto ----
    async function loadProvider(provider) {
        const myToken = ++requestToken;
        const allData = allDataCheck.checked;

        renderSummary(provider); // valores iniciales/rápidos mientras carga

        docsWrap.classList.add('show');
        loadingState.classList.add('show');
        docsScroll.style.display = 'none';
        totalsBar.classList.remove('show');
        checkAll.checked = false;
        docsBody.innerHTML = '';

        let url = DOCUMENTS_URL_TEMPLATE.replace('__ID__', encodeURIComponent(provider.AccountID));
        url += (url.includes('?') ? '&' : '?') + 'MsThirdPartyID=' + encodeURIComponent(provider.MsThirdPartyID);
        if (allData) url += (url.includes('?') ? '&' : '?') + 'all_data=1';

        try {
            const response = await fetch(url, {
                headers: { 'Accept': 'application/json' },
            });

            if (myToken !== requestToken) return; // el usuario ya seleccionó otro proveedor

            if (!response.ok) throw new Error('request_failed');

            const data = await response.json();
            const docs = data.documents || [];

            // Recalcula el resumen con los valores reales de los documentos ya cargados
            const resumen = calcularResumenDesdeDocumentos(docs);
            renderSummary(resumen);

            renderDocuments(docs, allData);
            document.getElementById('sumDocumentos').textContent = docs.length;
        } catch (err) {
            if (myToken !== requestToken) return;

            docsBody.innerHTML = '<tr><td colspan="13" class="error-state">No se pudieron cargar los documentos de este proveedor. Intenta de nuevo.</td></tr>';
            docsScroll.style.display = 'block';
            document.getElementById('sumDocumentos').textContent = '-';
        } finally {
            if (myToken === requestToken) {
                loadingState.classList.remove('show');
                docsScroll.style.display = 'block';
            }
        }
    }

    // ---- Trae los pagos del proveedor por AJAX (solo la primera vez que se entra a la pestaña) ----
    async function loadPayments(provider) {
        const myToken = ++paymentsRequestToken;

        paymentsLoadingState.classList.add('show');
        paymentsScroll.style.display = 'none';
        paymentsBody.innerHTML = '';

        let url = DOCUMENTS_URL_TEMPLATE.replace('__ID__', encodeURIComponent(provider.AccountID));
        url += (url.includes('?') ? '&' : '?') + 'type=payment';

        try {
            const response = await fetch(url, {
                headers: { 'Accept': 'application/json' },
            });

            if (myToken !== paymentsRequestToken) return; // el proveedor cambió mientras cargaba

            if (!response.ok) throw new Error('request_failed');

            const data = await response.json();
            paymentsData = data.payments || [];

            renderPayments(paymentsData);
        } catch (err) {
            if (myToken !== paymentsRequestToken) return;

            paymentsData = null; // permite reintentar al volver a entrar a la pestaña
            paymentsBody.innerHTML = '<tr><td colspan="4" class="error-state">No se pudieron cargar los pagos de este proveedor. Intenta de nuevo.</td></tr>';
            paymentsScroll.style.display = 'block';
        } finally {
            if (myToken === paymentsRequestToken) {
                paymentsLoadingState.classList.remove('show');
                paymentsScroll.style.display = 'block';
            }
        }
    }

    function renderPayments(payments) {
        if (!payments.length) {
            paymentsBody.innerHTML = `
                <tr>
                    <td colspan="4" class="empty-state">
                        Este proveedor no tiene pagos registrados.
                    </td>
                </tr>
            `;

            return;
        }

        paymentsBody.innerHTML = payments.map((periodo, index) => `
            <tr class="payment-month-row">
                <td>
                    <button type="button" class="btn-expand-payment" data-month="${index}" title="Mostrar pagos" aria-label="Mostrar pagos">
                        <svg class="expand-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </td>
                <td class="font-black">${escapeHtml(periodo.mes)}</td>
                <td class="font-black">${periodo.detalles.length}</td>
                <td class="font-black">${formatMoney(periodo.total)}</td>
            </tr>

            <tr id="payment-details-${index}" class="payment-details-row" style="display:none;">
                <td colspan="4">
                    <table class="docs-table payment-documents-table">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Documento</th>
                                <th style="min-width:110px;">Fecha</th>
                                <th>Valor</th>
                                <th>Estado</th>
                                <th style="width:60px;text-align:center;">
                                    Ver
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            ${periodo.detalles.map((payment, i) => `
                                <tr class="${payment.IsAnnulled ? 'row-nov1' : ''}">
                                    <td><span class="prefix-tag">${escapeHtml(payment.DocClass || '')}</span></td>
                                    <td>${payment.Link
                                        ? `<a href="${escapeHtml(payment.Link)}" target="_blank" rel="noopener noreferrer" class="document-link">
                                                ${escapeHtml(payment.DocName || '-')}
                                            </a>`
                                        : (escapeHtml(payment.DocName) || '-')}
                                    </td>
                                    <td>${formatDate(payment.DocDate)}</td>
                                    <td style="text-align:right;">${formatMoney(payment.TotalValue)}</td>
                                    <td>${payment.IsAnnulled
                                        ? '<span class="badge badge-v5">Anulado</span>'
                                        : '<span class="badge badge-ok">Activo</span>'
                                    }</td>
                                    <td style="text-align:center;">
                                        <button type="button" class="btn-icon-eye" data-period-index="${index}" data-payment-index="${i}" title="Ver detalle">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                                <circle cx="12" cy="12" r="3"/>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </td>
            </tr>
        `).join('');
    }

    paymentsBody.addEventListener('click', (e) => {
        const expandBtn = e.target.closest('.btn-expand-payment');
        if (!expandBtn) return;

        const index = Number(expandBtn.dataset.month);
        const detailsRow = document.getElementById(`payment-details-${index}`);

        if (!detailsRow) return;

        const icon = expandBtn.querySelector('.expand-icon');
        const isHidden = detailsRow.style.display === 'none';

        detailsRow.style.display = isHidden ? 'table-row' : 'none';

        if (icon) {
            if (isHidden) {
                icon.style.transform = 'rotate(90deg)';
            } else {
                icon.style.transform = 'rotate(0deg)';
            }
        }
    });

    paymentsBody.addEventListener('click', async (e) => {

        const btn = e.target.closest('.btn-icon-eye');

        if (!btn || !paymentsData) return;

        const periodIndex = Number(btn.dataset.periodIndex);
        const paymentIndex = Number(btn.dataset.paymentIndex);
        const periodo = paymentsData[periodIndex];

        if (!periodo || !periodo.detalles) return;

        const payment = periodo.detalles[paymentIndex];

        if (!payment) return;

        paymentDetailModalTitle.textContent = `Detalle · ${payment.DocName || ''}`;
        paymentDetailModalOverlay.classList.add('show');
        paymentDetailIframe.srcdoc = `<p style=" font-family:sans-serif; padding:1rem; color:#6b7280; text-align:center; ">Cargando detalle...</p>`;

        try {
            let url = PAYMENT_URL.replace('__ID__', encodeURIComponent(payment.ACEntryID));

            const response = await fetch(url, {
                headers: { 'Accept': 'application/json' },
            });

            if (!response.ok) {
                throw new Error('No fue posible obtener el detalle del pago.');
            }

            const html = await response.text();

            paymentDetailIframe.srcdoc = html || `<p style="font-family:sans-serif; padding:1rem; color:#6b7280;">Sin detalle disponible.</p>`;

        } catch (error) {
            paymentDetailIframe.srcdoc = `<p style=" font-family:sans-serif; padding:1rem; color:#dc2626;">Error al cargar el detalle del documento.</p>`;
        }
    });

    paymentDetailModalClose.addEventListener('click', () => paymentDetailModalOverlay.classList.remove('show'));

    paymentDetailModalOverlay.addEventListener('click', (e) => {
        if (e.target === paymentDetailModalOverlay) paymentDetailModalOverlay.classList.remove('show');
    });

    function renderDocuments(docs, allData) {
        docsTable.classList.toggle('show-extra', allData);
        currentDocs = docs;

        if (!docs.length) {
            docsBody.innerHTML = '<tr><td colspan="13" class="empty-state">Este proveedor no tiene documentos.</td></tr>';
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

    // =====================================================================
    // Refresco tras pago / anticipo / cruce contable (sin recargar la página)
    // =====================================================================
    async function refreshAfterAction() {
        if (!currentProvider) return;

        const accountId = String(currentProvider.AccountID);
        const provider = currentProvider;
        paymentsData = null; // fuerza a reconsultar los pagos

        // Refresca la lista de proveedores en segundo plano y actualiza el proveedor abierto
        const providersPromise = loadProviders({ silent: true }).then(() => {
            const fresh = providers.find((p) => String(p.AccountID) === accountId);
            if (fresh && currentProvider && String(currentProvider.AccountID) === accountId) {
                currentProvider = fresh;
            }
        });

        // Recarga documentos + tarjetas resumen
        const docsPromise = loadProvider(provider);

        // Si el usuario está viendo la pestaña de pagos, también se recarga
        if (activeTab === 'pagos') {
            paymentsData = [];
            loadPayments(provider);
        }

        await Promise.all([providersPromise, docsPromise]);
    }

    // ---- Modal: Realizar recibo de pago ----
    function getSelectedDocs() {
        const checked = docsBody.querySelectorAll('.doc-check:checked');

        return Array.from(checked)
            .map((cb) => currentDocs[Number(cb.closest('tr').dataset.docIndex)])
            .filter(Boolean);
    }

    // Reparte el "Valor a pagar" en cascada sobre los documentos seleccionados,
    // en el mismo orden en que aparecen en la tabla del modal.
    function renderPaymentCoverage() {
        let remaining = Number(paymentTotalInput.value) || 0;
        let coveredTotal = 0;

        modalDocsBody.innerHTML = selectedDocs.map((doc) => {
            const saldo = Number(doc.Saldo) || 0;
            const covered = Math.max(0, Math.min(remaining, saldo));
            remaining -= covered;
            coveredTotal += covered;

            const coverClass = covered <= 0
                ? 'cover-none'
                : (covered >= saldo ? 'cover-full' : 'cover-partial');

            return `
                <tr data-saldo="${saldo}" data-cubre="${covered}" data-documento="${escapeHtml(doc.DueName)}" data-json='${JSON.stringify(doc)}'>
                    <td><span class="prefix-tag">${escapeHtml(doc.DuePrefix)}</span></td>
                    <td>${escapeHtml(doc.DueName)}</td>
                    <td>${escapeHtml(doc.DocName)}</td>
                    <td>${formatDate(doc.DueDate)}</td>
                    <td style="text-align:right;">${formatMoney(saldo)}</td>
                    <td style="text-align:right;" class="${coverClass}">${formatMoney(covered)}</td>
                </tr>
            `;
        }).join('');

        modalCoveredTotalEl.textContent = formatMoney(coveredTotal);
    }

    paymentTotalInput.addEventListener('input', () => {
        if (Number(paymentTotalInput.value) < 0) paymentTotalInput.value = 0;
        renderPaymentCoverage();
    });

    function openPaymentModal() {
        selectedDocs = getSelectedDocs();
        if (!selectedDocs.length) return;

        const total = selectedDocs.reduce((sum, d) => sum + (Number(d.Saldo) || 0), 0);

        modalTotalEl.textContent = formatMoney(total);
        paymentTotalInput.value = total.toFixed(2);

        paymentTipo.value = '';
        paymentAction.value = '';
        paymentSource.value = '';
        paymentDate.value = new Intl.DateTimeFormat('en-CA', { timeZone: 'America/Bogota' }).format(new Date());
        paymentObservations.value = '';
        resetPaymentFile();

        renderPaymentCoverage();
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
        if (e.key !== 'Escape') return;

        if (modalOverlay.classList.contains('show')) closePaymentModal();
        if (advanceModalOverlay.classList.contains('show')) closeAdvanceModal();
        if (conciliationModalOverlay.classList.contains('show')) closeConciliationModal();
        if (paymentDetailModalOverlay.classList.contains('show')) paymentDetailModalOverlay.classList.remove('show');
    });

    modalPaymentConfirm.addEventListener('click', async () => {
        // Solo los documentos que efectivamente reciben cobertura (cubre > 0)
        const rows = Array.from(document.querySelectorAll('#modalDocsBody tr'))
            .filter(tr => Number(tr.dataset.cubre) > 0);

        if (!rows.length) {
            Swal.fire({
                icon: 'warning',
                title: 'Nada que pagar',
                text: 'El valor a pagar no cubre ninguno de los documentos seleccionados.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }

        const debtsWithCoverage = rows.map((tr) => ({
            doc: JSON.parse(tr.dataset.json),
            saldo: Number(tr.dataset.saldo) || 0,
            covered: Number(tr.dataset.cubre) || 0,
        }));

        const valor = debtsWithCoverage.reduce((sum, d) => sum + d.covered, 0);

        if(!paymentTipo.value || !paymentAction.value || !paymentDate.value || !paymentSource.value || !paymentFile || !paymentObservations.value) {
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

        const formData = new FormData();
        formData.append('proveedor', JSON.stringify(currentProvider));
        formData.append('tipo', paymentTipo.value);
        formData.append('accion', paymentAction.value);
        formData.append('origen', paymentSource.value);
        formData.append('fecha', paymentDate.value);
        formData.append('observaciones', paymentObservations.value);
        formData.append('valor', valor);
        formData.append('documentos', JSON.stringify(debtsWithCoverage));
        if (paymentFile) formData.append('comprobante', paymentFile);

        // Guardamos el contenido original del boton para poder restaurarlo despues
        const originalConfirmHTML = modalPaymentConfirm.innerHTML;

        modalPaymentConfirm.disabled = true;
        modalPaymentConfirm.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>Procesando...`;

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

            // Cierra el modal y refresca tabla/resumen sin recargar la página
            closePaymentModal();
            refreshAfterAction();

            await Swal.fire({
                icon: 'success',
                title: 'Listo',
                text: data.message || 'Recibo de pago procesado correctamente.',
                confirmButtonColor: '#3085d6'
            });

        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'No se pudo procesar el pago',
                text: error.message || 'Ocurrio un error inesperado.',
                confirmButtonColor: '#d33'
            });
        } finally {
            modalPaymentConfirm.disabled = false;
            modalPaymentConfirm.innerHTML = originalConfirmHTML;
        }
    });

    // ---- Modal: Realizar anticipo ----
    const advanceModalOverlay  = document.getElementById('advanceModalOverlay');
    const advanceModalClose    = document.getElementById('advanceModalClose');
    const advanceModalCancel   = document.getElementById('advanceModalCancel');
    const advanceModalConfirm  = document.getElementById('advanceModalConfirm');
    const advanceValue         = document.getElementById('advanceValue');
    const advanceModalTotalEl  = document.getElementById('advanceModalTotal');
    const advanceTipo          = document.getElementById('advanceTipo');
    const advanceAction        = document.getElementById('advanceAction');
    const advanceSource        = document.getElementById('advanceSource');
    const advanceDate          = document.getElementById('advanceDate');
    const advanceObservations  = document.getElementById('advanceObservations');
    const advanceDropzone      = document.getElementById('advanceDropzone');
    const advanceFileInput     = document.getElementById('advanceFileInput');
    const advanceFilePreview   = document.getElementById('advanceFilePreview');
    const advanceFileImg       = document.getElementById('advanceFileImg');
    const advanceFileName      = document.getElementById('advanceFileName');
    const advanceFileSize      = document.getElementById('advanceFileSize');
    const advanceFileRemove    = document.getElementById('advanceFileRemove');
    const advanceFileError     = document.getElementById('advanceFileError');
    const btnAdvance           = document.getElementById('btnAdvance');

    let advanceFile = null;

    advanceDropzone.addEventListener('click', () => advanceFileInput.click());

    ['dragover', 'dragenter'].forEach((evt) => {
        advanceDropzone.addEventListener(evt, (e) => {
            e.preventDefault();
            advanceDropzone.classList.add('dragover');
        });
    });

    ['dragleave', 'dragend'].forEach((evt) => {
        advanceDropzone.addEventListener(evt, () => advanceDropzone.classList.remove('dragover'));
    });

    advanceDropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        advanceDropzone.classList.remove('dragover');
        if (e.dataTransfer.files.length) handleAdvanceFile(e.dataTransfer.files[0]);
    });

    advanceFileInput.addEventListener('change', () => {
        if (advanceFileInput.files.length) handleAdvanceFile(advanceFileInput.files[0]);
    });

    advanceFileRemove.addEventListener('click', (e) => {
        e.stopPropagation();
        resetAdvanceFile();
    });

    function handleAdvanceFile(file) {
        const ext = file.name.split('.').pop().toLowerCase();
        advanceFileError.classList.remove('show');

        if (!ALLOWED_IMAGE_EXT.includes(ext)) {
            showAdvanceFileError('Solo se permiten imágenes JPG o PNG');
            resetAdvanceFile();
            return;
        }

        if (file.size / (1024 * 1024) > MAX_IMAGE_MB) {
            showAdvanceFileError(`La imagen supera el tamaño máximo de ${MAX_IMAGE_MB} MB`);
            resetAdvanceFile();
            return;
        }

        advanceFile = file;
        advanceFileName.textContent = file.name;
        advanceFileSize.textContent = formatFileSize(file.size);
        advanceFileImg.src = URL.createObjectURL(file);

        advanceFilePreview.classList.add('show');
        advanceDropzone.classList.add('hidden');
    }

    function resetAdvanceFile() {
        advanceFile = null;
        advanceFileInput.value = '';
        advanceFilePreview.classList.remove('show');
        advanceDropzone.classList.remove('hidden');
    }

    function showAdvanceFileError(msg) {
        advanceFileError.textContent = msg;
        advanceFileError.classList.add('show');
    }

    // El anticipo ya no depende de documentos seleccionados: solo se escribe el valor pagado.
    function openAdvanceModal() {
        advanceValue.value = '';
        advanceModalTotalEl.textContent = formatMoney(0);

        advanceTipo.value = '';
        advanceSource.value = '';
        advanceDate.value = new Intl.DateTimeFormat('en-CA', { timeZone: 'America/Bogota' }).format(new Date());
        advanceObservations.value = '';
        resetAdvanceFile();

        advanceModalOverlay.classList.add('show');
    }

    function closeAdvanceModal() {
        advanceModalOverlay.classList.remove('show');
    }

    function updateAdvanceTotal() {
        advanceModalTotalEl.textContent = formatMoney(Number(advanceValue.value) || 0);
    }

    advanceValue.addEventListener('input', updateAdvanceTotal);

    btnAdvance.addEventListener('click', openAdvanceModal);
    advanceModalClose.addEventListener('click', closeAdvanceModal);
    advanceModalCancel.addEventListener('click', closeAdvanceModal);

    advanceModalOverlay.addEventListener('click', (e) => {
        if (e.target === advanceModalOverlay) closeAdvanceModal();
    });

    advanceModalConfirm.addEventListener('click', async () => {
        const valor = Number(advanceValue.value) || 0;

        if (!advanceTipo.value || !advanceDate.value || !advanceAction.value || !advanceSource.value || !advanceFile || !advanceObservations.value || valor <= 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Campos incompletos',
                text: 'Completa todos los campos y define un valor a anticipar mayor a cero.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }

        const result = await Swal.fire({
            icon: 'warning',
            title: '¿Realizar anticipo?',
            text: 'Esta acción no se puede deshacer.',
            showCancelButton: true,
            confirmButtonText: 'Sí, realizar anticipo',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6'
        });
        if (!result.isConfirmed) return;

        const formData = new FormData();
        formData.append('proveedor', JSON.stringify(currentProvider));
        formData.append('tipo', advanceTipo.value);
        formData.append('accion', advanceAction.value);
        formData.append('origen', advanceSource.value);
        formData.append('fecha', advanceDate.value);
        formData.append('observaciones', advanceObservations.value);
        formData.append('valor', valor);
        if (advanceFile) formData.append('comprobante', advanceFile);

        const originalConfirmHTML = advanceModalConfirm.innerHTML;

        advanceModalConfirm.disabled = true;
        advanceModalConfirm.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>Procesando...`;

        Swal.fire({
            title: 'Procesando anticipo',
            text: 'Por favor espera, no cierres esta ventana...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => Swal.showLoading()
        });

        try {
            const response = await fetch('{{ route("siigo.accounts_advance") }}', {
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
                throw new Error(data.message || 'Ocurrio un error procesando el anticipo.');
            }

            // Cierra el modal y refresca tabla/resumen sin recargar la página
            closeAdvanceModal();
            refreshAfterAction();

            await Swal.fire({
                icon: 'success',
                title: 'Listo',
                text: data.message || 'Anticipo procesado correctamente.',
                confirmButtonColor: '#3085d6'
            });

        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'No se pudo procesar el anticipo',
                text: error.message || 'Ocurrio un error inesperado.',
                confirmButtonColor: '#d33'
            });
        } finally {
            advanceModalConfirm.disabled = false;
            advanceModalConfirm.innerHTML = originalConfirmHTML;
        }
    });

    const btnConciliation = document.getElementById('btnConciliation');

    // Modal cruce contable / conciliación
    const conciliationModalOverlay   = document.getElementById('conciliationModalOverlay');
    const conciliationModalClose     = document.getElementById('conciliationModalClose');
    const conciliationModalCancel    = document.getElementById('conciliationModalCancel');
    const conciliationModalConfirm   = document.getElementById('conciliationModalConfirm');
    const conciliationDocsBody       = document.getElementById('conciliationDocsBody');
    const conciliationTipo           = document.getElementById('conciliationTipo');
    const conciliationDate           = document.getElementById('conciliationDate');
    const conciliationObservations   = document.getElementById('conciliationObservations');
    const conciliationRpTotalEl      = document.getElementById('conciliationRpTotal');
    const conciliationDebtTotalEl    = document.getElementById('conciliationDebtTotal');
    const conciliationCoveredTotalEl = document.getElementById('conciliationCoveredTotal');

    let conciliationSelectedDebts = [];
    let conciliationSelectedRPs   = [];

    function getRPDocs() {
        return currentDocs.filter((doc) =>
            (doc.DuePrefix || '').toUpperCase().startsWith('RP') && !doc.IsAnnulled
        );
    }

    function openConciliationModal() {
        const selectedDebts = getSelectedDocs();

        if (!selectedDebts.length) {
            Swal.fire({
                icon: 'warning',
                title: 'Selecciona documentos',
                text: 'Debes seleccionar al menos un documento para realizar el cruce contable.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }

        const debtTotalNeeded = selectedDebts.reduce((sum, d) => sum + (Number(d.Saldo) || 0), 0);
        const allRpDocs = getRPDocs();

        // Solo tomamos los RP que realmente se necesitan para cubrir la deuda, en orden,
        // deteniéndonos apenas la suma acumulada alcanza (o supera) el total a cubrir.
        const usedRPs = [];
        let rpAccumulated = 0;

        for (const doc of allRpDocs) {
            if (rpAccumulated >= debtTotalNeeded) break;
            usedRPs.push(doc);
            rpAccumulated += Math.abs(Number(doc.Saldo) || 0);
        }

        let remaining = rpAccumulated;
        let debtTotal = 0;
        let coveredTotal = 0;
        let exceeded = false;

        // Reparte el saldo de los RP usados en cascada, en el orden en que se seleccionaron
        const debtsWithCoverage = selectedDebts.map((doc) => {
            const saldo = Number(doc.Saldo) || 0;
            const covered = Math.min(remaining, saldo);
            remaining -= covered;
            debtTotal += saldo;
            coveredTotal += covered;

            if (covered <= 0) exceeded = true; // este documento no queda totalmente cubierto

            return { doc, saldo, covered };
        });

        if (exceeded) {
            Swal.fire({
                icon: 'error',
                title: 'Saldo insuficiente',
                text: 'Los documentos seleccionados exceden el saldo a favor disponible en los recibos de pago. Quita alguno o selecciona menos documentos.',
                confirmButtonColor: '#d33'
            });
            return;
        }

        let rpRemainingToConsume = coveredTotal;

        const rpsWithUsage = usedRPs.map((doc) => {
            const saldo = Math.abs(Number(doc.Saldo) || 0);
            const used = Math.min(rpRemainingToConsume, saldo);
            rpRemainingToConsume -= used;

            return { doc, saldo, used };
        });

        conciliationSelectedDebts = debtsWithCoverage;
        conciliationSelectedRPs   = rpsWithUsage;

        let rowsHtml = '';

        rpsWithUsage.forEach(({ doc, saldo, used }) => {
            const coverClass = used >= saldo ? 'cover-full' : 'cover-partial';

            rowsHtml += `
                <tr data-json='${JSON.stringify(doc)}' data-tipo="rp" data-saldo="${saldo}" data-cubre="${used}">
                    <td><span class="prefix-tag">${escapeHtml(doc.DuePrefix)}</span></td>
                    <td>${escapeHtml(doc.DueName)}</td>
                    <td>${escapeHtml(doc.DocName) || '-'}</td>
                    <td>${formatDate(doc.DueDate)}</td>
                    <td style="text-align:right;">${formatMoney(saldo)}</td>
                    <td style="text-align:right;" class="${coverClass}">${formatMoney(used)}</td>
                </tr>
            `;
        });

        debtsWithCoverage.forEach(({ doc, saldo, covered }) => {
            const coverClass = covered >= saldo ? 'cover-full' : 'cover-partial';

            rowsHtml += `
                <tr data-json='${JSON.stringify(doc)}' data-tipo="debt" data-saldo="${saldo}" data-cubre="${covered}">
                    <td><span class="prefix-tag">${escapeHtml(doc.DuePrefix)}</span></td>
                    <td>${escapeHtml(doc.DueName)}</td>
                    <td>${escapeHtml(doc.DocName) || '-'}</td>
                    <td>${formatDate(doc.DueDate)}</td>
                    <td style="text-align:right;">${formatMoney(saldo)}</td>
                    <td style="text-align:right;" class="${coverClass}">${formatMoney(covered)}</td>
                </tr>
            `;
        });

        conciliationDocsBody.innerHTML = rowsHtml;

        conciliationRpTotalEl.textContent      = formatMoney(rpAccumulated);
        conciliationDebtTotalEl.textContent    = formatMoney(debtTotal);
        conciliationCoveredTotalEl.textContent = formatMoney(coveredTotal);

        conciliationTipo.value = '';
        conciliationDate.value = new Intl.DateTimeFormat('en-CA', { timeZone: 'America/Bogota' }).format(new Date());
        conciliationObservations.value = '';

        conciliationModalOverlay.classList.add('show');
    }

    function closeConciliationModal() {
        conciliationModalOverlay.classList.remove('show');
    }

    btnConciliation.addEventListener('click', openConciliationModal);
    conciliationModalClose.addEventListener('click', closeConciliationModal);
    conciliationModalCancel.addEventListener('click', closeConciliationModal);

    conciliationModalOverlay.addEventListener('click', (e) => {
        if (e.target === conciliationModalOverlay) closeConciliationModal();
    });

    conciliationModalConfirm.addEventListener('click', async () => {

        if (!conciliationTipo.value || !conciliationDate.value || !conciliationObservations.value) {
            Swal.fire({
                icon: 'warning',
                title: 'Campos incompletos',
                text: 'Completa el tipo de documento, la fecha de elaboración y las observaciones.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }

        const result = await Swal.fire({
            icon: 'warning',
            title: '¿Realizar cruce contable?',
            text: 'Esta acción no se puede deshacer.',
            showCancelButton: true,
            confirmButtonText: 'Sí, realizar cruce',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6'
        });
        if (!result.isConfirmed) return;

        const originalConfirmHTML = conciliationModalConfirm.innerHTML;
        conciliationModalConfirm.disabled = true;
        conciliationModalConfirm.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>Procesando...`;

        Swal.fire({
            title: 'Procesando cruce contable',
            text: 'Por favor espera, no cierres esta ventana...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => Swal.showLoading()
        });

        try {
            const formData = new FormData();
            formData.append('proveedor', JSON.stringify(currentProvider));
            formData.append('tipo', conciliationTipo.value);
            formData.append('fecha', conciliationDate.value);
            formData.append('observaciones', conciliationObservations.value);
            formData.append('documentos', JSON.stringify(conciliationSelectedDebts));
            formData.append('recibos', JSON.stringify(conciliationSelectedRPs));

            const response = await fetch('{{ route("siigo.accounts_conciliation") }}', {
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
                throw new Error(data.message || 'Ocurrio un error procesando el cruce contable.');
            }

            // Cierra el modal y refresca tabla/resumen sin recargar la página
            closeConciliationModal();
            refreshAfterAction();

            await Swal.fire({
                icon: 'success',
                title: 'Listo',
                text: data.message || 'Cruce contable procesado correctamente.',
                confirmButtonColor: '#3085d6'
            });

        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'No se pudo procesar el cruce contable',
                text: error.message || 'Ocurrio un error inesperado.',
                confirmButtonColor: '#d33'
            });
        } finally {
            conciliationModalConfirm.disabled = false;
            conciliationModalConfirm.innerHTML = originalConfirmHTML;
        }
    });

    // ---- Inicio: se consulta el listado de proveedores al endpoint ----
    resetView();
    loadProviders();
})();
</script>

</body>
</html>
