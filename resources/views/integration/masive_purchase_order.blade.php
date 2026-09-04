<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Orden de compra masivos</title>
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
            margin-bottom: 1.5rem;
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

        .excel-field-input {
            width: 100%;
            padding: 0.65rem 0.85rem;
            font-size: 0.88rem;
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

        .excel-field-input:invalid:not(:placeholder-shown) {
            border-color: #fca5a5;
        }

        .excel-field-hint {
            font-size: 0.75rem;
            color: #9ca3af;
            margin-top: 0.3rem;
        }

        .excel-field-select {
            width: 100%;
            padding: 0.65rem 0.85rem;
            font-size: 0.88rem;
            color: #1f2937;
            background: #f9fafb;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            transition: border-color 0.2s ease, background 0.2s ease;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.85rem center;
            background-size: 16px;
            cursor: pointer;
        }

        .excel-field-select:focus {
            outline: none;
            border-color: #16a34a;
            background-color: #ffffff;
        }

        .excel-dropzone {
            position: relative;
            border: 2px dashed #cbd5e1;
            border-radius: 14px;
            padding: 2.25rem 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.25s ease;
            background: #f9fafb;
        }

        .excel-dropzone:hover {
            border-color: #16a34a;
            background: #f0fdf4;
        }

        .excel-dropzone.dragover {
            border-color: #16a34a;
            background: #ecfdf5;
            transform: scale(1.01);
        }

        .excel-dropzone.has-file {
            border-color: #16a34a;
            border-style: solid;
            background: #f0fdf4;
        }

        .excel-dropzone.is-disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }

        .excel-icon {
            width: 56px;
            height: 56px;
            margin: 0 auto 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #dcfce7;
            border-radius: 50%;
            transition: background 0.25s ease;
        }

        .excel-icon svg {
            width: 28px;
            height: 28px;
            stroke: #16a34a;
        }

        .excel-dropzone-text {
            font-size: 0.95rem;
            color: #374151;
            font-weight: 500;
            margin-bottom: 0.15rem;
        }

        .excel-dropzone-text span {
            color: #16a34a;
            text-decoration: underline;
        }

        .excel-dropzone-hint {
            font-size: 0.78rem;
            color: #9ca3af;
        }

        .excel-input {
            display: none;
        }

        .excel-file-info {
            display: none;
            align-items: center;
            gap: 0.75rem;
            margin-top: 1rem;
            padding: 0.75rem 1rem;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 10px;
            animation: fadeIn 0.25s ease;
        }

        .excel-file-info.show {
            display: flex;
        }

        .excel-file-icon {
            width: 34px;
            height: 34px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #16a34a;
            border-radius: 8px;
        }

        .excel-file-icon svg {
            width: 18px;
            height: 18px;
            stroke: #ffffff;
        }

        .excel-file-details {
            flex: 1;
            min-width: 0;
        }

        .excel-file-name {
            font-size: 0.85rem;
            font-weight: 600;
            color: #1f2937;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .excel-file-size {
            font-size: 0.75rem;
            color: #6b7280;
        }

        .excel-remove-btn {
            background: none;
            border: none;
            cursor: pointer;
            color: #ef4444;
            font-size: 1.1rem;
            line-height: 1;
            padding: 0.25rem;
            border-radius: 6px;
            transition: background 0.2s ease;
        }

        .excel-remove-btn:hover {
            background: #fee2e2;
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
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .excel-submit-btn:hover {
            background: #15803d;
        }

        .excel-submit-btn:active {
            transform: scale(0.98);
        }

        .excel-submit-btn:disabled {
            background: #d1d5db;
            cursor: not-allowed;
        }

        .excel-submit-btn .spinner {
            display: none;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.5);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }

        .excel-submit-btn.is-loading .spinner {
            display: inline-block;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
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

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-4px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.85rem;
            font-weight: 600;
            color: #4f46e5;
            text-decoration: none;
            margin-top: 0.25rem;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .back-link svg {
            width: 15px;
            height: 15px;
        }

        .excel-download-btn {
            width: 100%;
            margin-top: 0.25rem;
            padding: 0.75rem;
            background: #16a34a;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s ease, transform 0.1s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .excel-download-btn:hover {
            background: #15803d;
        }

        .excel-download-btn:active {
            transform: scale(0.98);
        }

        .excel-download-btn:disabled {
            background: #d1d5db;
            cursor: not-allowed;
        }

        /* --- Buscador de fotos por referencia --- */
        .search-row {
            display: flex;
            gap: 0.5rem;
        }

        .search-btn {
            margin-top: 0;
            width: auto;
            padding: 0 1.2rem;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .photo-status {
            display: none;
            flex-direction: column;
            align-items: flex-start;
            gap: 0.6rem;
            margin-top: 0.75rem;
            padding: 0.6rem 0.8rem;
            border-radius: 10px;
            font-size: 0.82rem;
            font-weight: 500;
        }

        .photo-status.show {
            display: flex;
        }

        .photo-status.status-ok {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
        }

        .photo-status.status-warn {
            background: #fffbeb;
            border: 1px solid #fde68a;
            color: #92400e;
        }

        .photo-status-thumbs {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            width: 100%;
        }

        .photo-status-thumbs img {
            width: 150px;
            height: 150px;
            max-width: 100%;
            object-fit: contain;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
        }
    </style>
</head>

<body>

    <div class="excel-upload-wrapper">

        <!-- Formulario 1: descargar el formato según el tipo de orden de compra -->
        <div class="excel-upload-card">
            <div class="excel-upload-title">Descargar formato</div>
            <div class="excel-upload-subtitle">
                Selecciona el tipo de orden de compra y descarga la plantilla Excel correspondiente
            </div>

            <form action="{{ route('siigo.masive_purchase_order_format') }}" method="POST" id="downloadTypeForm">
                @csrf
                <div class="excel-field-group" style="margin-bottom: 0;">
                    <label for="downloadPurchaseOrderType" class="excel-field-label">
                        Tipo de orden de compra <span class="required-mark">*</span>
                    </label>
                    <select name="purchase_order_type_id" id="downloadPurchaseOrderType" class="excel-field-select" required>
                        <option value="" disabled selected>Selecciona un tipo</option>
                        @foreach ($purchase_order_types as $code => $label)
                            <option value="{{ $code }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="excel-field-group">
                    <label for="downloadCookie" class="excel-field-label">
                        Cookie <span class="required-mark">*</span>
                    </label>
                    <input type="text" name="cookie" id="downloadCookie" class="excel-field-input"
                        placeholder="Pega aquí el valor de la cookie" required>
                </div>

                <button type="submit" class="excel-download-btn" id="downloadBtn" disabled>
                    Descargar formato
                </button>
            </form>
        </div>

        <!-- Formulario 2: cargar el archivo ya diligenciado -->
        <div class="excel-upload-card">
            <div class="excel-upload-title">Cargar archivo de orden de compra</div>
            <div class="excel-upload-subtitle">
                Sube la plantilla Excel (.xlsx o .xls) ya diligenciada para procesar la orden de compra
            </div>

            <form action="{{ route('siigo.masive_purchase_order_upload') }}" method="POST" enctype="multipart/form-data"
                id="excelUploadForm">
                @csrf

                <div class="excel-field-group">
                    <label for="notifyEmail" class="excel-field-label">
                        Correo de notificación <span class="required-mark">*</span>
                    </label>
                    <input type="email" name="email" id="notifyEmail" class="excel-field-input"
                        placeholder="nombre@correo.com" value="{{ old('email') }}" required>
                    <div class="excel-field-hint">
                        Te enviaremos el resultado a este correo para que lo consultes cuando quieras.
                    </div>
                    @error('email')
                        <div class="excel-error show">{{ $message }}</div>
                    @enderror
                </div>

                <div class="excel-field-group">
                    <label for="referenciaInput" class="excel-field-label">
                        Referencia <span class="required-mark">*</span>
                    </label>
                    <div class="search-row">
                        <input type="text" id="referenciaInput" name="referencia" class="excel-field-input"
                            placeholder="Ej: VAMPELT" required>
                        <button type="button" id="searchBtn" class="excel-download-btn search-btn">
                            Buscar
                        </button>
                    </div>
                    <div class="excel-field-hint">
                        Consulta si ya existe una foto cargada para esta referencia.
                    </div>

                    <div class="photo-status" id="photoStatusOk">
                        <div class="photo-status-thumbs" id="photoStatusThumbs"></div>
                        <span id="photoStatusOkText"></span>
                    </div>
                    <div class="photo-status status-warn" id="photoStatusWarn">
                        No se encontraron fotos para esta referencia.
                    </div>
                    <div class="excel-error" id="searchError"></div>
                </div>

                <div class="excel-dropzone" id="excelDropzone">
                    <div class="excel-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                            <polyline points="17 8 12 3 7 8" />
                            <line x1="12" y1="3" x2="12" y2="15" />
                        </svg>
                    </div>
                    <div class="excel-dropzone-text">
                        Arrastra tu archivo aquí o <span>selecciónalo</span>
                    </div>
                    <div class="excel-dropzone-hint">Formatos permitidos: .xlsx, .xls (máx. 10 MB)</div>

                    <input type="file" name="file" id="excelInput" class="excel-input" accept=".xlsx,.xls">
                </div>

                <div class="excel-file-info" id="excelFileInfo">
                    <div class="excel-file-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                            <polyline points="14 2 14 8 20 8" />
                        </svg>
                    </div>
                    <div class="excel-file-details">
                        <div class="excel-file-name" id="excelFileName"></div>
                        <div class="excel-file-size" id="excelFileSize"></div>
                    </div>
                    <button type="button" class="excel-remove-btn" id="excelRemoveBtn">&times;</button>
                </div>

                <div class="excel-error" id="excelError"></div>

                @error('file')
                    <div class="excel-error show">{{ $message }}</div>
                @enderror

                <button type="submit" class="excel-submit-btn" id="excelSubmitBtn" disabled>
                    <span class="spinner"></span>
                    <span class="excel-submit-btn-text">Realizar orden de compra</span>
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
    (function() {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        // --- Buscador de referencia/foto (solo informativo) ---
        const referenciaInput = document.getElementById('referenciaInput');
        const searchBtn = document.getElementById('searchBtn');
        const searchError = document.getElementById('searchError');
        const photoStatusOk = document.getElementById('photoStatusOk');
        const photoStatusOkText = document.getElementById('photoStatusOkText');
        const photoStatusThumbs = document.getElementById('photoStatusThumbs');
        const photoStatusWarn = document.getElementById('photoStatusWarn');

        searchBtn.addEventListener('click', () => buscarReferencia());
        referenciaInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                buscarReferencia();
            }
        });

        async function buscarReferencia() {
            const referencia = referenciaInput.value.trim();
            searchError.classList.remove('show');
            photoStatusOk.classList.remove('show');
            photoStatusWarn.classList.remove('show');

            if (!referencia) {
                showFieldError(searchError, 'Ingresa una referencia para buscar');
                return;
            }

            searchBtn.disabled = true;
            searchBtn.textContent = 'Buscando...';

            try {
                const res = await fetch("{{ route('siigo.product_photo_search') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ referencia }),
                });
                console.log(res);
                if (!res.ok) throw new Error('request_failed');

                const data = await res.json();

                if (data.exists && data.photos.length) {
                    photoStatusThumbs.innerHTML = '';
                    data.photos.slice(0, 4).forEach((photo) => {
                        const img = document.createElement('img');
                        img.src = photo.url;
                        img.alt = photo.name;
                        photoStatusThumbs.appendChild(img);
                    });
                    photoStatusOkText.textContent = `${data.photos.length} foto(s) encontrada(s) para esta referencia.`;
                    photoStatusOk.classList.add('show');
                } else {
                    photoStatusWarn.classList.add('show');
                }
            } catch (err) {
                showFieldError(searchError, 'No se pudo buscar la referencia. Intenta de nuevo.');
            } finally {
                searchBtn.disabled = false;
                searchBtn.textContent = 'Buscar';
            }
        }

        function showFieldError(el, msg) {
            el.textContent = msg;
            el.classList.add('show');
        }
        // --- Formulario 1: habilitar el botón de descarga solo cuando hay tipo seleccionado ---
        const downloadType = document.getElementById('downloadPurchaseOrderType');
        const downloadBtn = document.getElementById('downloadBtn');

        downloadType.addEventListener('change', () => {
            downloadBtn.disabled = !downloadType.value;
        });

        // --- Formulario 2: carga del archivo ---
        const form = document.getElementById('excelUploadForm');
        const dropzone = document.getElementById('excelDropzone');
        const input = document.getElementById('excelInput');
        const fileInfo = document.getElementById('excelFileInfo');
        const fileName = document.getElementById('excelFileName');
        const fileSize = document.getElementById('excelFileSize');
        const removeBtn = document.getElementById('excelRemoveBtn');
        const submitBtn = document.getElementById('excelSubmitBtn');
        const submitBtnText = submitBtn.querySelector('.excel-submit-btn-text');
        const errorBox = document.getElementById('excelError');

        const allowedExt = ['xlsx', 'xls'];
        const maxSizeMB = 10;

        dropzone.addEventListener('click', () => input.click());

        ['dragover', 'dragenter'].forEach(evt => {
            dropzone.addEventListener(evt, (e) => {
                e.preventDefault();
                dropzone.classList.add('dragover');
            });
        });

        ['dragleave', 'dragend'].forEach(evt => {
            dropzone.addEventListener(evt, () => dropzone.classList.remove('dragover'));
        });

        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                input.files = e.dataTransfer.files;
                handleFile(input.files[0]);
            }
        });

        input.addEventListener('change', () => {
            if (input.files.length) handleFile(input.files[0]);
        });

        removeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            resetInput();
        });

        function handleFile(file) {
            const ext = file.name.split('.').pop().toLowerCase();
            errorBox.classList.remove('show');

            if (!allowedExt.includes(ext)) {
                showError('Solo se permiten archivos .xlsx o .xls');
                resetInput();
                return;
            }

            if (file.size / (1024 * 1024) > maxSizeMB) {
                showError('El archivo supera el tamaño máximo de ' + maxSizeMB + ' MB');
                resetInput();
                return;
            }

            fileName.textContent = file.name;
            fileSize.textContent = formatSize(file.size);
            fileInfo.classList.add('show');
            dropzone.classList.add('has-file');
            submitBtn.disabled = false;
        }

        function resetInput() {
            input.value = '';
            fileInfo.classList.remove('show');
            dropzone.classList.remove('has-file');
            submitBtn.disabled = true;
        }

        function showError(msg) {
            errorBox.textContent = msg;
            errorBox.classList.add('show');
        }

        function formatSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        }

        // --- Evitar doble envío: deshabilitar el boton apenas se hace submit ---
        form.addEventListener('submit', function (e) {
            // Si el boton ya esta deshabilitado (segundo intento de submit), bloquear.
            if (submitBtn.disabled) {
                e.preventDefault();
                return;
            }
            submitBtn.disabled = true;
            submitBtn.classList.add('is-loading');
            submitBtnText.textContent = 'Enviando...';
        });
    })();
</script>

</html>
