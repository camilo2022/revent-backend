<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Recepcion de orden de compra</title>
    <style>

        * {
            box-sizing: border-box;
        }

        body {
            background: #f3f4f6;
            font-family: 'Segoe UI', system-ui, sans-serif;
            margin: 0;
            padding: 2rem 1rem;
        }

        .excel-upload-wrapper {
            max-width: 960px;
            margin: 2rem auto;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }

        .excel-upload-card,
        .result-card,
        .checklist-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #eef0f2;
        }

        /* =========================================================
           BUSCADOR
        ========================================================== */

        .excel-upload-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.4rem;
        }

        .excel-upload-subtitle {
            font-size: 0.88rem;
            color: #6b7280;
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }

        .excel-search-row {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            width: 100%;
        }

        .excel-field-group {
            width: 100%;
            margin: 0;
        }

        .excel-field-label {
            display: block;
            font-size: 0.82rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.45rem;
        }

        .excel-field-input {
            flex: 1;
            width: 100%;
            height: 44px;
            padding: 0.65rem 0.85rem;
            font-size: 0.9rem;
            color: #1f2937;
            background: #f9fafb;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            transition: border-color 0.2s ease, background 0.2s ease;
        }

        .excel-field-input::placeholder {
            color: #9ca3af;
        }

        .excel-field-input:focus {
            outline: none;
            border-color: #16a34a;
            background: #ffffff;
        }

        .excel-field-hint {
            font-size: 0.75rem;
            color: #9ca3af;
            margin-top: 0.35rem;
        }

        .excel-submit-btn {
            position: relative;
            flex: 0 0 auto;
            width: 110px;
            height: 44px;
            padding: 0 0.75rem;
            margin: 0;
            background: #16a34a;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            transition: background 0.2s ease;
        }

        .excel-submit-btn:hover {
            background: #15803d;
        }

        .excel-submit-btn:active,
        .excel-submit-btn:disabled {
            transform: none;
        }

        .excel-submit-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .btn-spinner {
            display: none;
            width: 13px;
            height: 13px;
            flex: 0 0 13px;
            border: 2px solid rgba(255,255,255,0.35);
            border-top-color: #fff;
            border-right-color: #fff;
            border-radius: 50%;
            animation: btn-spin 0.65s linear infinite;
        }

        .excel-submit-btn.is-loading .btn-spinner {
            display: block;
        }

        @keyframes btn-spin {
            to {
                transform: rotate(360deg);
            }
        }

        .excel-status {
            margin-bottom: 1rem;
            font-size: 0.8rem;
            color: #166534;
            background: #f0fdf4;
            border: 1px solid #86efac;
            padding: 0.6rem 0.85rem;
            border-radius: 8px;
        }

        /* =========================================================
           RESULTADO
        ========================================================== */

        .result-card {
            display: none;
            margin-top: 1.2rem;
        }

        .result-card.show {
            display: block;
        }

        .result-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1.3rem;
        }

        .result-doc-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1f2937;
        }

        .result-doc-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            margin-top: 0.45rem;
            font-size: 0.78rem;
            color: #6b7280;
        }

        .result-doc-info strong {
            color: #374151;
        }

        .badge {
            display: inline-flex;
            padding: 0.25rem 0.65rem;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .badge-ok {
            background: #f0fdf4;
            color: #166534;
        }

        .badge-invalid {
            background: #fee2e2;
            color: #991b1b;
        }

        /* =========================================================
           RESUMEN
        ========================================================== */

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 1.4rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .summary-card {
            padding: 0.9rem 1.1rem;
            color: #ffffff;
        }

        .summary-card .amount {
            font-size: 1.15rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .summary-card .label {
            font-size: 0.72rem;
            opacity: 0.92;
            margin-top: 0.15rem;
        }

        .summary-requested {
            background: #3F51B5;
        }

        .summary-received {
            background: #5C6BC0;
        }

        .summary-pending {
            background: #7986CB;
        }

        /* =========================================================
           ITEMS
        ========================================================== */

        .section-title {
            font-size: 0.85rem;
            font-weight: 700;
            color: #374151;
            margin: 1.4rem 0 0.6rem;
        }

        .items-scroll {
            overflow-x: auto;
            border: 1px solid #f1f3f5;
            border-radius: 10px;
            background: #ffffff;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8rem;
            min-width: 900px;
        }

        .items-table thead th {
            text-align: center;
            font-size: 0.69rem;
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            padding: 0.7rem 0.75rem;
            background: #f9fafb;
            border-bottom: 1px solid #f1f3f5;
        }

        .items-table td {
            text-align: center;
            padding: 0.65rem 0.75rem;
            border-bottom: 1px solid #f1f3f5;
            color: #374151;
            vertical-align: middle;
        }

        .items-table tbody tr:last-child td {
            border-bottom: none;
        }

        .description-cell {
            text-align: left !important;
            min-width: 280px;
            font-weight: 600;
            color: #1f2937 !important;
        }

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

        .receiving-input {
            width: 78px;
            height: 34px;
            padding: 0.35rem 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #f9fafb;
            color: #1f2937;
            font-size: 0.8rem;
            font-weight: 600;
            text-align: center;
            outline: none;
        }

        .receiving-input:focus {
            border-color: #16a34a;
            background: #ffffff;
        }

        .receiving-input.over {
            border-color: #2563eb;
            background: #eff6ff;
        }

        .pending-value {
            display: inline-flex;
            min-width: 30px;
            justify-content: center;
        }

        .cover-full {
            color: #16a34a !important;
            font-weight: 700;
        }

        .cover-partial {
            color: #c2410c !important;
            font-weight: 700;
        }

        .cover-over {
            color: #2563eb !important;
            font-weight: 700;
        }

        .cover-none {
            color: #9ca3af !important;
            font-weight: 600;
        }

        .empty-state {
            text-align: center;
            color: #9ca3af;
            font-size: 0.8rem;
            padding: 1.4rem 0;
        }

        .btn-reset {
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 0.55rem 1rem;
            font-size: 0.8rem;
            font-weight: 600;
            color: #374151;
            cursor: pointer;
            margin-top: 1.3rem;
        }

        .btn-reset:hover {
            background: #e5e7eb;
        }

        /* =========================================================
           CHECKLIST
        ========================================================== */

        .checklist-card {
            display: none;
            margin-top: 1.2rem;
        }

        .checklist-card.show {
            display: block;
        }

        .checklist-header {
            text-align: center;
            margin-bottom: 1.3rem;
        }

        .checklist-company {
            font-size: 1.05rem;
            font-weight: 800;
            color: #111827;
            margin-bottom: 0.3rem;
        }

        .checklist-department {
            font-size: 0.78rem;
            font-weight: 700;
            color: #374151;
            text-transform: uppercase;
            margin-bottom: 0.15rem;
        }

        .checklist-title {
            font-size: 0.85rem;
            font-weight: 800;
            color: #111827;
            text-transform: uppercase;
            margin-top: 0.7rem;
        }

        .checklist-order-info {
            margin-top: 0.8rem;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .checklist-order-badge {
            padding: 0.35rem 0.65rem;
            background: #f3f4f6;
            border-radius: 7px;
            font-size: 0.72rem;
            color: #374151;
        }

        .checklist-section {
            margin-top: 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
        }

        .checklist-section-title {
            background: #fff3cd;
            color: #111827;
            padding: 0.55rem 0.7rem;
            font-size: 0.78rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .checklist-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 55px;
            align-items: center;
            min-height: 48px;
            border-top: 1px solid #e5e7eb;
            background: #ffffff;
        }

        .checklist-description {
            padding: 0.6rem 0.7rem;
            font-size: 0.78rem;
            color: #374151;
            line-height: 1.4;
        }

        .checklist-description strong {
            color: #111827;
        }

        .checklist-check {
            height: 100%;
            min-height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-left: 1px solid #e5e7eb;
        }

        .checklist-check input {
            width: 19px;
            height: 19px;
            cursor: pointer;
            accent-color: #16a34a;
        }

        .checklist-textarea {
            display: block;
            width: 100%;
            min-height: 85px;
            resize: vertical;
            padding: 0.7rem;
            border: none;
            outline: none;
            font-family: inherit;
            font-size: 0.8rem;
            color: #374151;
        }

        .checklist-textarea:focus {
            background: #fafffb;
        }

        .checklist-footer {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1rem;
        }

        .checklist-footer-field {
            min-height: 45px;
            border-bottom: 1px solid #9ca3af;
            padding-top: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: #374151;
        }

        .checklist-submit-wrapper {
            display: flex;
            justify-content: flex-end;
            margin-top: 1.2rem;
        }

        .checklist-submit-btn {
            background: #16a34a;
            color: #ffffff;
            border: none;
            border-radius: 9px;
            padding: 0.7rem 1.3rem;
            font-size: 0.82rem;
            font-weight: 700;
            cursor: pointer;
        }

        .checklist-submit-btn:hover {
            background: #15803d;
        }

        .checklist-submit-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        /* =========================================================
           UPLOAD IMAGENES
        ========================================================== */

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

        .payment-file-input {
            display: none;
        }

        .receiving-files-preview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 0.7rem;
            margin-top: 0.8rem;
        }

        .receiving-file-preview {
            position: relative;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 10px;
            padding: 0.55rem;
        }

        .receiving-file-preview img {
            display: block;
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 7px;
            border: 1px solid #d1fae5;
        }

        .receiving-file-info {
            padding: 0.45rem 0.2rem 0.1rem;
        }

        .receiving-file-name {
            font-size: 0.72rem;
            font-weight: 600;
            color: #1f2937;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .receiving-file-size {
            font-size: 0.68rem;
            color: #6b7280;
            margin-top: 2px;
        }

        .receiving-file-remove {
            position: absolute;
            top: 7px;
            right: 7px;
            width: 26px;
            height: 26px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.95);
            color: #dc2626;
            font-size: 16px;
            line-height: 1;
            cursor: pointer;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.15);
        }

        .receiving-file-remove:hover {
            background: #fee2e2;
        }

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

        .payment-file-error.show {
            display: block;
        }

        /* =========================================================
           BACK
        ========================================================== */

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

        .back-link:hover {
            text-decoration: underline;
        }

        .back-link svg {
            width: 15px;
            height: 15px;
        }

        /* =========================================================
           RESPONSIVE
        ========================================================== */

        @media (max-width: 700px) {

            body {
                padding: 1rem 0.5rem;
            }

            .excel-upload-wrapper {
                margin: 0.5rem auto;
            }

            .excel-upload-card,
            .result-card,
            .checklist-card {
                padding: 1.2rem;
            }

            .excel-search-row {
                display: grid;
                grid-template-columns: 1fr;
            }

            .excel-submit-btn {
                width: 100%;
            }

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .checklist-footer {
                grid-template-columns: 1fr;
            }

            .receiving-files-preview {
                grid-template-columns: repeat(2, 1fr);
            }

        }

    </style>
</head>

<body>
<div class="excel-upload-wrapper">

    <!-- =========================================================
         BUSCADOR
    ========================================================== -->

    <div class="excel-upload-card">
        <div class="excel-upload-title">
            Recepcion de orden de compra
        </div>

        <div class="excel-upload-subtitle">
            Escribe el numero de la orden de compra (por ejemplo <strong>OC-1-12345</strong>)
            para consultar su informacion y lo recibido hasta el momento.
        </div>

        @if (session('status'))
            <div class="excel-status">
                {{ session('status') }}
            </div>
        @endif

        <form id="searchForm" autocomplete="off">
            <div class="excel-field-group">
                <label for="query" class="excel-field-label">
                    Token *
                </label>

                <div class="excel-search-row">
                    <input type="text" name="token" id="token" class="excel-field-input" required autofocus>
                </div>
            </div>
            <div class="excel-field-group">
                <label for="query" class="excel-field-label">
                    N° de orden de compra *
                </label>

                <div class="excel-search-row">
                    <input type="text" name="query" id="query" class="excel-field-input" placeholder="Ej: OC-1-12345" required autofocus>
                    <button type="submit" class="excel-submit-btn" id="submitBtn"><span class="btn-spinner"></span><span class="btn-label">Buscar</span></button>
                </div>

                <div class="excel-field-hint">
                    Escribe el numero exacto tal como aparece en Siigo.
                </div>

            </div>
        </form>

    </div>

    <!-- =========================================================
         RESULTADO
    ========================================================== -->

    <div
        class="result-card"
        id="resultCard">

        <div class="result-header">
            <div>
                <div class="result-doc-name" id="resDocName">
                    -
                </div>
                <div class="result-doc-info">
                    <span>
                        Fecha OC:
                        <strong id="resDocDate">-</strong>
                    </span>
                    <span>
                        Bodega:
                        <strong id="resWarehouse">-</strong>
                    </span>
                </div>

                <div class="result-doc-info" id="resDocObservations" style="display:none;">
                    <span>
                        Observaciones:
                        <strong id="resObservationsText">-</strong>
                    </span>
                </div>
            </div>

            <span class="badge" id="resBadge">
                -
            </span>

        </div>

        <!-- RESUMEN -->

        <div class="summary-grid">
            <div class="summary-card summary-requested">
                <div class="amount" id="resRequested">
                    0
                </div>
                <div class="label">
                    Cantidad solicitada
                </div>
            </div>

            <div class="summary-card summary-received">
                <div class="amount" id="resReceived">
                    0
                </div>
                <div class="label">
                    Cantidad ingresando
                </div>
            </div>

            <div class="summary-card summary-pending">
                <div class="amount" id="resPending">
                    0
                </div>
                <div class="label">
                    Cantidad pendiente
                </div>
            </div>

        </div>

        <!-- ITEMS -->

        <div class="section-title">
            Items de la orden
        </div>

        <div class="items-scroll">
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Descripcion</th>
                        <th>Referencia</th>
                        <th>Color</th>
                        <th>Categoria</th>
                        <th>Talla</th>
                        <th>Solicitada</th>
                        <th>Ingresando</th>
                        <th>Pendiente</th>
                    </tr>
                </thead>

                <tbody id="resItemsBody"></tbody>

            </table>

        </div>

        <button type="button" class="btn-reset" id="btnReset">

            Buscar otra orden

        </button>

    </div>

    <!-- =========================================================
         CHECKLIST
    ========================================================== -->

    <div
        class="checklist-card"
        id="checklistCard">

        <div class="checklist-header">
            <div class="checklist-company">
                REVENT CALZADO S.A.S.
            </div>
            <div class="checklist-department">
                GERENCIA ADMINISTRATIVA
            </div>
            <div class="checklist-department">
                DEPARTAMENTO DE OPERACIONES Y LOGISTICA
            </div>
            <div class="checklist-title">
                CHECKLIST RAPIDO -
                RECEPCION DE MERCANCIA TIENDAS
            </div>

            <div class="checklist-order-info">
                <span class="checklist-order-badge">
                    OC:
                    <strong id="checklistOrder">
                        -
                    </strong>
                </span>

                <span class="checklist-order-badge">
                    Fecha:
                    <strong id="checklistDate">
                        -
                    </strong>
                </span>

            </div>

        </div>

        <form id="receivingChecklistForm">

            <div class="checklist-section">
                <div class="checklist-section-title">
                    1. Frente al transportador
                </div>
                <div class="checklist-row">
                    <div class="checklist-description">
                        <strong>Cajas Master:</strong>
                        Contar bultos vs. Guia de Envio.
                    </div>
                    <div class="checklist-check">
                        <input type="checkbox" name="check_cajas_master" value="1">
                    </div>

                </div>

                <div class="checklist-row">
                    <div class="checklist-description">
                        <strong>Sellos:</strong>
                        Verificar que la cinta de seguridad
                        NO este rota o despegada.
                    </div>

                    <div class="checklist-check">
                        <input type="checkbox" name="check_sellos" value="1">
                    </div>
                </div>

                <div class="checklist-row">
                    <div class="checklist-description">
                        <strong>Estado:</strong>
                        Cajas sin huecos, humedad o signos
                        de maltrato.
                    </div>

                    <div class="checklist-check">
                        <input type="checkbox" name="check_estado_cajas" value="1">
                    </div>

                </div>

                <div class="checklist-row">
                    <div class="checklist-description">
                        <strong>Documentos:</strong>
                        Trae Factura y Orden de Compra (OC)
                        original?
                    </div>

                    <div class="checklist-check">
                        <input type="checkbox" name="check_documentos" value="1">
                    </div>

                </div>

            </div>

            <!-- =================================================
                 2. CALIDAD
            ================================================== -->

            <div class="checklist-section">
                <div class="checklist-section-title">
                    2. Calidad y detalle (Al abrir)
                </div>
                <div class="checklist-row">
                    <div class="checklist-description">
                        <strong>Empaque:</strong>
                        Cajas blancas individuales,
                        nuevas y limpias.
                    </div>

                    <div class="checklist-check">
                        <input type="checkbox" name="check_empaque" value="1">
                    </div>

                </div>

                <div class="checklist-row">
                    <div class="checklist-description">
                        <strong>Stickers:</strong>
                        Cada par con precio y etiqueta DIAN.
                    </div>

                    <div class="checklist-check">
                        <input type="checkbox" name="check_stickers" value="1">
                    </div>

                </div>

                <div class="checklist-row">

                    <div class="checklist-description">

                        <strong>Producto:</strong>
                        Revisar costuras, pegado y que
                        no tenga rayones.

                    </div>

                    <div class="checklist-check">
                        <input type="checkbox" name="check_producto" value="1">
                    </div>

                </div>

                <div class="checklist-row">
                    <div class="checklist-description">
                        <strong>Exactitud:</strong>
                        Color, talla y cantidad coinciden
                        con la OC?
                    </div>

                    <div class="checklist-check">
                        <input type="checkbox" name="check_exactitud" value="1">
                    </div>

                </div>

                <div class="checklist-row">
                    <div class="checklist-description">
                        <strong>Variacion y Diseno:</strong>
                        El producto coincide con el modelo
                        original (imagen web o con el de
                        existencias en tienda, si aplica)?
                    </div>

                    <div class="checklist-check">
                        <input type="checkbox" name="check_variacion_diseno" value="1">
                    </div>

                </div>

            </div>

            <!-- =================================================
                 3. CIERRE ADMINISTRATIVO
            ================================================== -->

            <div class="checklist-section">

                <div class="checklist-section-title">
                    3. Cierre administrativo
                </div>

                <div class="checklist-row">
                    <div class="checklist-description">
                        <strong>Firma:</strong>
                        Firmar y fechar la OC si todo
                        esta correcto.
                    </div>

                    <div class="checklist-check">
                        <input type="checkbox" name="check_firma" value="1">
                    </div>

                </div>

                <div class="checklist-row">
                    <div class="checklist-description">
                        <strong>Reporte WhatsApp:</strong>
                        Enviar foto de la OC antes
                        de las 5:30 PM.
                    </div>

                    <div class="checklist-check">
                        <input type="checkbox" name="check_reporte_whatsapp" value="1">
                    </div>

                </div>

                <div class="checklist-row">

                    <div class="checklist-description">

                        <strong>Reporte WhatsApp:</strong>
                        Respaldar los productos que reciban,
                        al grupo: "REVENT Grupo Comercial"

                    </div>

                    <div class="checklist-check">

                        <input
                            type="checkbox"
                            name="check_respaldo_whatsapp"
                            value="1">

                    </div>

                </div>

                <div class="checklist-row">

                    <div class="checklist-description">

                        <strong>Alerta:</strong>
                        Si hay fallas, informar a Operaciones
                        ANTES de recibir.

                    </div>

                    <div class="checklist-check">

                        <input
                            type="checkbox"
                            name="check_alerta"
                            value="1">

                    </div>

                </div>

                <div class="checklist-row">

                    <div class="checklist-description">

                        <strong>Venta:</strong>
                        NO vender hasta que confirmen
                        carga en SIIGO.

                    </div>

                    <div class="checklist-check">

                        <input
                            type="checkbox"
                            name="check_venta"
                            value="1">

                    </div>

                </div>

            </div>

            <div class="checklist-section">
                <div class="checklist-section-title">Datos de quien realiza la recepción</div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.8rem;padding:0.8rem;">
                    <div>
                        <label class="excel-field-label" for="firmaNombre">Nombre completo *</label>
                        <input type="text" class="excel-field-input" name="firma_nombre" id="firmaNombre" placeholder="Nombre completo" required>
                    </div>
                    <div>
                        <label class="excel-field-label" for="firmaCargo">Cargo *</label>
                        <input type="text" class="excel-field-input" name="firma_cargo" id="firmaCargo" placeholder="Cargo" required>
                    </div>
                    <div>
                        <label class="excel-field-label" for="firmaDepartamento">Departamento *</label>
                        <input type="text" class="excel-field-input" name="firma_departamento" id="firmaDepartamento" placeholder="Departamento" required>
                    </div>
                    <div>
                        <label class="excel-field-label" for="firmaCelular">Celular</label>
                        <input type="text" class="excel-field-input" name="firma_celular" id="firmaCelular" placeholder="Celular">
                    </div>
                </div>
            </div>
            <!-- =================================================
                 REFERENCIAS
            ================================================== -->

            <div class="checklist-section">

                <div class="checklist-section-title">
                    Referencias recibidas
                </div>

                <textarea
                    class="checklist-textarea"
                    name="referencias_recibidas"
                    id="referenciasRecibidas"
                    placeholder="Escribe las referencias recibidas..."></textarea>

            </div>

            <!-- =================================================
                 OBSERVACIONES
            ================================================== -->

            <div class="checklist-section">

                <div class="checklist-section-title">Observaciones *</div>

                <textarea class="checklist-textarea" name="observaciones" id="checklistObservaciones" placeholder="Escribe las observaciones de la recepcion..." required></textarea>

            </div>

            <!-- =================================================
                 EVIDENCIA FOTOGRAFICA
            ================================================== -->

            <div class="checklist-section">

                <div class="checklist-section-title">Evidencia fotografica *</div>

                <div style="padding:0.8rem;">

                    <div
                        class="payment-dropzone"
                        id="receivingDropzone">

                        <div class="payment-dropzone-icon">

                            <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">

                                <rect x="3" y="3" width="18" height="18" rx="2"/>

                                <circle cx="8.5" cy="8.5" r="1.5"/>

                                <polyline points="21 15 16 10 5 21"/>

                            </svg>

                        </div>

                        <div class="payment-dropzone-text">

                            Arrastra imágenes aquí o
                            <span>selecciónalas</span>

                        </div>

                        <div class="payment-dropzone-hint">JPG o PNG · Máximo 8 MB por imagen · Al menos una imagen es obligatoria · Puedes seleccionar varias</div>

                        <input type="file" id="receivingFileInput" class="payment-file-input" accept=".jpg,.jpeg,.png" multiple>

                    </div>

                    <div
                        id="receivingFileError"
                        class="payment-file-error">
                    </div>

                    <div
                        id="receivingFilesPreview"
                        class="receiving-files-preview">
                    </div>

                </div>

            </div>

            <!-- =================================================
                 BOTON
            ================================================== -->

            <div class="checklist-submit-wrapper">

                <button
                    type="submit"
                    class="checklist-submit-btn"
                    id="saveChecklistBtn">

                    Guardar recepción

                </button>

            </div>

        </form>

    </div>

    <!-- =========================================================
         VOLVER
    ========================================================== -->

    <a
        href="{{ route('home') }}"
        class="back-link">

        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="19" y1="12" x2="5" y2="12"/>
            <polyline points="12 19 5 12 12 5"/>

        </svg>

        Volver a acciones disponibles

    </a>

</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>

(function() {

    /* =========================================================
       CONFIGURACION
    ========================================================== */

    const SEARCH_URL = "{{ route('siigo.invoice_purchase_order_search') }}";

    const form = document.getElementById('searchForm');
    const tokenInput = document.getElementById('token');
    const queryInput = document.getElementById('query');
    const submitBtn = document.getElementById('submitBtn');
    const btnLabel = submitBtn.querySelector('.btn-label');
    const resultCard = document.getElementById('resultCard');
    const checklistCard = document.getElementById('checklistCard');
    const btnReset = document.getElementById('btnReset');
    const checklistForm = document.getElementById('receivingChecklistForm');

    /* =========================================================
       IMAGENES
    ========================================================== */

    const receivingDropzone = document.getElementById('receivingDropzone');
    const receivingFileInput = document.getElementById('receivingFileInput');
    const receivingFilesPreview = document.getElementById('receivingFilesPreview');
    const receivingFileError = document.getElementById('receivingFileError');
    const receivingImages = [];
    const ALLOWED_RECEIVING_IMAGE_EXT = ['jpg', 'jpeg', 'png'];
    const MAX_RECEIVING_IMAGE_MB = 8;

    /* =========================================================
       VARIABLES GLOBALES
    ========================================================== */

    window.currentPurchaseOrder = null;
    window.receivingItems = [];
    window.receivingData = null;
    window.receivingJson = null;

    /* =========================================================
       ESCAPE HTML
    ========================================================== */

    function esc(value) {

        if (value === null || value === undefined) {
            return '';
        }

        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    /* =========================================================
       FORMATO CANTIDADES
    ========================================================== */

    function formatThousands(value) {
        const number = Number(value) || 0;
        const negative = number < 0;
        const absolute =  Math.abs(Math.round(number));
        const formatted =
            String(absolute)
                .replace(
                    /\B(?=(\d{3})+(?!\d))/g,
                    '.'
                );

        return negative
            ? '-' + formatted
            : formatted;

    }

    function qtyFmt(value) {
        return formatThousands(value);
    }

    /* =========================================================
       FORMATO FECHA
    ========================================================== */

    function dateFmt(value) {
        if (!value) {
            return '-';
        }
        const stringValue = String(value);
        const siigoMatch = stringValue.match(/^(\d{4})(\d{2})(\d{2})$/);

        if (siigoMatch) {
            return (siigoMatch[3] + '/' + siigoMatch[2] + '/' + siigoMatch[1]);
        }

        const isoMatch = stringValue.match(/^(\d{4})-(\d{2})-(\d{2})/);

        if (isoMatch) {
            return (isoMatch[3] + '/' + isoMatch[2] + '/' + isoMatch[1]);
        }

        const date = new Date(value);

        if (Number.isNaN(date.getTime())) {
            return '-';
        }

        const day = String(date.getDate()).padStart(2, '0');

        const month = String(date.getMonth() + 1).padStart(2, '0');

        return (day + '/' + month + '/' + date.getFullYear());

    }

    /* =========================================================
       CLASE PENDIENTE
    ========================================================== */

    function pendingClass(pending) {
        pending = Number(pending) || 0;
        if (pending > 0) return 'cover-over';
        if (pending < 0) return 'cover-partial';
        return 'cover-full';
    }

    /* =========================================================
       OBTENER DATOS ORDEN
    ========================================================== */

    function getOrderEntry(order) {

        return (order && order.Entry)
            ? order.Entry
            : {};

    }

    function getOrderDocName(order) {
        const entry = getOrderEntry(order);
        return (order.DocName || entry.DocName || '-');
    }

    function getOrderDocDate(order) {
        const entry = getOrderEntry(order);
        return (order.DocDate || entry.DocDate ||  null);
    }

    function getOrderObservations(order) {
        const entry =
            getOrderEntry(order);

        return (
            order.Observations ||
            entry.Observations ||
            ''
        );
    }

    function getOrderWarehouse(order, items) {

        if (order.Warehouse) {

            return order.Warehouse;

        }

        const firstItem =
            items && items.length
                ? items[0]
                : null;

        if (
            firstItem &&
            firstItem.Warehouse
        ) {

            return firstItem.Warehouse;

        }

        return '-';

    }

    /* =========================================================
       RENDER ITEMS
    ========================================================== */

    function renderItems(items) {

        const body =
            document.getElementById(
                'resItemsBody'
            );

        if (
            !Array.isArray(items) ||
            !items.length
        ) {

            body.innerHTML = `
                <tr>
                    <td colspan="8" class="empty-state">
                        Esta orden no tiene items.
                    </td>
                </tr>
            `;

            window.receivingItems = [];

            updateReceivingSummary();

            return;

        }

        window.receivingItems =
            items.map(function(item) {

                return {

                    ...item,

                    ReceivingQuantity: 0

                };

            });

        body.innerHTML =
            window.receivingItems

                .map(function(item, index) {

                    const requested =
                        Number(item.Quantity) || 0;

                    const description =
                        item.LongDescription ||
                        item.Description ||
                        '-';

                    return `

                        <tr
                            data-item-index="${index}"
                            data-product-code="${esc(
                                item.ProductCode || ''
                            )}"
                        >

                            <td class="description-cell">
                                ${esc(description)}
                            </td>

                            <td>

                                <span class="prefix-tag">
                                    ${esc(
                                        item.Reference || '-'
                                    )}
                                </span>

                            </td>

                            <td>
                                ${esc(
                                    item.Color || '-'
                                )}
                            </td>

                            <td>
                                ${esc(
                                    item.Category || '-'
                                )}
                            </td>

                            <td>
                                ${esc(
                                    item.Size || '-'
                                )}
                            </td>

                            <td>
                                <strong>
                                    ${qtyFmt(requested)}
                                </strong>
                            </td>

                            <td>

                                <input
                                    type="number"
                                    class="receiving-input"
                                    min="0"
                                    step="1"
                                    value="0"
                                    data-item-index="${index}"
                                >

                            </td>

                            <td>

                                <span
                                    class="pending-value cover-partial"
                                    data-item-index="${index}"
                                >
                                    ${qtyFmt(-requested)}
                                </span>

                            </td>

                        </tr>

                    `;

                })

                .join('');

        body
            .querySelectorAll(
                '.receiving-input'
            )
            .forEach(function(input) {

                input.addEventListener(
                    'input',
                    function() {

                        updateItemReceiving(
                            this
                        );

                    }
                );

            });

        updateReceivingSummary();

    }

    /* =========================================================
       ACTUALIZAR ITEM
    ========================================================== */

    function updateItemReceiving(input) {

        const index =
            Number(input.dataset.itemIndex);

        const item =
            window.receivingItems[index];

        if (!item) {

            return;

        }

        const requested =
            Number(item.Quantity) || 0;

        let receiving =
            Number(input.value);

        if (
            !Number.isFinite(receiving) ||
            receiving < 0
        ) {

            receiving = 0;

            input.value = 0;

        }

        item.ReceivingQuantity =
            receiving;

        const pending = receiving - requested;

        const pendingElement =
            document.querySelector(
                `.pending-value[data-item-index="${index}"]`
            );

        input.classList.toggle(
            'over',
            receiving > requested
        );

        if (pendingElement) {

            pendingElement.textContent = pending > 0 ? '+' + qtyFmt(pending) : qtyFmt(pending);

            pendingElement.className =
                'pending-value ' +
                pendingClass(pending);

        }

        updateReceivingSummary();

        updateReceivingJson();

    }

    /* =========================================================
       TOTALES
    ========================================================== */

    function calculateTotals() {

        const items =
            window.receivingItems || [];

        let requested = 0;

        let receiving = 0;

        let pending = 0;

        items.forEach(function(item) {

            const itemRequested =
                Number(item.Quantity) || 0;

            const itemReceiving =
                Number(
                    item.ReceivingQuantity
                ) || 0;

            requested += itemRequested;

            receiving += itemReceiving;

            pending += itemReceiving - itemRequested;

        });

        return {

            requested,

            receiving,

            pending

        };

    }

    function updateReceivingSummary() {

        const totals =
            calculateTotals();

        document.getElementById(
            'resRequested'
        ).textContent =
            qtyFmt(totals.requested);

        document.getElementById(
            'resReceived'
        ).textContent =
            qtyFmt(totals.receiving);

        document.getElementById(
            'resPending'
        ).textContent =
            qtyFmt(totals.pending);

    }

    /* =========================================================
       JSON RECEPCION
    ========================================================== */

    function buildReceivingJson() {

        const order =
            window.currentPurchaseOrder;

        if (!order) {

            return null;

        }

        const entry =
            getOrderEntry(order);

        const items =
            (window.receivingItems || [])
                .map(function(item) {

                    const requested =
                        Number(item.Quantity) || 0;

                    const receiving =
                        Number(
                            item.ReceivingQuantity
                        ) || 0;

                    const pending = receiving - requested;

                    return {

                        ProductCode:
                            item.ProductCode ||
                            null,

                        Description:
                            item.LongDescription ||
                            item.Description ||
                            null,

                        LongDescription:
                            item.LongDescription ||
                            null,

                        Reference:
                            item.Reference ||
                            null,

                        Color:
                            item.Color ||
                            null,

                        Category:
                            item.Category ||
                            null,

                        Size:
                            item.Size ||
                            null,

                        WarehouseCode:
                            item.WarehouseCode ||
                            null,

                        Warehouse:
                            item.Warehouse ||
                            null,

                        Quantity:
                            requested,

                        ReceivingQuantity:
                            receiving,

                        PendingQuantity:
                            pending

                    };

                });

        const totals =
            calculateTotals();

        return {

            Order: {

                ACEntryID:
                    order.ACEntryID ||
                    entry.ACEntryID ||
                    null,

                DocName:
                    getOrderDocName(order),

                DocDate:
                    getOrderDocDate(order),

                DeadlineDate:
                    order.DeadlineDate ||
                    null,

                Identification:
                    order.Identification ||
                    null,

                FullName:
                    order.FullName ||
                    null,

                CompanyName:
                    order.CompanyName ||
                    null,

                Warehouse:
                    getOrderWarehouse(
                        order,
                        window.receivingItems
                    ),

                Observations:
                    getOrderObservations(order)

            },

            Items: items,

            Totals: {

                Requested:
                    totals.requested,

                Receiving:
                    totals.receiving,

                Pending:
                    totals.pending

            }

        };

    }

    function updateReceivingJson() {

        window.receivingJson =
            buildReceivingJson();

    }

    /* =========================================================
       RENDER RESULTADO
    ========================================================== */

    function renderResult(order) {

        window.currentPurchaseOrder =
            order;

        const items =
            Array.isArray(order.Items)
                ? order.Items
                : [];

        const docName =
            getOrderDocName(order);

        const docDate =
            getOrderDocDate(order);

        const observations =
            getOrderObservations(order);

        const warehouse =
            getOrderWarehouse(
                order,
                items
            );

        document.getElementById(
            'resDocName'
        ).textContent =
            docName;

        document.getElementById(
            'resDocDate'
        ).textContent =
            dateFmt(docDate);

        document.getElementById(
            'resWarehouse'
        ).textContent =
            warehouse;

        const observationsElement =
            document.getElementById(
                'resDocObservations'
            );

        const observationsText =
            document.getElementById(
                'resObservationsText'
            );

        if (observations) {

            observationsText.textContent =
                observations;

            observationsElement.style.display =
                'block';

        } else {

            observationsText.textContent =
                '';

            observationsElement.style.display =
                'none';

        }

        const badgeElement =
            document.getElementById(
                'resBadge'
            );

        if (order.IsAnnulled) {

            badgeElement.textContent =
                'Anulada';

            badgeElement.className =
                'badge badge-invalid';

        } else {

            badgeElement.textContent =
                'Valida';

            badgeElement.className =
                'badge badge-ok';

        }

        renderItems(items);

        document.getElementById(
            'checklistOrder'
        ).textContent =
            docName;

        document.getElementById(
            'checklistDate'
        ).textContent =
            dateFmt(docDate);

        resultCard.classList.add('show');

        checklistCard.classList.add('show');

        updateReceivingJson();

        resultCard.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });

    }

    /* =========================================================
       BUSCAR ORDEN
    ========================================================== */

    form.addEventListener('submit', async function(event) {

            event.preventDefault();

            const token = tokenInput.value.trim();
            const query = queryInput.value.trim();

            if (!token) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Falta el token de acceso',
                    text: 'Obten de siigo el token de acceso y copialo aca para realizar la consulta.',
                    confirmButtonColor: '#16a34a'
                });
                return;
            }

            if (!query) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Falta el numero de orden',
                    text: 'Escribe el numero de la orden de compra que quieres consultar.',
                    confirmButtonColor: '#16a34a'
                });
                return;
            }

            submitBtn.disabled = true;
            submitBtn.classList.add('is-loading');
            btnLabel.textContent = 'Buscando...';
            resultCard.classList.remove('show');
            checklistCard.classList.remove('show');

            Swal.fire({
                title: 'Consultando orden de compra',
                text: 'Por favor espera un momento...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: function() {
                    Swal.showLoading();
                }
            });

            try {

                const url = SEARCH_URL + '?token=' + encodeURIComponent(token) + '&query=' + encodeURIComponent(query);
                const response = await fetch(url, {
                        method: 'GET',
                        headers: {
                            'Accept':'application/json'
                        }
                    }
                );

                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }

                const data = await response.json();
                const order = data.invoice_purchase_order;

                if (!order) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No encontrada',
                        text: 'No se encontro ninguna orden de compra con el numero "' + query + '". Verifica el dato e intenta de nuevo.',
                        confirmButtonColor: '#16a34a'
                    });
                    return;
                }

                renderResult(order);

                Swal.fire({
                    icon: 'success',
                    title: 'Orden encontrada',
                    timer: 1200,
                    showConfirmButton: false
                });

            } catch (error) {

                Swal.fire({

                    icon:
                        'error',

                    title:
                        'Ocurrio un error',

                    text:
                        'No se pudo consultar la orden de compra. Intenta de nuevo en unos segundos.',

                    confirmButtonColor:
                        '#d33'

                });

            } finally {

                submitBtn.disabled =
                    false;

                submitBtn.classList.remove(
                    'is-loading'
                );

                btnLabel.textContent =
                    'Buscar';

            }

        }
    );

    /* =========================================================
       BUSCAR OTRA ORDEN
    ========================================================== */

    function resetReceivingView() {
        resultCard.classList.remove('show');
        checklistCard.classList.remove('show');
        queryInput.value = '';
        window.currentPurchaseOrder = null;
        window.receivingItems = [];
        window.receivingData = null;
        window.receivingJson = null;
        checklistForm.reset();
        receivingImages.length = 0;
        receivingFilesPreview.innerHTML = '';
        receivingFileError.classList.remove('show');
        document.getElementById('resItemsBody').innerHTML = '';
        document.getElementById('resRequested').textContent = '0';
        document.getElementById('resReceived').textContent = '0';
        document.getElementById('resPending').textContent = '0';
        queryInput.focus();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    btnReset.addEventListener('click', function() {
        resetReceivingView();
    });

    /* =========================================================
       UPLOAD - CLICK
    ========================================================== */

    receivingDropzone.addEventListener(
        'click',
        function() {

            receivingFileInput.click();

        }
    );

    /* =========================================================
       UPLOAD - DRAG
    ========================================================== */

    [
        'dragover',
        'dragenter'
    ].forEach(function(eventName) {

        receivingDropzone.addEventListener(
            eventName,
            function(event) {

                event.preventDefault();

                receivingDropzone.classList.add(
                    'dragover'
                );

            }
        );

    });

    [
        'dragleave',
        'dragend'
    ].forEach(function(eventName) {

        receivingDropzone.addEventListener(
            eventName,
            function() {

                receivingDropzone.classList.remove(
                    'dragover'
                );

            }
        );

    });

    /* =========================================================
       UPLOAD - DROP
    ========================================================== */

    receivingDropzone.addEventListener(
        'drop',
        function(event) {

            event.preventDefault();

            receivingDropzone.classList.remove(
                'dragover'
            );

            const files =
                Array.from(
                    event.dataTransfer.files || []
                );

            files.forEach(function(file) {

                addReceivingImage(file);

            });

        }
    );

    /* =========================================================
       UPLOAD - INPUT
    ========================================================== */

    receivingFileInput.addEventListener(
        'change',
        function() {

            const files =
                Array.from(
                    receivingFileInput.files || []
                );

            files.forEach(function(file) {

                addReceivingImage(file);

            });

            receivingFileInput.value =
                '';

        }
    );

    /* =========================================================
       AGREGAR IMAGEN
    ========================================================== */

    function addReceivingImage(file) {

        receivingFileError.classList.remove(
            'show'
        );

        const extension =
            file.name
                .split('.')
                .pop()
                .toLowerCase();

        if (
            !ALLOWED_RECEIVING_IMAGE_EXT.includes(
                extension
            )
        ) {

            showReceivingFileError(
                'Solo se permiten imagenes JPG o PNG.'
            );

            return;

        }

        if (
            file.size /
            (1024 * 1024)
            > MAX_RECEIVING_IMAGE_MB
        ) {

            showReceivingFileError(
                `La imagen "${file.name}" supera el tamaño máximo de ${MAX_RECEIVING_IMAGE_MB} MB.`
            );

            return;

        }

        const alreadyExists =
            receivingImages.some(
                function(item) {

                    return (
                        item.name === file.name &&
                        item.size === file.size
                    );

                }
            );

        if (alreadyExists) {

            return;

        }

        receivingImages.push(file);

        renderReceivingImages();

    }

    /* =========================================================
       RENDER IMAGENES
    ========================================================== */

    function renderReceivingImages() {

        receivingFilesPreview.innerHTML =
            '';

        receivingImages.forEach(
            function(file, index) {

                const preview =
                    document.createElement(
                        'div'
                    );

                preview.className =
                    'receiving-file-preview';

                const image =
                    document.createElement(
                        'img'
                    );

                image.src =
                    URL.createObjectURL(file);

                image.alt =
                    file.name;

                const info =
                    document.createElement(
                        'div'
                    );

                info.className =
                    'receiving-file-info';

                const name =
                    document.createElement(
                        'div'
                    );

                name.className =
                    'receiving-file-name';

                name.textContent =
                    file.name;

                const size =
                    document.createElement(
                        'div'
                    );

                size.className =
                    'receiving-file-size';

                size.textContent =
                    formatReceivingFileSize(
                        file.size
                    );

                info.appendChild(name);

                info.appendChild(size);

                const removeButton =
                    document.createElement(
                        'button'
                    );

                removeButton.type =
                    'button';

                removeButton.className =
                    'receiving-file-remove';

                removeButton.innerHTML =
                    '&times;';

                removeButton.title =
                    'Eliminar imagen';

                removeButton.addEventListener(
                    'click',
                    function(event) {

                        event.preventDefault();

                        event.stopPropagation();

                        removeReceivingImage(
                            index
                        );

                    }
                );

                preview.appendChild(
                    image
                );

                preview.appendChild(
                    info
                );

                preview.appendChild(
                    removeButton
                );

                receivingFilesPreview.appendChild(
                    preview
                );

            }
        );

    }

    /* =========================================================
       ELIMINAR IMAGEN
    ========================================================== */

    function removeReceivingImage(index) {

        if (
            index < 0 ||
            index >= receivingImages.length
        ) {

            return;

        }

        receivingImages.splice(
            index,
            1
        );

        renderReceivingImages();

    }

    /* =========================================================
       FORMATO TAMAÑO
    ========================================================== */

    function formatReceivingFileSize(bytes) {

        if (!bytes) {

            return '0 KB';

        }

        const mb =
            bytes /
            (1024 * 1024);

        if (mb >= 1) {

            return mb.toFixed(2) +
                ' MB';

        }

        return Math.ceil(
            bytes / 1024
        ) + ' KB';

    }

    /* =========================================================
       ERROR ARCHIVO
    ========================================================== */

    function showReceivingFileError(message) {

        receivingFileError.textContent =
            message;

        receivingFileError.classList.add(
            'show'
        );

    }

    /* =========================================================
       GUARDAR RECEPCION
    ========================================================== */

    checklistForm.addEventListener(
        'submit',
        async function(event) {

            event.preventDefault();

            /* -------------------------------------------------
               VALIDAR ORDEN
            ------------------------------------------------- */

            if (!window.currentPurchaseOrder) {

                Swal.fire({

                    icon:
                        'warning',

                    title:
                        'Orden no encontrada',

                    text:
                        'Primero debes consultar una orden de compra.',

                    confirmButtonColor:
                        '#16a34a'

                });

                return;

            }

            /* -------------------------------------------------
               CONSTRUIR CHECKLIST
            ------------------------------------------------- */

            const checklistFormData = new FormData(checklistForm);
            const observaciones = (checklistFormData.get('observaciones') || '').trim();
            const firmaNombre = (checklistFormData.get('firma_nombre') || '').trim();
            const firmaCargo = (checklistFormData.get('firma_cargo') || '').trim();
            const firmaDepartamento = (checklistFormData.get('firma_departamento') || '').trim();

            if (!observaciones || !firmaNombre || !firmaCargo || !firmaDepartamento || receivingImages.length === 0) {
                let mensaje = 'Completa los campos obligatorios.';
                if (!observaciones) mensaje = 'Ingresa las observaciones de la recepción.';
                else if (!firmaNombre) mensaje = 'Ingresa el nombre de quien realiza la recepción.';
                else if (!firmaCargo) mensaje = 'Ingresa el cargo de quien realiza la recepción.';
                else if (!firmaDepartamento) mensaje = 'Ingresa el departamento de quien realiza la recepción.';
                else if (receivingImages.length === 0) mensaje = 'Debes adjuntar al menos una imagen como evidencia de la recepción.';
                Swal.fire({ icon: 'warning', title: 'Campos incompletos', text: mensaje, confirmButtonColor: '#3085d6' });
                return;
            }

            const checklist = {

                cajas_master:
                    checklistFormData.has(
                        'check_cajas_master'
                    ),

                sellos:
                    checklistFormData.has(
                        'check_sellos'
                    ),

                estado_cajas:
                    checklistFormData.has(
                        'check_estado_cajas'
                    ),

                documentos:
                    checklistFormData.has(
                        'check_documentos'
                    ),

                empaque:
                    checklistFormData.has(
                        'check_empaque'
                    ),

                stickers:
                    checklistFormData.has(
                        'check_stickers'
                    ),

                producto:
                    checklistFormData.has(
                        'check_producto'
                    ),

                exactitud:
                    checklistFormData.has(
                        'check_exactitud'
                    ),

                variacion_diseno:
                    checklistFormData.has(
                        'check_variacion_diseno'
                    ),

                firma:
                    checklistFormData.has(
                        'check_firma'
                    ),

                reporte_whatsapp:
                    checklistFormData.has(
                        'check_reporte_whatsapp'
                    ),

                respaldo_whatsapp:
                    checklistFormData.has(
                        'check_respaldo_whatsapp'
                    ),

                alerta:
                    checklistFormData.has(
                        'check_alerta'
                    ),

                venta:
                    checklistFormData.has(
                        'check_venta'
                    ),

                referencias_recibidas: checklistFormData.get('referencias_recibidas') || '',
                observaciones: checklistFormData.get('observaciones') || '',
                responsable: {
                    nombre: checklistFormData.get('firma_nombre') || '',
                    cargo: checklistFormData.get('firma_cargo') || '',
                    departamento: checklistFormData.get('firma_departamento') || '',
                    celular: checklistFormData.get('firma_celular') || ''
                }

            };

            /* -------------------------------------------------
               JSON COMPLETO
            ------------------------------------------------- */

            const receivingJson =
                buildReceivingJson();

            if (!receivingJson) {

                Swal.fire({

                    icon:
                        'error',

                    title:
                        'Datos incompletos',

                    text:
                        'No fue posible construir la información de la recepción.',

                    confirmButtonColor:
                        '#dc2626'

                });

                return;

            }

            window.receivingData = {

                ...receivingJson,

                Checklist:
                    checklist

            };

            /* -------------------------------------------------
               FORM DATA
            ------------------------------------------------- */

            const requestData =
                new FormData();

            /*
             * JSON de recepción.
             */

            requestData.append(
                'receivingData',
                JSON.stringify(
                    window.receivingData
                )
            );

            /*
             * Imágenes.
             */

            receivingImages.forEach(
                function(file) {

                    requestData.append(
                        'imagenes[]',
                        file,
                        file.name
                    );

                }
            );

            /* -------------------------------------------------
               BOTON
            ------------------------------------------------- */

            const saveButton =
                document.getElementById(
                    'saveChecklistBtn'
                );

            const originalButtonText =
                saveButton.textContent;

            saveButton.disabled =
                true;

            saveButton.textContent =
                'Enviando...';

            /* -------------------------------------------------
               LOADING
            ------------------------------------------------- */

            Swal.fire({

                title:
                    'Registrando recepción',

                text:
                    'Enviando información y evidencia fotográfica...',

                allowOutsideClick:
                    false,

                allowEscapeKey:
                    false,

                didOpen:
                    function() {

                        Swal.showLoading();

                    }

            });

            try {

                /* ---------------------------------------------
                   FETCH
                ---------------------------------------------- */

                const response =
                    await fetch(
                        '{{ route("siigo.invoice_purchase_order_confirmed") }}',
                        {

                            method:
                                'POST',

                            headers: {

                                'X-CSRF-TOKEN':
                                    '{{ csrf_token() }}',

                                'Accept':
                                    'application/json'

                            },

                            body:
                                requestData

                        }
                    );

                /* ---------------------------------------------
                   RESPUESTA
                ---------------------------------------------- */

                let data;

                try {

                    data =
                        await response.json();

                } catch (parseError) {

                    throw new Error(
                        'El servidor respondió de forma inesperada. Intenta nuevamente.'
                    );

                }

                if (!response.ok || !data.success) {
                    const firstError = data.errors ? Object.values(data.errors).flat()[0] : null;
                    Swal.close();
                    Swal.fire({ icon: 'warning', title: 'Campos incompletos', text: firstError || data.message || 'Los datos enviados no son válidos.', confirmButtonColor: '#3085d6' });
                    return;
                }

                /* ---------------------------------------------
                   EXITO
                ---------------------------------------------- */

                await Swal.fire({

                    icon:
                        'success',

                    title:
                        'Recepción registrada',

                    text:
                        data.message ||
                        'La recepción fue registrada y el correo fue enviado correctamente.',

                    confirmButtonColor:
                        '#16a34a'

                });

                resetReceivingView();

                /*
                 * Limpiamos las imágenes después
                 * de haber enviado correctamente.
                 */

                receivingImages.length =
                    0;

                receivingFilesPreview.innerHTML =
                    '';

                receivingFileError.classList.remove(
                    'show'
                );

            } catch (error) {

                Swal.fire({

                    icon:
                        'error',

                    title:
                        'No se pudo registrar la recepción',

                    text:
                        error.message ||
                        'No fue posible enviar la información.',

                    confirmButtonColor:
                        '#dc2626'

                });

            } finally {

                saveButton.disabled =
                    false;

                saveButton.textContent =
                    originalButtonText;

            }

        }
    );

})();

</script>

</body>

</html>
