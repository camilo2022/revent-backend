<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Reporte de facturación 360</title>
    <style>
        * { box-sizing: border-box; }

        body {
            background: #f3f4f6;
            font-family: 'Segoe UI', system-ui, sans-serif;
            margin: 0;
            padding: 2rem 1rem;
        }

        .excel-upload-wrapper {
            max-width: 580px;
            margin: 2rem auto;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }

        .excel-upload-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #eef0f2;
        }

        .excel-upload-title {
            font-size: 1.15rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.35rem;
        }

        .excel-upload-subtitle {
            font-size: 0.85rem;
            color: #6b7280;
            margin-bottom: 1.25rem;
        }

        .excel-field-group {
            margin-bottom: 1.1rem;
        }

        .excel-field-label {
            display: block;
            font-size: 0.82rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.4rem;
        }

        .excel-field-label .required-mark {
            color: #dc2626;
        }

        .excel-field-input,
        .excel-field-select {
            width: 100%;
            padding: 0.65rem 0.85rem;
            font-size: 0.88rem;
            color: #1f2937;
            background: #f9fafb;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            transition: border-color 0.2s ease, background 0.2s ease;
        }

        .excel-field-select {
            width: 100%;
            cursor: pointer;
        }

        .excel-field-input::placeholder {
            color: #9ca3af;
        }

        .excel-field-input:focus,
        .excel-field-select:focus {
            outline: none;
            border-color: #16a34a;
            background: #ffffff;
        }

        .excel-field-input:disabled {
            background: #f3f4f6;
            color: #9ca3af;
            cursor: not-allowed;
        }

        .excel-field-hint {
            font-size: 0.75rem;
            color: #9ca3af;
            margin-top: 0.3rem;
        }

        /* ---- Emails dinámicos ---- */
        .email-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.6rem;
        }

        .email-row .excel-field-input {
            width: 100%;
            flex: 1;
        }

        .email-remove-btn {
            flex-shrink: 0;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .email-remove-btn:hover {
            background: #fee2e2;
        }

        .email-remove-btn svg {
            width: 15px;
            height: 15px;
            stroke: #dc2626;
        }

        .email-remove-btn:disabled {
            opacity: 0.35;
            cursor: not-allowed;
        }

        .email-add-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.8rem;
            font-weight: 600;
            color: #16a34a;
            background: #f0fdf4;
            border: 1px dashed #86efac;
            border-radius: 8px;
            padding: 0.5rem 0.85rem;
            cursor: pointer;
            transition: background 0.2s ease;
            margin-top: 0.2rem;
        }

        .email-add-btn:hover {
            background: #dcfce7;
        }

        .email-add-btn svg {
            width: 14px;
            height: 14px;
            stroke: #16a34a;
        }

        /* ---- Tabs modo de fecha ---- */
        .mode-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.1rem;
            background: #f3f4f6;
            padding: 0.3rem;
            border-radius: 10px;
        }

        .mode-tab {
            flex: 1;
            text-align: center;
            font-size: 0.82rem;
            font-weight: 600;
            color: #6b7280;
            padding: 0.5rem 0.6rem;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s ease, color 0.2s ease;
            user-select: none;
        }

        .mode-tab.active {
            background: #ffffff;
            color: #16a34a;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
        }

        .mode-panel {
            display: none;
        }

        .mode-panel.active {
            display: block;
        }

        /* ---- Rango de fechas ---- */
        .date-range-group {
            display: flex;
            gap: 0.75rem;
        }

        .date-range-group .excel-field-group {
            flex: 1;
            margin-bottom: 0;
        }

        /* ---- Secciones ---- */
        .section-divider {
            border: none;
            border-top: 1px solid #f1f3f5;
            margin: 1.4rem 0;
        }

        .section-title {
            font-size: 0.78rem;
            font-weight: 700;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 0.8rem;
        }

        .excel-submit-btn {
            width: 100%;
            margin-top: 1.25rem;
            padding: 0.75rem;
            background: #16a34a;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s ease, transform 0.1s ease;
        }

        .excel-submit-btn:hover {
            background: #15803d;
        }

        .excel-submit-btn:active {
            transform: scale(0.98);
        }

        .excel-error {
            display: none;
            margin-top: 0.75rem;
            font-size: 0.8rem;
            color: #dc2626;
            background: #fef2f2;
            border: 1px solid #fecaca;
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
        }

        .excel-error.show {
            display: block;
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

        .back-link:hover {
            text-decoration: underline;
        }

        .back-link svg {
            width: 15px;
            height: 15px;
        }
    </style>
</head>

<body>

    <div class="excel-upload-wrapper">
        <div class="excel-upload-card">

            <div class="excel-upload-title">Generar reporte de facturación 360</div>
            <div class="excel-upload-subtitle">
                Elige un mes completo o un rango de fechas manual. El reporte se enviará a los correos indicados.
            </div>

            <form action="{{ route('siigo.export_invoice_download') }}" method="POST" id="invoiceReportForm">
                @csrf

                <div class="section-title">Correos de notificación</div>

                <div id="emailsContainer">
                    @foreach (old('emails', ['']) as $index => $emailValue)
                        <div class="email-row">
                            <input type="email" name="emails[]"
                                class="excel-field-input email-input @error('emails.' . $index) is-invalid @enderror"
                                placeholder="nombre@correo.com" value="{{ $emailValue }}">
                            <button type="button" class="email-remove-btn" {{ $loop->count <= 1 ? 'disabled' : '' }} title="Quitar correo">
                                <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="18" y1="6" x2="6" y2="18"/>
                                    <line x1="6" y1="6" x2="18" y2="18"/>
                                </svg>
                            </button>
                        </div>
                        @error('emails.' . $index)
                            <div class="excel-error show">{{ $message }}</div>
                        @enderror
                    @endforeach
                </div>

                <button type="button" class="email-add-btn" id="addEmailBtn">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Agregar otro correo
                </button>

                <div class="excel-field-hint">
                    Si no agregas ningún correo, se enviará a la lista de notificación por defecto del equipo.
                </div>

                @error('emails')
                    <div class="excel-error show">{{ $message }}</div>
                @enderror

                <hr class="section-divider">

                <div class="section-title">Periodo a consultar</div>

                <div class="mode-tabs">
                    <div class="mode-tab active" data-mode="month">Mes completo</div>
                    <div class="mode-tab" data-mode="range">Rango manual</div>
                </div>

                <div class="mode-panel active" id="monthPanel">
                    <div class="excel-field-group">
                        <label for="monthInput" class="excel-field-label">Mes</label>
                        <input type="month" name="month" id="monthInput" class="excel-field-input"
                            value="{{ old('month') }}">
                        <div class="excel-field-hint">
                            Si lo dejas en blanco, se generará el reporte del mes actual.
                        </div>
                    </div>
                </div>

                <div class="mode-panel" id="rangePanel">
                    <div class="date-range-group">
                        <div class="excel-field-group">
                            <label for="createdStart" class="excel-field-label">Fecha inicio</label>
                            <input type="datetime-local" name="created_start" id="createdStart"
                                class="excel-field-input" value="{{ old('created_start') }}">
                        </div>
                    </div>
                    <div class="date-range-group">
                        <div class="excel-field-group">
                            <label for="createdEnd" class="excel-field-label">Fecha fin</label>
                            <input type="datetime-local" name="created_end" id="createdEnd"
                                class="excel-field-input" value="{{ old('created_end') }}">
                        </div>
                    </div>
                    <div class="excel-field-hint">
                        Ambas fechas son obligatorias si usas el rango manual.
                    </div>
                </div>

                @error('month')
                    <div class="excel-error show">{{ $message }}</div>
                @enderror
                @error('created_start')
                    <div class="excel-error show">{{ $message }}</div>
                @enderror
                @error('created_end')
                    <div class="excel-error show">{{ $message }}</div>
                @enderror

                <div class="excel-error" id="formError"></div>

                <button type="submit" class="excel-submit-btn" id="submitBtn">
                    Generar reporte
                </button>
            </form>

        </div>

        <a href="{{ route('home') }}" class="back-link">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            Volver a acciones disponibles
        </a>
    </div>

</body>

<script>
    (function () {
        const emailsContainer = document.getElementById('emailsContainer');
        const addEmailBtn = document.getElementById('addEmailBtn');
        const form = document.getElementById('invoiceReportForm');
        const formError = document.getElementById('formError');

        const modeTabs = document.querySelectorAll('.mode-tab');
        const monthPanel = document.getElementById('monthPanel');
        const rangePanel = document.getElementById('rangePanel');
        const monthInput = document.getElementById('monthInput');
        const createdStart = document.getElementById('createdStart');
        const createdEnd = document.getElementById('createdEnd');

        // ---- Emails dinámicos ----
        function buildEmailRow() {
            const row = document.createElement('div');
            row.className = 'email-row';
            row.innerHTML = `
                <input type="email" name="emails[]" class="excel-field-input email-input" placeholder="nombre@correo.com">
                <button type="button" class="email-remove-btn" title="Quitar correo">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            `;
            return row;
        }

        function updateRemoveButtonsState() {
            const rows = emailsContainer.querySelectorAll('.email-row');
            const removeButtons = emailsContainer.querySelectorAll('.email-remove-btn');
            removeButtons.forEach(btn => {
                btn.disabled = rows.length <= 1;
            });
        }

        addEmailBtn.addEventListener('click', () => {
            emailsContainer.appendChild(buildEmailRow());
            updateRemoveButtonsState();
        });

        emailsContainer.addEventListener('click', (e) => {
            const btn = e.target.closest('.email-remove-btn');
            if (!btn || btn.disabled) return;

            btn.closest('.email-row').remove();
            updateRemoveButtonsState();
        });

        // ---- Tabs: mes vs rango manual ----
        function setMode(mode) {
            modeTabs.forEach(tab => tab.classList.toggle('active', tab.dataset.mode === mode));

            if (mode === 'month') {
                monthPanel.classList.add('active');
                rangePanel.classList.remove('active');
                createdStart.value = '';
                createdEnd.value = '';
                createdStart.disabled = true;
                createdEnd.disabled = true;
                monthInput.disabled = false;
            } else {
                rangePanel.classList.add('active');
                monthPanel.classList.remove('active');
                monthInput.value = '';
                monthInput.disabled = true;
                createdStart.disabled = false;
                createdEnd.disabled = false;
            }
        }

        modeTabs.forEach(tab => {
            tab.addEventListener('click', () => setMode(tab.dataset.mode));
        });

        // Si el usuario vuelve de una validación fallida con datos de rango, deja esa pestaña activa
        if (createdStart.value || createdEnd.value) {
            setMode('range');
        } else {
            setMode('month');
        }

        // ---- Validación antes de enviar ----
        form.addEventListener('submit', (e) => {
            formError.classList.remove('show');

            const usingRange = rangePanel.classList.contains('active');

            if (usingRange) {
                const hasStart = createdStart.value.trim() !== '';
                const hasEnd = createdEnd.value.trim() !== '';

                if (!hasStart || !hasEnd) {
                    e.preventDefault();
                    formError.textContent = 'Debes diligenciar la fecha de inicio y la fecha fin del rango manual.';
                    formError.classList.add('show');
                    return;
                }

                if (new Date(createdStart.value) > new Date(createdEnd.value)) {
                    e.preventDefault();
                    formError.textContent = 'La fecha de inicio no puede ser mayor a la fecha fin.';
                    formError.classList.add('show');
                }
            }
        });

        updateRemoveButtonsState();
    })();
</script>

</html>
