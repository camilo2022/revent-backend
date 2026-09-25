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
            flex: 0 0 auto;
            width: 94px;
            height: 44px;
            padding: 0 1rem;
            margin: 0;
            background: #16a34a;
            color: #ffffff;
            border: none;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;

            display: inline-flex;
            align-items: center;
            justify-content: center;

            transition: background 0.2s ease, transform 0.1s ease;
        }

        .excel-submit-btn:hover {
            background: #15803d;
        }

        .excel-submit-btn:active {
            transform: scale(0.98);
        }

        .excel-submit-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .btn-spinner {
            display: none;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255, 255, 255, 0.4);
            border-top-color: #ffffff;
            border-radius: 50%;
            margin-right: 0.5rem;
            animation: btn-spin 0.7s linear infinite;
        }

        .excel-submit-btn.is-loading .btn-spinner {
            display: inline-block;
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

        /* Estados: igual (0) = verde, falta (>0) = naranja, sobra (<0) = azul */
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
                Escribe el numero de la orden de compra
                (por ejemplo <strong>OC-1-12345</strong>)
                para consultar su informacion y lo recibido
                hasta el momento.
            </div>

            @if (session('status'))
                <div class="excel-status">
                    {{ session('status') }}
                </div>
            @endif

            <form id="searchForm" autocomplete="off">

                <div class="excel-field-group">

                    <label for="query" class="excel-field-label">
                        N° de orden de compra *
                    </label>

                    <div class="excel-search-row">

                        <input type="text" name="query" id="query" class="excel-field-input"
                            placeholder="Ej: OC-1-12345" required autofocus>

                        <button type="submit" class="excel-submit-btn" id="submitBtn">
                            <span class="btn-spinner"></span>
                            <span class="btn-label">Buscar</span>
                        </button>

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

        <div class="result-card" id="resultCard">

            <div class="result-header">

                <div>

                    <div class="result-doc-name" id="resDocName">-</div>

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

                <span class="badge" id="resBadge">-</span>

            </div>

            <!-- =====================================================
                 RESUMEN
            ====================================================== -->

            <div class="summary-grid">

                <div class="summary-card summary-requested">
                    <div class="amount" id="resRequested">0</div>
                    <div class="label">Cantidad solicitada</div>
                </div>

                <div class="summary-card summary-received">
                    <div class="amount" id="resReceived">0</div>
                    <div class="label">Cantidad ingresando</div>
                </div>

                <div class="summary-card summary-pending">
                    <div class="amount" id="resPending">0</div>
                    <div class="label">Cantidad pendiente</div>
                </div>

            </div>

            <!-- =====================================================
                 ITEMS
            ====================================================== -->

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

        <div class="checklist-card" id="checklistCard">

            <div class="checklist-header">

                <div class="checklist-company">REVENT CALZADO S.A.S.</div>

                <div class="checklist-department">GERENCIA ADMINISTRATIVA</div>

                <div class="checklist-department">DEPARTAMENTO DE OPERACIONES Y LOGISTICA</div>

                <div class="checklist-title">
                    CHECKLIST RAPIDO -
                    RECEPCION DE MERCANCIA TIENDAS
                </div>

                <div class="checklist-order-info">

                    <span class="checklist-order-badge">
                        OC:
                        <strong id="checklistOrder">-</strong>
                    </span>

                    <span class="checklist-order-badge">
                        Fecha:
                        <strong id="checklistDate">-</strong>
                    </span>

                </div>

            </div>

            <form id="receivingChecklistForm">

                <!-- =================================================
                     1. FRENTE AL TRANSPORTADOR
                ================================================== -->

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
                            <input type="checkbox" name="check_respaldo_whatsapp" value="1">
                        </div>
                    </div>

                    <div class="checklist-row">
                        <div class="checklist-description">
                            <strong>Alerta:</strong>
                            Si hay fallas, informar a Operaciones
                            ANTES de recibir.
                        </div>
                        <div class="checklist-check">
                            <input type="checkbox" name="check_alerta" value="1">
                        </div>
                    </div>

                    <div class="checklist-row">
                        <div class="checklist-description">
                            <strong>Venta:</strong>
                            NO vender hasta que confirmen
                            carga en SIIGO.
                        </div>
                        <div class="checklist-check">
                            <input type="checkbox" name="check_venta" value="1">
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

                    <textarea class="checklist-textarea" name="referencias_recibidas" id="referenciasRecibidas"
                        placeholder="Escribe las referencias recibidas..."></textarea>

                </div>

                <!-- =================================================
                     OBSERVACIONES
                ================================================== -->

                <div class="checklist-section">

                    <div class="checklist-section-title">
                        Observaciones
                    </div>

                    <textarea class="checklist-textarea" name="observaciones" id="checklistObservaciones"
                        placeholder="Escribe las observaciones de la recepcion..."></textarea>

                </div>

                <div class="checklist-submit-wrapper">
                    <button type="submit" class="checklist-submit-btn" id="saveChecklistBtn">
                        Guardar
                    </button>
                </div>

            </form>

        </div>

        <!-- =========================================================
             VOLVER
        ========================================================== -->

        <a href="{{ route('home') }}" class="back-link">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                stroke-linejoin="round">
                <line x1="19" y1="12" x2="5" y2="12" />
                <polyline points="12 19 5 12 12 5" />
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

            const SEARCH_URL = @json(route('siigo.invoice_purchase_order_search'));

            const form = document.getElementById('searchForm');
            const queryInput = document.getElementById('query');
            const submitBtn = document.getElementById('submitBtn');
            const btnLabel = submitBtn.querySelector('.btn-label');
            const resultCard = document.getElementById('resultCard');
            const checklistCard = document.getElementById('checklistCard');
            const btnReset = document.getElementById('btnReset');
            const checklistForm = document.getElementById('receivingChecklistForm');

            /* =========================================================
               VARIABLES GLOBALES
            ========================================================== */

            window.currentPurchaseOrder = null;
            window.receivingItems = [];
            window.receivingData = null;

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
                const absolute = Math.abs(Math.round(number));

                const formatted = String(absolute)
                    .replace(/\B(?=(\d{3})+(?!\d))/g, '.');

                return negative ? '-' + formatted : formatted;
            }

            function qtyFmt(value) {
                return formatThousands(value);
            }

            /* =========================================================
               FORMATO FECHA SIIGO
            ========================================================== */

            function dateFmt(value) {

                if (!value) {
                    return '-';
                }

                const stringValue = String(value);

                /*
                 * Formato Siigo: YYYYMMDD (ej: 20260924)
                 */
                const siigoMatch = stringValue.match(/^(\d{4})(\d{2})(\d{2})$/);

                if (siigoMatch) {
                    return siigoMatch[3] + '/' + siigoMatch[2] + '/' + siigoMatch[1];
                }

                /*
                 * Formato: YYYY-MM-DD
                 */
                const isoMatch = stringValue.match(/^(\d{4})-(\d{2})-(\d{2})/);

                if (isoMatch) {
                    return isoMatch[3] + '/' + isoMatch[2] + '/' + isoMatch[1];
                }

                const date = new Date(value);

                if (Number.isNaN(date.getTime())) {
                    return '-';
                }

                const day = String(date.getDate()).padStart(2, '0');
                const month = String(date.getMonth() + 1).padStart(2, '0');

                return day + '/' + month + '/' + date.getFullYear();
            }

            /* =========================================================
               CLASE SEGUN EL PENDIENTE
               (>0 falta = naranja, 0 = verde, <0 sobra = azul)
            ========================================================== */

            function pendingClass(pending) {

                pending = Number(pending) || 0;

                if (pending > 0) {
                    return 'cover-partial';
                }

                if (pending < 0) {
                    return 'cover-over';
                }

                return 'cover-full';
            }

            /* =========================================================
               OBTENER DATOS DE LA ORDEN
            ========================================================== */

            function getOrderEntry(order) {
                return order && order.Entry ? order.Entry : {};
            }

            function getOrderDocName(order) {
                const entry = getOrderEntry(order);
                return order.DocName || entry.DocName || '-';
            }

            function getOrderDocDate(order) {
                const entry = getOrderEntry(order);
                return order.DocDate || entry.DocDate || null;
            }

            function getOrderObservations(order) {
                const entry = getOrderEntry(order);
                return order.Observations || entry.Observations || '';
            }

            function getOrderWarehouse(order, items) {

                if (order.Warehouse) {
                    return order.Warehouse;
                }

                const firstItem = items && items.length ? items[0] : null;

                if (firstItem && firstItem.Warehouse) {
                    return firstItem.Warehouse;
                }

                return '-';
            }

            /* =========================================================
               RENDER ITEMS
            ========================================================== */

            function renderItems(items) {

                const body = document.getElementById('resItemsBody');

                if (!Array.isArray(items) || !items.length) {

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

                /*
                 * Guardamos los items originales.
                 * La cantidad ingresada empieza en cero.
                 */

                window.receivingItems = items.map(function(item) {
                    return {
                        ...item,
                        ReceivingQuantity: 0
                    };
                });

                body.innerHTML = window.receivingItems
                    .map(function(item, index) {

                        const requested = Number(item.Quantity) || 0;

                        /*
                         * Primero LongDescription. Si no existe: Description.
                         */

                        const description = item.LongDescription || item.Description || '-';

                        return `
                            <tr
                                data-item-index="${index}"
                                data-product-code="${esc(item.ProductCode || '')}"
                            >

                                <td class="description-cell">
                                    ${esc(description)}
                                </td>

                                <td>
                                    <span class="prefix-tag">
                                        ${esc(item.Reference || '-')}
                                    </span>
                                </td>

                                <td>${esc(item.Color || '-')}</td>

                                <td>${esc(item.Category || '-')}</td>

                                <td>${esc(item.Size || '-')}</td>

                                <td>
                                    <strong>${qtyFmt(requested)}</strong>
                                </td>

                                <td>
                                    <input type="number" class="receiving-input" min="0" step="1" value="0"
                                        data-item-index="${index}">
                                </td>

                                <td>
                                    <span
                                        class="pending-value cover-none"
                                        data-item-index="${index}"
                                    >
                                        ${qtyFmt(requested)}
                                    </span>
                                </td>

                            </tr>
                        `;
                    })
                    .join('');

                /*
                 * Eventos de cantidades.
                 */

                body.querySelectorAll('.receiving-input').forEach(function(input) {
                    input.addEventListener('input', function() {
                        updateItemReceiving(this);
                    });
                });

                updateReceivingSummary();
            }

            /* =========================================================
               ACTUALIZAR ITEM
               La cantidad pendiente es siempre requested - receiving
               (puede quedar negativa si se recibe de más) y toma
               directamente el color de pendingClass.
            ========================================================== */

            function updateItemReceiving(input) {

                const index = Number(input.dataset.itemIndex);
                const item = window.receivingItems[index];

                if (!item) {
                    return;
                }

                const requested = Number(item.Quantity) || 0;
                let receiving = Number(input.value);

                /*
                 * Nunca permitimos negativos en lo ingresado.
                 */

                if (!Number.isFinite(receiving) || receiving < 0) {
                    receiving = 0;
                    input.value = 0;
                }

                /*
                 * IMPORTANTE: no hay maximo. Puede recibir mas de lo solicitado.
                 */

                item.ReceivingQuantity = receiving;

                /*
                 * Pendiente real (puede quedar negativo si se recibe de más).
                 */

                const pending = requested - receiving;

                const pendingElement = document.querySelector(
                    `.pending-value[data-item-index="${index}"]`
                );

                input.classList.toggle('over', receiving > requested);

                if (pendingElement) {
                    pendingElement.textContent = qtyFmt(pending);
                    pendingElement.className = 'pending-value ' + pendingClass(pending);
                }

                updateReceivingSummary();
                updateReceivingJson();
            }

            /* =========================================================
               RESUMEN GENERAL
               El pendiente total tambien puede quedar negativo si,
               en conjunto, se recibio mas de lo solicitado.
            ========================================================== */

            function calculateTotals() {

                const items = window.receivingItems || [];

                let requested = 0;
                let receiving = 0;
                let pending = 0;

                items.forEach(function(item) {

                    const itemRequested = Number(item.Quantity) || 0;
                    const itemReceiving = Number(item.ReceivingQuantity) || 0;

                    requested += itemRequested;
                    receiving += itemReceiving;
                    pending += (itemRequested - itemReceiving);
                });

                return {
                    requested,
                    receiving,
                    pending
                };
            }

            function updateReceivingSummary() {

                const totals = calculateTotals();

                document.getElementById('resRequested').textContent = qtyFmt(totals.requested);
                document.getElementById('resReceived').textContent = qtyFmt(totals.receiving);
                document.getElementById('resPending').textContent = qtyFmt(totals.pending);
            }

            /* =========================================================
               JSON DE RECEPCION
            ========================================================== */

            function buildReceivingJson() {

                const order = window.currentPurchaseOrder;

                if (!order) {
                    return null;
                }

                const entry = getOrderEntry(order);

                const items = (window.receivingItems || []).map(function(item) {

                    const requested = Number(item.Quantity) || 0;
                    const receiving = Number(item.ReceivingQuantity) || 0;
                    const pending = requested - receiving;

                    return {
                        ProductCode: item.ProductCode || null,
                        Description: item.LongDescription || item.Description || null,
                        LongDescription: item.LongDescription || null,
                        Reference: item.Reference || null,
                        Color: item.Color || null,
                        Category: item.Category || null,
                        Size: item.Size || null,
                        WarehouseCode: item.WarehouseCode || null,
                        Warehouse: item.Warehouse || null,
                        Quantity: requested,
                        ReceivingQuantity: receiving,
                        PendingQuantity: pending
                    };
                });

                const totals = calculateTotals();

                return {

                    Order: {
                        ACEntryID: order.ACEntryID || entry.ACEntryID || null,
                        DocName: getOrderDocName(order),
                        DocDate: getOrderDocDate(order),
                        DeadlineDate: order.DeadlineDate || null,
                        Identification: order.Identification || null,
                        FullName: order.FullName || null,
                        CompanyName: order.CompanyName || null,
                        Warehouse: getOrderWarehouse(order, window.receivingItems),
                        Observations: getOrderObservations(order)
                    },

                    Items: items,

                    Totals: {
                        Requested: totals.requested,
                        Receiving: totals.receiving,
                        Pending: totals.pending
                    }
                };
            }

            function updateReceivingJson() {
                window.receivingJson = buildReceivingJson();
            }

            /* =========================================================
               RENDER RESULTADO
            ========================================================== */

            function renderResult(order) {

                window.currentPurchaseOrder = order;

                const entry = getOrderEntry(order);
                const items = Array.isArray(order.Items) ? order.Items : [];

                const docName = getOrderDocName(order);
                const docDate = getOrderDocDate(order);
                const observations = getOrderObservations(order);
                const warehouse = getOrderWarehouse(order, items);

                /*
                 * Nombre OC.
                 */

                document.getElementById('resDocName').textContent = docName;

                /*
                 * Fecha.
                 */

                document.getElementById('resDocDate').textContent = dateFmt(docDate);

                /*
                 * Bodega.
                 */

                document.getElementById('resWarehouse').textContent = warehouse;

                /*
                 * Observaciones.
                 */

                const observationsElement = document.getElementById('resDocObservations');
                const observationsText = document.getElementById('resObservationsText');

                if (observations) {
                    observationsText.textContent = observations;
                    observationsElement.style.display = 'block';
                } else {
                    observationsText.textContent = '';
                    observationsElement.style.display = 'none';
                }

                /*
                 * Estado.
                 */

                const badgeElement = document.getElementById('resBadge');

                if (order.IsAnnulled) {
                    badgeElement.textContent = 'Anulada';
                    badgeElement.className = 'badge badge-invalid';
                } else {
                    badgeElement.textContent = 'Valida';
                    badgeElement.className = 'badge badge-ok';
                }

                /*
                 * Items.
                 */

                renderItems(items);

                /*
                 * Checklist.
                 */

                document.getElementById('checklistOrder').textContent = docName;
                document.getElementById('checklistDate').textContent = dateFmt(docDate);

                /*
                 * Mostrar.
                 */

                resultCard.classList.add('show');
                checklistCard.classList.add('show');

                /*
                 * JSON inicial.
                 */

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

                const query = queryInput.value.trim();

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

                    const url = SEARCH_URL + '?query=' + encodeURIComponent(query);

                    const response = await fetch(url, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json'
                        }
                    });

                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }

                    const data = await response.json();

                    /*
                     * La respuesta real viene:
                     * {
                     *     invoice_purchase_order: {
                     *         Entry: {...},
                     *         Items: [...]
                     *     }
                     * }
                     */

                    const order = data.invoice_purchase_order;

                    if (!order) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'No encontrada',
                            text: 'No se encontro ninguna orden de compra con el numero "' + query +
                                '". Verifica el dato e intenta de nuevo.',
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

                    console.error('Error consultando OC:', error);

                    Swal.fire({
                        icon: 'error',
                        title: 'Ocurrio un error',
                        text: 'No se pudo consultar la orden de compra. Intenta de nuevo en unos segundos.',
                        confirmButtonColor: '#d33'
                    });

                } finally {

                    submitBtn.disabled = false;
                    submitBtn.classList.remove('is-loading');
                    btnLabel.textContent = 'Buscar';
                }
            });

            /* =========================================================
               BUSCAR OTRA ORDEN
            ========================================================== */

            btnReset.addEventListener('click', function() {

                resultCard.classList.remove('show');
                checklistCard.classList.remove('show');

                queryInput.value = '';

                window.currentPurchaseOrder = null;
                window.receivingItems = [];
                window.receivingData = null;
                window.receivingJson = null;

                checklistForm.reset();

                document.getElementById('resItemsBody').innerHTML = '';

                queryInput.focus();

                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });

            /* =========================================================
               GUARDAR RECEPCION
            ========================================================== */

            checklistForm.addEventListener('submit', async function(event) {
                event.preventDefault();

                if (!window.currentPurchaseOrder) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Orden no encontrada',
                        text: 'Primero debes consultar una orden de compra.',
                        confirmButtonColor: '#16a34a'
                    });

                    return;
                }

                const formData = new FormData(checklistForm);

                /*
                * Checklist.
                */
                const checklist = {
                    cajas_master: formData.has('check_cajas_master'),
                    sellos: formData.has('check_sellos'),
                    estado_cajas: formData.has('check_estado_cajas'),
                    documentos: formData.has('check_documentos'),
                    empaque: formData.has('check_empaque'),
                    stickers: formData.has('check_stickers'),
                    producto: formData.has('check_producto'),
                    exactitud: formData.has('check_exactitud'),
                    variacion_diseno: formData.has('check_variacion_diseno'),
                    firma: formData.has('check_firma'),
                    reporte_whatsapp: formData.has('check_reporte_whatsapp'),
                    respaldo_whatsapp: formData.has('check_respaldo_whatsapp'),
                    alerta: formData.has('check_alerta'),
                    venta: formData.has('check_venta'),

                    referencias_recibidas:
                        formData.get('referencias_recibidas') || '',

                    observaciones:
                        formData.get('observaciones') || ''
                };

                /*
                * Construimos el JSON completo.
                */
                const receivingJson = buildReceivingJson();

                window.receivingData = {
                    ...receivingJson,
                    Checklist: checklist
                };

                console.log(
                    'DATOS COMPLETOS DE RECEPCION:',
                    window.receivingData
                );

                /*
                * Botón.
                */
                const saveButton = document.getElementById('saveChecklistBtn');

                saveButton.disabled = true;
                saveButton.textContent = 'Enviando...';

                Swal.fire({
                    title: 'Registrando recepción',
                    text: 'Enviando la información por correo...',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: function() {
                        Swal.showLoading();
                    }
                });

                try {

                    const response = await fetch('{{ route("siigo.invoice_purchase_order_confirmed") }}', {
                        method: 'POST',

                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },

                        body: JSON.stringify(window.receivingData)
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(
                            data.message || 'No fue posible registrar la recepción.'
                        );
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Recepción registrada',
                        text: data.message ||
                            'La recepción fue registrada y el correo fue enviado correctamente.',
                        confirmButtonColor: '#16a34a'
                    });

                    console.log(
                        'RESPUESTA BACKEND:',
                        data
                    );

                } catch (error) {

                    console.error(
                        'Error enviando recepción:',
                        error
                    );

                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message ||
                            'No fue posible enviar la información de la recepción.',
                        confirmButtonColor: '#dc2626'
                    });

                } finally {

                    saveButton.disabled = false;
                    saveButton.textContent = 'Guardar recepción';
                }
            });

        })();
    </script>

</body>

</html>
