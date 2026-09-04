<!-- resources/views/integration/photo_product.blade.php -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Fotos de producto</title>
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

        .excel-field-hint {
            font-size: 0.75rem;
            color: #9ca3af;
            margin-top: 0.3rem;
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

        .excel-submit-btn:hover { background: #15803d; }
        .excel-submit-btn:active { transform: scale(0.98); }
        .excel-submit-btn:disabled { background: #d1d5db; cursor: not-allowed; }

        .excel-submit-btn .spinner {
            display: none;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.5);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }

        .excel-submit-btn.is-loading .spinner { display: inline-block; }

        @keyframes spin { to { transform: rotate(360deg); } }

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

        .excel-error.show { display: block; }

        .excel-success {
            display: none;
            margin-top: 0.75rem;
            font-size: 0.8rem;
            color: #166534;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
        }

        .excel-success.show { display: block; }

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

        .back-link:hover { text-decoration: underline; }
        .back-link svg { width: 15px; height: 15px; }

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

        .excel-download-btn:hover { background: #15803d; }
        .excel-download-btn:active { transform: scale(0.98); }
        .excel-download-btn:disabled { background: #d1d5db; cursor: not-allowed; }

        /* --- Específico de esta vista --- */
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

        .photo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .photo-item {
            position: relative;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #eef0f2;
            aspect-ratio: 1 / 1;
            background: #f9fafb;
        }

        .photo-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .photo-item .photo-delete-btn {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: none;
            background: rgba(220, 38, 38, 0.9);
            color: #fff;
            font-size: 0.9rem;
            line-height: 1;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .photo-item .photo-delete-btn:hover {
            background: #b91c1c;
        }

        .photo-empty-hint {
            font-size: 0.8rem;
            color: #9ca3af;
            margin-top: 0.5rem;
        }

        .preview-list {
            margin-top: 1rem;
        }

        .preview-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.6rem 0.8rem;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 10px;
            margin-bottom: 0.5rem;
        }

        .preview-item img {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 6px;
            flex-shrink: 0;
        }

        .preview-item-details {
            flex: 1;
            min-width: 0;
        }

        .preview-item-name {
            font-size: 0.82rem;
            font-weight: 600;
            color: #1f2937;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .preview-item-size {
            font-size: 0.72rem;
            color: #6b7280;
        }

        .preview-item-remove {
            background: none;
            border: none;
            cursor: pointer;
            color: #ef4444;
            font-size: 1.1rem;
            line-height: 1;
            padding: 0.25rem;
            border-radius: 6px;
        }

        .preview-item-remove:hover { background: #fee2e2; }

        .section-label {
            font-size: 0.78rem;
            font-weight: 700;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            margin: 1.25rem 0 0.5rem;
        }

        .section-label:first-child { margin-top: 0; }
    </style>
</head>

<body>

    <div class="excel-upload-wrapper">

        <div class="excel-upload-card">
            <div class="excel-upload-title">Fotos de producto</div>
            <div class="excel-upload-subtitle">
                Busca una referencia para ver, subir o eliminar sus fotos.
            </div>

            <div class="excel-field-group">
                <label for="referenciaInput" class="excel-field-label">
                    Referencia <span class="required-mark">*</span>
                </label>
                <div class="search-row">
                    <input type="text" id="referenciaInput" class="excel-field-input"
                        placeholder="Ej: VAMPELT">
                    <button type="button" id="searchBtn" class="excel-download-btn search-btn">
                        Buscar
                    </button>
                </div>
                <div class="excel-field-hint">Ingresa la referencia del producto para gestionar sus fotos.</div>
            </div>

            <div class="excel-error" id="searchError"></div>

            <!-- Fotos existentes -->
            <div id="existingSection" style="display: none;">
                <div class="section-label">Fotos existentes</div>
                <div class="photo-grid" id="existingGrid"></div>
                <div class="photo-empty-hint" id="existingEmptyHint" style="display: none;">
                    No hay fotos cargadas para esta referencia todavía.
                </div>
            </div>

            <!-- Subida de nuevas fotos -->
            <div id="uploadSection" style="display: none;">
                <div class="section-label">Subir nuevas fotos</div>

                <div class="excel-dropzone" id="photosDropzone">
                    <div class="excel-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                            <polyline points="17 8 12 3 7 8" />
                            <line x1="12" y1="3" x2="12" y2="15" />
                        </svg>
                    </div>
                    <div class="excel-dropzone-text">
                        Arrastra tus fotos aquí o <span>selecciónalas</span>
                    </div>
                    <div class="excel-dropzone-hint">Puedes seleccionar varias a la vez — .jpg, .jpeg, .png, .webp (máx. 5 MB c/u)</div>

                    <input type="file" id="photosInput" class="excel-input" accept=".jpg,.jpeg,.png,.webp" multiple>
                </div>

                <div class="preview-list" id="previewList"></div>

                <div class="excel-error" id="uploadError"></div>
                <div class="excel-success" id="uploadSuccess"></div>

                <button type="button" class="excel-submit-btn" id="uploadBtn" disabled>
                    <span class="spinner"></span>
                    <span class="excel-submit-btn-text">Subir fotos</span>
                </button>
            </div>
        </div>

        <a href="{{ route('home') }}" class="back-link">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            Volver a acciones disponibles
        </a>
    </div>

</body>

<script>
    (function () {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        const referenciaInput = document.getElementById('referenciaInput');
        const searchBtn = document.getElementById('searchBtn');
        const searchError = document.getElementById('searchError');

        const existingSection = document.getElementById('existingSection');
        const existingGrid = document.getElementById('existingGrid');
        const existingEmptyHint = document.getElementById('existingEmptyHint');

        const uploadSection = document.getElementById('uploadSection');
        const photosDropzone = document.getElementById('photosDropzone');
        const photosInput = document.getElementById('photosInput');
        const previewList = document.getElementById('previewList');
        const uploadError = document.getElementById('uploadError');
        const uploadSuccess = document.getElementById('uploadSuccess');
        const uploadBtn = document.getElementById('uploadBtn');
        const uploadBtnText = uploadBtn.querySelector('.excel-submit-btn-text');

        const allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
        const maxSizeMB = 5;

        let currentReferencia = null;
        let selectedFiles = [];

        // --- Buscar referencia ---
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
            existingSection.style.display = 'none';
            uploadSection.style.display = 'none';
            resetUploadState();

            if (!referencia) {
                showError(searchError, 'Ingresa una referencia para buscar');
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

                if (!res.ok) throw new Error('request_failed');

                const data = await res.json();
                currentReferencia = data.referencia;

                renderExisting(data.photos);
                existingSection.style.display = 'block';
                uploadSection.style.display = 'block';
            } catch (err) {
                showError(searchError, 'No se pudo buscar la referencia. Intenta de nuevo.');
            } finally {
                searchBtn.disabled = false;
                searchBtn.textContent = 'Buscar';
            }
        }

        function renderExisting(photos) {
            existingGrid.innerHTML = '';

            if (!photos.length) {
                existingEmptyHint.style.display = 'block';
                return;
            }

            existingEmptyHint.style.display = 'none';

            photos.forEach((photo) => {
                const item = document.createElement('div');
                item.className = 'photo-item';
                item.innerHTML = `
                    <img src="${photo.url}" alt="${photo.name}">
                    <button type="button" class="photo-delete-btn" title="Eliminar" data-filename="${photo.name}">&times;</button>
                `;
                item.querySelector('.photo-delete-btn').addEventListener('click', () => eliminarFoto(photo.name, item));
                existingGrid.appendChild(item);
            });
        }

        async function eliminarFoto(filename, itemEl) {
            if (!confirm('¿Eliminar esta foto?')) return;

            try {
                const res = await fetch("{{ route('siigo.product_photo_delete') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ referencia: currentReferencia, filename }),
                });

                const data = await res.json();

                if (!res.ok || !data.success) throw new Error('delete_failed');

                itemEl.remove();

                if (!existingGrid.children.length) {
                    existingEmptyHint.style.display = 'block';
                }
            } catch (err) {
                alert('No se pudo eliminar la foto. Intenta de nuevo.');
            }
        }

        // --- Subida de nuevas fotos ---
        photosDropzone.addEventListener('click', () => photosInput.click());

        ['dragover', 'dragenter'].forEach(evt => {
            photosDropzone.addEventListener(evt, (e) => {
                e.preventDefault();
                photosDropzone.classList.add('dragover');
            });
        });

        ['dragleave', 'dragend'].forEach(evt => {
            photosDropzone.addEventListener(evt, () => photosDropzone.classList.remove('dragover'));
        });

        photosDropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            photosDropzone.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                addFiles(Array.from(e.dataTransfer.files));
            }
        });

        photosInput.addEventListener('change', () => {
            if (photosInput.files.length) {
                addFiles(Array.from(photosInput.files));
                photosInput.value = '';
            }
        });

        function addFiles(files) {
            uploadError.classList.remove('show');
            uploadSuccess.classList.remove('show');

            for (const file of files) {
                const ext = file.name.split('.').pop().toLowerCase();

                if (!allowedExt.includes(ext)) {
                    showError(uploadError, `"${file.name}" no es un formato permitido`);
                    continue;
                }

                if (file.size / (1024 * 1024) > maxSizeMB) {
                    showError(uploadError, `"${file.name}" supera el tamaño máximo de ${maxSizeMB} MB`);
                    continue;
                }

                selectedFiles.push(file);
            }

            renderPreview();
        }

        function renderPreview() {
            previewList.innerHTML = '';

            selectedFiles.forEach((file, index) => {
                const reader = new FileReader();
                const item = document.createElement('div');
                item.className = 'preview-item';
                item.innerHTML = `
                    <img src="" alt="${file.name}">
                    <div class="preview-item-details">
                        <div class="preview-item-name">${file.name}</div>
                        <div class="preview-item-size">${formatSize(file.size)}</div>
                    </div>
                    <button type="button" class="preview-item-remove" data-index="${index}">&times;</button>
                `;
                reader.onload = (e) => { item.querySelector('img').src = e.target.result; };
                reader.readAsDataURL(file);

                item.querySelector('.preview-item-remove').addEventListener('click', () => {
                    selectedFiles.splice(index, 1);
                    renderPreview();
                });

                previewList.appendChild(item);
            });

            uploadBtn.disabled = selectedFiles.length === 0;
        }

        function resetUploadState() {
            selectedFiles = [];
            previewList.innerHTML = '';
            uploadBtn.disabled = true;
            uploadError.classList.remove('show');
            uploadSuccess.classList.remove('show');
        }

        uploadBtn.addEventListener('click', async () => {
            if (!currentReferencia || !selectedFiles.length) return;

            uploadError.classList.remove('show');
            uploadSuccess.classList.remove('show');
            uploadBtn.disabled = true;
            uploadBtn.classList.add('is-loading');
            uploadBtnText.textContent = 'Subiendo...';

            const formData = new FormData();
            formData.append('referencia', currentReferencia);
            selectedFiles.forEach((file) => formData.append('photos[]', file));

            try {
                const res = await fetch("{{ route('siigo.product_photo_upload') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                const data = await res.json();

                if (!res.ok || !data.success) throw new Error('upload_failed');

                data.uploaded.forEach((photo) => {
                    existingEmptyHint.style.display = 'none';
                    const item = document.createElement('div');
                    item.className = 'photo-item';
                    item.innerHTML = `
                        <img src="${photo.url}" alt="${photo.name}">
                        <button type="button" class="photo-delete-btn" title="Eliminar" data-filename="${photo.name}">&times;</button>
                    `;
                    item.querySelector('.photo-delete-btn').addEventListener('click', () => eliminarFoto(photo.name, item));
                    existingGrid.appendChild(item);
                });

                showSuccess(uploadSuccess, `${data.uploaded.length} foto(s) subida(s) correctamente.`);
                resetUploadState();
            } catch (err) {
                showError(uploadError, 'No se pudieron subir las fotos. Intenta de nuevo.');
            } finally {
                uploadBtn.classList.remove('is-loading');
                uploadBtnText.textContent = 'Subir fotos';
            }
        });

        function showError(el, msg) {
            el.textContent = msg;
            el.classList.add('show');
        }

        function showSuccess(el, msg) {
            el.textContent = msg;
            el.classList.add('show');
        }

        function formatSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        }
    })();
</script>

</html>
