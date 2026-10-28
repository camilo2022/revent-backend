<style>

        * {
            box-sizing: border-box;
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
            transition: all 0.2s ease;
        }

        /* RECIBIDO < SOLICITADO */
        .receiving-input.partial {
            border-color: #f59e0b;
            background: #fffbeb;
            color: #b45309;
        }

        /* RECIBIDO = SOLICITADO */
        .receiving-input.complete {
            border-color: #16a34a;
            background: #f0fdf4;
            color: #166534;
        }

        /* RECIBIDO > SOLICITADO */
        .receiving-input.over {
            border-color: #2563eb;
            background: #eff6ff;
            color: #1d4ed8;
        }

        /* Mantener el color del estado al hacer focus */
        .receiving-input.partial:focus {
            border-color: #f59e0b;
            background: #fffbeb;
        }

        .receiving-input.complete:focus {
            border-color: #16a34a;
            background: #f0fdf4;
        }

        .receiving-input.over:focus {
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

            .rv-page { padding: 1rem 0.5rem; }

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


        /* =========================================================
           APROBACION DE TRASLADO EN TRANSITO
        ========================================================== */

        .qty-input {
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
            transition: all 0.2s ease;
        }

        /* APROBANDO < MAXIMO */
        .qty-input.partial,
        .qty-input.partial:focus {
            border-color: #ef4444;
            background: #fef2f2;
            color: #b91c1c;
        }

        /* APROBANDO = MAXIMO */
        .qty-input.complete,
        .qty-input.complete:focus {
            border-color: #16a34a;
            background: #f0fdf4;
            color: #166534;
        }

        /* APROBANDO > MAXIMO (no permitido) */
        .qty-input.over,
        .qty-input.over:focus {
            border-color: #2563eb;
            background: #eff6ff;
            color: #1d4ed8;
        }

        .qty-input:disabled {
            background: #f3f4f6;
            border-color: #e5e7eb;
            color: #9ca3af;
        }

        .qty-note {
            display: none;
            margin-top: 0.25rem;
            font-size: 0.68rem;
            font-weight: 600;
            color: #1d4ed8;
        }

        .qty-note.show {
            display: block;
        }

        .qty-actions {
            display: inline-flex;
            gap: 0.3rem;
            margin-left: 0.4rem;
        }

        .qty-actions button {
            border: 1px solid #d1d5db;
            background: #ffffff;
            border-radius: 6px;
            padding: 0.25rem 0.45rem;
            font-size: 0.7rem;
            font-weight: 600;
            cursor: pointer;
        }

        .qty-actions button:hover {
            background: #f0fdf4;
            border-color: #16a34a;
        }

        .approval-confirm {
            margin-top: 1.2rem;
            padding-top: 1.1rem;
            border-top: 1px solid #e5e7eb;
        }

        .image-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 0.8rem;
            margin-top: 0.6rem;
        }

        .image-card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 0.5rem;
            text-align: center;
            background: #fff;
        }

        .image-card img {
            width: 100%;
            height: 170px;
            object-fit: contain;
        }

        .image-card .no-image {
            height: 170px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #9ca3af;
            font-size: 0.8rem;
        }

        .image-card .image-ref {
            font-weight: 700;
            margin-top: 0.4rem;
        }

        .image-card .image-color {
            font-size: 0.8rem;
            color: #6b7280;
        }
</style>
