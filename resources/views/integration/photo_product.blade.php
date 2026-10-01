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
            max-width: 90%;
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

        .mode-tabs { display: flex; gap: 0.5rem; margin-bottom: 1.25rem; padding: 4px; background: #f3f4f6; border-radius: 10px; }
        .mode-tab { flex: 1; padding: 0.65rem 0.5rem; border: none; border-radius: 7px; background: transparent; color: #6b7280; font: inherit; font-size: 0.85rem; font-weight: 600; cursor: pointer; }
        .mode-tab.active { background: #fff; color: #15803d; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .bulk-note { margin: 0.75rem 0; padding: 0.75rem; border: 1px solid #bfdbfe; border-radius: 8px; background: #eff6ff; color: #1d4ed8; font-size: 0.78rem; line-height: 1.5; }
        .zip-dropzone { position: relative; border: 2px dashed #cbd5e1; border-radius: 14px; padding: 2.25rem 1.5rem; text-align: center; cursor: pointer; background: #f9fafb; transition: all 0.25s ease; }
        .zip-dropzone:hover, .zip-dropzone.dragover { border-color: #16a34a; background: #f0fdf4; }
        .zip-dropzone.has-file { border-color: #16a34a; border-style: solid; background: #f0fdf4; }
        .zip-input { display: none; }
        .zip-summary { display: none; margin-top: 1rem; padding: 0.85rem; border: 1px solid #e5e7eb; border-radius: 10px; background: #fff; font-size: 0.82rem; color: #374151; }
        .zip-summary.show { display: block; }
        .zip-summary ul { margin: 0.5rem 0 0; padding-left: 1.25rem; }
        .zip-summary li { margin: 0.25rem 0; overflow-wrap: anywhere; }
        .zip-action-btn { width: 100%; margin-top: 1rem; padding: 0.75rem; border: none; border-radius: 10px; background: #d1d5db; color: #fff; font-size: 0.9rem; font-weight: 600; cursor: not-allowed; }
        .zip-action-btn:enabled { background: #16a34a; cursor: pointer; }
        .zip-action-btn:enabled:hover { background: #15803d; }
        .zip-status { display: none; margin-top: 0.75rem; padding: 0.65rem 0.75rem; border-radius: 8px; font-size: 0.8rem; line-height: 1.5; }
        .zip-status.show { display: block; }
        .zip-status.error { color: #dc2626; background: #fef2f2; border: 1px solid #fecaca; }
        .zip-status.success { color: #166534; background: #f0fdf4; border: 1px solid #bbf7d0; }

        /* --- Explorador de carpetas --- */
        .explorer-breadcrumb { display: flex; flex-wrap: wrap; align-items: center; gap: 0.15rem; margin: 0.25rem 0 1rem; font-size: 0.82rem; }
        .crumb-btn { background: none; border: none; padding: 0.2rem 0.45rem; border-radius: 6px; color: #4f46e5; font: inherit; font-weight: 600; cursor: pointer; }
        .crumb-btn:hover { background: #eef2ff; }
        .crumb-btn.current { color: #1f2937; cursor: default; }
        .crumb-btn.current:hover { background: none; }
        .crumb-sep { color: #9ca3af; }

        .explorer-toolbar { display: flex; gap: 0.5rem; margin-bottom: 0.75rem; }
        .explorer-toolbar .excel-field-input { flex: 1; min-width: 0; }
        .explorer-btn { padding: 0.65rem 1rem; border: 1px solid #16a34a; border-radius: 10px; background: #fff; color: #15803d; font: inherit; font-size: 0.85rem; font-weight: 600; cursor: pointer; white-space: nowrap; }
        .explorer-btn:hover { background: #f0fdf4; }

        .folder-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 0.75rem; }
        .folder-item { display: flex; flex-direction: column; align-items: center; gap: 0.35rem; padding: 0.9rem 0.6rem; border: 1px solid #eef0f2; border-radius: 12px; background: #f9fafb; cursor: pointer; text-align: center; font: inherit; transition: border-color 0.2s ease, background 0.2s ease; }
        .folder-item:hover { border-color: #16a34a; background: #f0fdf4; }
        .folder-icon { width: 36px; height: 36px; stroke: #16a34a; }
        .folder-swatch { width: 36px; height: 36px; border-radius: 50%; border: 1px solid rgba(0, 0, 0, 0.15); flex-shrink: 0; }
        .folder-name { font-size: 0.8rem; font-weight: 600; color: #1f2937; overflow-wrap: anywhere; }
        .folder-meta { font-size: 0.7rem; color: #9ca3af; }

        .explorer-dropzone { padding: 1.25rem 1rem; margin-top: 1rem; }
    </style>
</head>

<body>

    <div class="excel-upload-wrapper">

        <div class="excel-upload-card">
            <div class="excel-upload-title">Fotos de producto</div>
            <div class="excel-upload-subtitle">
                Busca una referencia para ver, subir o eliminar sus fotos.
            </div>

            <div class="mode-tabs" role="tablist" aria-label="Modo de gestión de fotos">
                <button type="button" class="mode-tab active" id="individualTab" role="tab" aria-selected="true">Por referencia</button>
                <button type="button" class="mode-tab" id="bulkTab" role="tab" aria-selected="false">Carga masiva ZIP</button>
                <button type="button" class="mode-tab" id="explorerTab" role="tab" aria-selected="false">Explorador</button>
            </div>

            <div id="individualMode">

            <div class="excel-field-group">
                <label for="tokenInput" class="excel-field-label">
                    Token <span class="required-mark">*</span>
                </label>
                <div class="search-row">
                    <input type="text" id="tokenInput" class="excel-field-input">
                </div>
                <div class="excel-field-hint">Necesario para validar tus permisos.</div>
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

            <div id="bulkMode" style="display: none;">
                <div class="excel-upload-subtitle">Carga fotografías de muchas referencias usando un archivo ZIP con una carpeta por referencia.</div>
                <div class="bulk-note"><strong>Carga masiva:</strong> primero se valida la estructura del ZIP en el navegador. Después se enviará el ZIP y tu token al servidor, que validará tu usuario antes de guardar las imágenes.</div>

                <div class="excel-field-group">
                    <label for="zipModeSelect" class="excel-field-label">Tipo de ZIP <span class="required-mark">*</span></label>
                    <select id="zipModeSelect" class="excel-field-input">
                        <option value="folders">ZIP con carpetas por referencia</option>
                        <option value="filenames">ZIP con imágenes nombradas por referencia</option>
                    </select>
                    <div class="excel-field-hint" id="zipModeHint">Cada carpeta debe llamarse como la referencia y contener las imágenes de ese producto.</div>
                </div>

                <div class="excel-field-group">
                    <label for="bulkTokenInput" class="excel-field-label">
                        Token <span class="required-mark">*</span>
                    </label>
                    <div class="search-row">
                        <input type="text" id="bulkTokenInput" class="excel-field-input" autocomplete="off" placeholder="Ingresa tu token de Siigo">
                    </div>
                    <div class="excel-field-hint">Necesario para validar que tu usuario tenga permisos para realizar la carga masiva.</div>
                </div>

                <div class="excel-field-group">
                    <label for="zipInput" class="excel-field-label">Archivo comprimido <span class="required-mark">*</span></label>
                    <div class="zip-dropzone" id="zipDropzone">
                        <div class="excel-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="16" rx="2" />
                                <path d="M7 4v4M11 4v4M15 4v4M7 12h10M7 16h6" />
                            </svg>
                        </div>
                        <div class="excel-dropzone-text">Arrastra tu archivo ZIP aquí o <span>selecciónalo</span></div>
                        <div class="excel-dropzone-hint">Solo se acepta ZIP con carpetas de referencia; cada carpeta debe contener solo imágenes JPG, JPEG, PNG o WEBP.</div>
                        <input type="file" id="zipInput" class="zip-input" accept=".zip,application/zip">
                    </div>
                    <div class="excel-field-hint">Estructura requerida: CUÑIS/frontal.jpg, CUÑIS/lateral.png, SANDALIA45/foto1.webp. Las carpetas deben estar en MAYÚSCULAS; se permite Ñ. Se aceptan carpetas vacías. Sin subcarpetas ni archivos en la raíz.</div>
                </div>

                <div class="zip-summary" id="zipSummary"></div>
                <div class="zip-status" id="zipStatus"></div>
                <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:12px;">
                    <button type="button" class="zip-action-btn" id="analyzeZipBtn" disabled>Analizar archivo ZIP</button>
                    <button type="button" class="zip-action-btn" id="uploadZipBtn" disabled>Importar ZIP al servidor</button>
                </div>
            </div>

            <!-- Explorador de carpetas: products / REFERENCIA / CÓDIGO DE COLOR -->
            <div id="explorerMode" style="display: none;">
                <div class="excel-upload-subtitle">Explora <strong>products</strong>: cada carpeta es un producto y dentro puede tener fotos o carpetas por color.</div>

                <div class="excel-field-group">
                    <label for="explorerTokenInput" class="excel-field-label">
                        Token <span class="required-mark">*</span>
                    </label>
                    <input type="text" id="explorerTokenInput" class="excel-field-input" autocomplete="off" placeholder="Ingresa tu token de Siigo">
                    <div class="excel-field-hint">Solo es necesario para crear carpetas, subir o eliminar fotos. Navegar no lo requiere.</div>
                </div>

                <nav class="explorer-breadcrumb" id="explorerBreadcrumb" aria-label="Ruta"></nav>

                <div class="explorer-toolbar">
                    <input type="text" id="explorerFilter" class="excel-field-input" placeholder="Filtrar carpetas..." autocomplete="off">
                    <button type="button" class="explorer-btn" id="explorerNewBtn">Nueva carpeta</button>
                </div>

                <div class="excel-error" id="explorerError"></div>

                <div id="explorerFoldersSection" style="display: none;">
                    <div class="section-label" id="explorerFoldersLabel">Carpetas</div>
                    <div class="folder-grid" id="explorerFolders"></div>
                </div>

                <div id="explorerFilesSection" style="display: none;">
                    <div class="section-label">Fotos</div>
                    <div class="photo-grid" id="explorerFiles"></div>
                </div>

                <div class="photo-empty-hint" id="explorerEmptyHint" style="display: none;"></div>

                <div id="explorerUploadSection" style="display: none;">
                    <div class="excel-dropzone explorer-dropzone" id="explorerDropzone">
                        <div class="excel-dropzone-text">Arrastra fotos aquí o <span>selecciónalas</span> para subirlas a esta carpeta</div>
                        <div class="excel-dropzone-hint">.jpg, .jpeg, .png, .webp (máx. 5 MB c/u)</div>
                        <input type="file" id="explorerPhotosInput" class="excel-input" accept=".jpg,.jpeg,.png,.webp" multiple>
                    </div>
                </div>
            </div>
        </div>

        <a href="{{ route('home') }}" class="back-link">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            Volver a acciones disponibles
        </a>
    </div>

</body>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/jszip@3.10.1/dist/jszip.min.js"></script>
<script>
    (function () {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        const tokenInput = document.getElementById('tokenInput');
        const bulkTokenInput = document.getElementById('bulkTokenInput');
        const explorerTokenInput = document.getElementById('explorerTokenInput');
        const zipModeSelect = document.getElementById('zipModeSelect');
        const zipModeHint = document.getElementById('zipModeHint');
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

        // --- Pestañas y sincronización del token ---
        const individualTab = document.getElementById('individualTab');
        const bulkTab = document.getElementById('bulkTab');
        const explorerTab = document.getElementById('explorerTab');
        const individualMode = document.getElementById('individualMode');
        const bulkMode = document.getElementById('bulkMode');
        const explorerMode = document.getElementById('explorerMode');

        const tokenInputs = [tokenInput, bulkTokenInput, explorerTokenInput];
        tokenInputs.forEach((input) => {
            input.addEventListener('input', () => {
                tokenInputs.forEach((other) => { if (other !== input) other.value = input.value; });
            });
        });

        individualTab.addEventListener('click', () => setMode('individual'));
        bulkTab.addEventListener('click', () => setMode('bulk'));
        explorerTab.addEventListener('click', () => setMode('explorer'));

        function setMode(mode) {
            const tabs = { individual: individualTab, bulk: bulkTab, explorer: explorerTab };
            const panels = { individual: individualMode, bulk: bulkMode, explorer: explorerMode };

            Object.keys(tabs).forEach((key) => {
                const active = key === mode;
                panels[key].style.display = active ? 'block' : 'none';
                tabs[key].classList.toggle('active', active);
                tabs[key].setAttribute('aria-selected', String(active));
            });

            // El explorador carga su contenido la primera vez que se abre.
            if (mode === 'explorer' && !explorerLoaded) {
                cargarExplorador('');
            }
        }

        // --- Carga masiva ZIP: solo análisis local, sin enviar archivos al servidor ---
        const zipDropzone = document.getElementById('zipDropzone');
        const zipInput = document.getElementById('zipInput');
        const zipSummary = document.getElementById('zipSummary');
        const zipStatus = document.getElementById('zipStatus');
        const analyzeZipBtn = document.getElementById('analyzeZipBtn');
        const uploadZipBtn = document.getElementById('uploadZipBtn');
        const allowedZipImageExt = ['jpg', 'jpeg', 'png', 'webp'];
        const maxZipSizeMB = 512;
        let selectedZip = null;
        let zipAnalysisValid = false;
        const bulkZipImportRoute = "{{ route('siigo.product_photo_bulk_upload') }}";

        zipDropzone.addEventListener('click', () => zipInput.click());
        ['dragover', 'dragenter'].forEach(evt => zipDropzone.addEventListener(evt, (e) => {
            e.preventDefault();
            zipDropzone.classList.add('dragover');
        }));
        ['dragleave', 'dragend'].forEach(evt => zipDropzone.addEventListener(evt, () => zipDropzone.classList.remove('dragover')));
        zipDropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            zipDropzone.classList.remove('dragover');
            if (e.dataTransfer.files.length) setZipFile(e.dataTransfer.files[0]);
        });
        zipInput.addEventListener('change', () => {
            if (zipInput.files.length) setZipFile(zipInput.files[0]);
            zipInput.value = '';
        });
        zipModeSelect.addEventListener('change', () => {
            zipAnalysisValid = false;
            uploadZipBtn.disabled = true;
            zipSummary.innerHTML = '';
            zipSummary.className = 'zip-summary';
            zipStatus.textContent = '';
            zipStatus.className = 'zip-status';
            zipModeHint.textContent = zipModeSelect.value === 'folders'
                ? 'Cada carpeta debe llamarse como la referencia y contener las imágenes de ese producto. Se aceptan carpetas vacías.'
                : 'Todas las imágenes deben estar directamente en la raíz del ZIP y el nombre de cada archivo, sin extensión, debe ser la referencia. Ejemplo: CUÑIS.jpg.';
        });
        analyzeZipBtn.addEventListener('click', analyzeZipLocally);
        uploadZipBtn.addEventListener('click', uploadZipToServer);

        function setZipFile(file) {
            zipStatus.className = 'zip-status';
            zipStatus.textContent = '';
            zipSummary.className = 'zip-summary';
            zipSummary.innerHTML = '';
            analyzeZipBtn.disabled = true;
            uploadZipBtn.disabled = true;
            zipAnalysisValid = false;

            if (!file.name.toLowerCase().endsWith('.zip')) {
                selectedZip = null;
                showZipStatus('Formato no válido. Selecciona únicamente un archivo ZIP (.zip).', 'error');
                return;
            }
            if (file.size > maxZipSizeMB * 1024 * 1024) {
                selectedZip = null;
                showZipStatus(`El ZIP supera el límite de ${maxZipSizeMB} MB.`, 'error');
                return;
            }

            selectedZip = file;
            zipDropzone.classList.add('has-file');
            zipDropzone.querySelector('.excel-dropzone-text').innerHTML = `<strong>${escapeHtml(file.name)}</strong>`;
            zipDropzone.querySelector('.excel-dropzone-hint').textContent = `Tamaño: ${formatSize(file.size)}. Aún no se ha enviado al servidor.`;
            analyzeZipBtn.disabled = false;
        }

        async function analyzeZipLocally() {
            if (!selectedZip) return;
            if (typeof JSZip === 'undefined') {
                showZipStatus('No se pudo cargar el analizador ZIP. Comprueba la conexión y vuelve a intentarlo.', 'error');
                return;
            }

            analyzeZipBtn.disabled = true;
            uploadZipBtn.disabled = true;
            zipAnalysisValid = false;
            analyzeZipBtn.textContent = 'Analizando localmente...';
            zipStatus.className = 'zip-status';
            zipSummary.className = 'zip-summary';
            zipSummary.innerHTML = '';

            try {
                const archive = await JSZip.loadAsync(selectedZip, { checkCRC32: false });
                const entries = Object.values(archive.files);
                const files = entries.filter(entry => !entry.dir);
                const folders = entries.filter(entry => entry.dir);
                const mode = zipModeSelect.value;
                const groups = new Map();
                const errors = [];
                let imageCount = 0;

                if (mode === 'folders') {
                    files.forEach(entry => {
                        const parts = entry.name.split('/').filter(Boolean);
                        const filename = parts[parts.length - 1] || '';
                        const ext = filename.includes('.') ? filename.split('.').pop().toLowerCase() : '';
                        if (parts.length === 1) {
                            errors.push(`Archivo en la raíz del ZIP: ${entry.name}`);
                            return;
                        }
                        const reference = parts[0];
                        if (!/^[A-ZÑ0-9_-]+$/.test(reference)) {
                            errors.push(`Nombre de carpeta inválido: "${reference}". Usa solo MAYÚSCULAS (se permite Ñ), números, guion o guion bajo.`);
                            return;
                        }
                        if (parts.length !== 2) {
                            errors.push(`No se permiten subcarpetas: ${entry.name}`);
                            return;
                        }
                        if (!allowedZipImageExt.includes(ext)) {
                            errors.push(`Archivo no permitido: ${entry.name}. Solo se aceptan JPG, JPEG, PNG y WEBP.`);
                            return;
                        }
                        if (!groups.has(reference)) groups.set(reference, []);
                        groups.get(reference).push(entry.name);
                        imageCount++;
                    });

                    folders.forEach(entry => {
                        const parts = entry.name.split('/').filter(Boolean);
                        if (parts.length > 1) errors.push(`No se permiten subcarpetas: ${entry.name}`);
                        else if (parts.length === 1 && !/^[A-ZÑ0-9_-]+$/.test(parts[0])) {
                            errors.push(`Nombre de carpeta inválido: "${parts[0]}". Usa solo MAYÚSCULAS (se permite Ñ), números, guion o guion bajo.`);
                        }
                    });
                } else {
                    if (folders.length) {
                        folders.forEach(entry => errors.push(`El ZIP de imágenes por nombre no debe contener carpetas: ${entry.name}`));
                    }
                    files.forEach(entry => {
                        const parts = entry.name.split('/').filter(Boolean);
                        if (parts.length !== 1) {
                            errors.push(`En el modo por nombre, todas las imágenes deben estar en la raíz: ${entry.name}`);
                            return;
                        }
                        const filename = parts[0];
                        const ext = filename.includes('.') ? filename.split('.').pop().toLowerCase() : '';
                        const reference = filename.slice(0, filename.lastIndexOf('.'));
                        if (!allowedZipImageExt.includes(ext)) {
                            errors.push(`Archivo no permitido: ${filename}. Solo se aceptan JPG, JPEG, PNG y WEBP.`);
                            return;
                        }
                        if (!/^[A-ZÑ0-9_-]+$/.test(reference)) {
                            errors.push(`Nombre de imagen inválido: "${filename}". El nombre sin extensión debe ser la referencia en MAYÚSCULAS (se permite Ñ), números, guion o guion bajo.`);
                            return;
                        }
                        if (!groups.has(reference)) groups.set(reference, []);
                        groups.get(reference).push(filename);
                        imageCount++;
                    });
                }

                const groupRows = Array.from(groups.entries()).map(([reference, groupFiles]) =>
                    `<li><strong>${escapeHtml(reference)}</strong>: ${groupFiles.length} imagen(es)</li>`
                ).join('');
                const errorRows = errors.length
                    ? `<strong style="color:#dc2626;">Problemas encontrados (${errors.length})</strong><ul>${errors.map(error => `<li>${escapeHtml(error)}</li>`).join('')}</ul>`
                    : '';
                const formatDescription = mode === 'folders'
                    ? 'Estructura requerida: REFERENCIA/imagen.jpg. Las carpetas deben estar en MAYÚSCULAS (se permite Ñ). Se aceptan carpetas vacías. No se permiten subcarpetas ni archivos en la raíz.'
                    : 'Estructura requerida: REFERENCIA.jpg. Todas las imágenes deben estar en la raíz del ZIP y el nombre sin extensión debe ser la referencia en MAYÚSCULAS (se permite Ñ).';
                zipSummary.innerHTML = `<strong>Resultado del análisis local</strong><ul><li>Archivos dentro del ZIP: ${files.length}</li><li>Imágenes válidas: ${imageCount}</li><li>Referencias detectadas: ${groups.size}</li><li>Problemas encontrados: ${errors.length}</li></ul>${groupRows ? `<strong>Referencias detectadas</strong><ul>${groupRows}</ul>` : ''}${errorRows}<p class="excel-field-hint">${formatDescription}</p>`;
                zipSummary.classList.add('show');
                zipAnalysisValid = errors.length === 0 && imageCount > 0;
                uploadZipBtn.disabled = !zipAnalysisValid;
                showZipStatus(
                    errors.length ? 'Análisis terminado: corrige los problemas indicados antes de importar.'
                        : imageCount === 0 ? 'El ZIP no contiene imágenes para importar.'
                        : 'ZIP válido. Puedes importar las imágenes al servidor.',
                    errors.length || imageCount === 0 ? 'error' : 'success'
                );
            } catch (error) {
                showZipStatus('No se pudo leer el ZIP. Verifica que no esté dañado o protegido con contraseña.', 'error');
            } finally {
                analyzeZipBtn.disabled = !selectedZip;
                analyzeZipBtn.textContent = 'Analizar archivo ZIP';
            }
        }

        async function uploadZipToServer() {
            const token = bulkTokenInput.value.trim();

            if (!token) {
                await Swal.fire({
                    icon: 'warning',
                    title: 'Token requerido',
                    text: 'Ingresa tu token de Siigo antes de importar el ZIP.',
                    confirmButtonText: 'Entendido'
                });
                bulkTokenInput.focus();
                return;
            }

            if (!selectedZip || !zipAnalysisValid) {
                await Swal.fire({
                    icon: 'warning',
                    title: 'ZIP no validado',
                    text: 'Selecciona y analiza un ZIP válido antes de importarlo.',
                    confirmButtonText: 'Entendido'
                });
                return;
            }

            const formData = new FormData();
            formData.append('token', token);
            formData.append('zip_mode', zipModeSelect.value);
            formData.append('zip', selectedZip);

            uploadZipBtn.disabled = true;
            analyzeZipBtn.disabled = true;
            uploadZipBtn.textContent = 'Importando imágenes...';

            Swal.fire({
                title: 'Cargando fotografías',
                text: 'Por favor espera. Se están guardando las imágenes y este proceso puede demorar varios minutos. No cierres esta ventana...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => Swal.showLoading()
            });

            try {
                const response = await fetch(bulkZipImportRoute, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                let data;
                try {
                    data = await response.json();
                } catch (parseError) {
                    data = {};
                }

                if (!response.ok || !data.success) {
                    Swal.close();
                    const message = data.error || data.message || 'No se pudo importar el ZIP.';
                    const details = Array.isArray(data.errors) && data.errors.length
                        ? '\n\n' + data.errors.join('\n')
                        : '';

                    showZipStatus(message + details, 'error');
                    await Swal.fire({
                        icon: 'error',
                        title: 'No se pudo completar la carga',
                        text: message,
                        footer: details ? `<div style="text-align:left;white-space:pre-line">${escapeHtml(details.trim())}</div>` : undefined,
                        confirmButtonText: 'Entendido'
                    });
                    return;
                }

                Swal.close();

                await Swal.fire({
                    icon: 'success',
                    title: '¡Carga completada!',
                    html: `
                        <p>Las fotografías se guardaron correctamente.</p>
                        <div style="text-align:left;margin-top:12px">
                            <p><strong>Referencias procesadas:</strong> ${Number(data.references_processed || 0)}</p>
                            <p><strong>Imágenes guardadas:</strong> ${Number(data.images_uploaded || 0)}</p>
                            <p><strong>Carpetas vacías:</strong> ${Number(data.empty_folders || 0)}</p>
                        </div>
                    `,
                    confirmButtonText: 'Aceptar',
                    allowOutsideClick: false
                });

                // Limpiar los campos solo después de confirmar el éxito.
                selectedZip = null;
                zipAnalysisValid = false;
                zipInput.value = '';
                tokenInputs.forEach((input) => { input.value = ''; });
                zipSummary.innerHTML = '';
                zipSummary.className = 'zip-summary';
                zipStatus.textContent = '';
                zipStatus.className = 'zip-status';
                zipDropzone.classList.remove('has-file', 'dragover');
                zipDropzone.querySelector('.excel-dropzone-text').innerHTML = 'Arrastra tu archivo ZIP aquí o <span>selecciónalo</span>';
                zipDropzone.querySelector('.excel-dropzone-hint').textContent = 'Solo se acepta ZIP con carpetas de referencia; cada carpeta debe contener solo imágenes JPG, JPEG, PNG o WEBP.';
                analyzeZipBtn.disabled = true;
                uploadZipBtn.disabled = true;

                // El explorador pudo quedar desactualizado tras la importación.
                explorerLoaded = false;
            } catch (error) {
                Swal.close();
                showZipStatus('Ocurrió un error de conexión durante la importación. Verifica el resultado antes de volver a enviar el ZIP para evitar duplicar imágenes.', 'error');
                await Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión',
                    text: 'No se pudo confirmar el resultado de la importación. Verifica el servidor antes de volver a enviar el ZIP para evitar duplicar imágenes.',
                    confirmButtonText: 'Entendido'
                });
            } finally {
                uploadZipBtn.disabled = !zipAnalysisValid;
                analyzeZipBtn.disabled = !selectedZip;
                uploadZipBtn.textContent = 'Importar ZIP al servidor';
            }
        }

        function showZipStatus(message, type) {
            zipStatus.textContent = message;
            zipStatus.className = `zip-status show ${type}`;
        }

        function escapeHtml(value) {
            return String(value).replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char]);
        }

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
            const result = await Swal.fire({
                icon: 'warning',
                title: '¿Eliminar esta foto?',
                text: 'Esta acción no se puede deshacer.',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6'
            });

            if (!result.isConfirmed) return;

            const token = tokenInput.value.trim();

            if (!token) {
                Swal.fire({
                    icon: 'error',
                    title: 'Token requerido',
                    text: 'Ingresa tu token antes de eliminar una foto.',
                    confirmButtonColor: '#3085d6'
                });
                return;
            }

            try {
                const res = await fetch("{{ route('siigo.product_photo_delete') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ token, referencia: currentReferencia, filename }),
                });

                const data = await res.json();

                if (!res.ok || !data.success) {
                    throw new Error(data.error || 'No se pudo eliminar la foto.');
                }

                itemEl.remove();

                if (!existingGrid.children.length) {
                    existingEmptyHint.style.display = 'block';
                }

                explorerLoaded = false;
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo eliminar la foto. Intenta de nuevo.',
                    confirmButtonColor: '#3085d6'
                });
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

            const token = tokenInput.value.trim();

            if (!token) {
                showError(uploadError, 'Ingresa tu token antes de subir fotos.');
                return;
            }

            uploadError.classList.remove('show');
            uploadSuccess.classList.remove('show');
            uploadBtn.disabled = true;
            uploadBtn.classList.add('is-loading');
            uploadBtnText.textContent = 'Subiendo...';

            const formData = new FormData();
            formData.append('token', token);
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

                if (!res.ok || !data.success) {
                    throw new Error(data.error || 'No se pudieron subir las fotos.');
                }

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
                explorerLoaded = false;
            } catch (err) {
                showError(uploadError, err.message || 'No se pudieron subir las fotos. Intenta de nuevo.');
            } finally {
                uploadBtn.classList.remove('is-loading');
                uploadBtnText.textContent = 'Subir fotos';
            }
        });

        // =====================================================================
        // Explorador de carpetas
        // =====================================================================
        const explorerRoute = "{{ route('siigo.product_photo_explorer') }}";
        const explorerUploadRoute = "{{ route('siigo.product_photo_explorer_upload') }}";
        const explorerDeleteRoute = "{{ route('siigo.product_photo_explorer_delete') }}";
        const explorerCreateRoute = "{{ route('siigo.product_photo_explorer_create_folder') }}";

        const explorerBreadcrumb = document.getElementById('explorerBreadcrumb');
        const explorerFilter = document.getElementById('explorerFilter');
        const explorerNewBtn = document.getElementById('explorerNewBtn');
        const explorerError = document.getElementById('explorerError');
        const explorerFoldersSection = document.getElementById('explorerFoldersSection');
        const explorerFoldersLabel = document.getElementById('explorerFoldersLabel');
        const explorerFolders = document.getElementById('explorerFolders');
        const explorerFilesSection = document.getElementById('explorerFilesSection');
        const explorerFiles = document.getElementById('explorerFiles');
        const explorerEmptyHint = document.getElementById('explorerEmptyHint');
        const explorerUploadSection = document.getElementById('explorerUploadSection');
        const explorerDropzone = document.getElementById('explorerDropzone');
        const explorerPhotosInput = document.getElementById('explorerPhotosInput');

        const folderIconSvg = '<svg class="folder-icon" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>';

        let explorerLoaded = false;
        let explorerData = null;

        explorerNewBtn.addEventListener('click', crearCarpeta);
        explorerFilter.addEventListener('input', renderExplorerFolders);

        async function cargarExplorador(path) {
            explorerError.classList.remove('show');
            explorerFilter.value = '';

            try {
                const res = await fetch(explorerRoute + '?path=' + encodeURIComponent(path || ''), {
                    headers: { 'Accept': 'application/json' },
                });
                const data = await res.json();

                if (!res.ok || !data.success) {
                    throw new Error(data.error || 'No se pudo cargar la carpeta.');
                }

                explorerData = data;
                explorerLoaded = true;
                renderExplorer();
            } catch (err) {
                showError(explorerError, err.message || 'No se pudo cargar la carpeta.');
            }
        }

        function renderExplorer() {
            const data = explorerData;

            // Breadcrumb
            explorerBreadcrumb.innerHTML = '';
            data.breadcrumb.forEach((crumb, index) => {
                const isLast = index === data.breadcrumb.length - 1;
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'crumb-btn' + (isLast ? ' current' : '');
                btn.textContent = crumb.label;
                if (!isLast) btn.addEventListener('click', () => cargarExplorador(crumb.path));
                explorerBreadcrumb.appendChild(btn);

                if (!isLast) {
                    const sep = document.createElement('span');
                    sep.className = 'crumb-sep';
                    sep.textContent = '/';
                    explorerBreadcrumb.appendChild(sep);
                }
            });

            // Botón "Nueva carpeta" según el nivel
            explorerNewBtn.style.display = data.level === 2 ? 'none' : '';
            explorerNewBtn.textContent = data.level === 0 ? 'Nuevo producto' : 'Nuevo color';
            explorerFilter.style.display = data.level === 2 ? 'none' : '';
            explorerFilter.placeholder = data.level === 0 ? 'Filtrar productos...' : 'Filtrar colores...';
            explorerFoldersLabel.textContent = data.level === 0 ? 'Productos' : 'Colores';

            renderExplorerFolders();
            renderExplorerFiles();

            // Zona de subida: solo dentro de un producto o de un color
            explorerUploadSection.style.display = data.level >= 1 ? 'block' : 'none';

            // Mensaje de carpeta vacía
            const empty = !data.folders.length && !data.files.length;
            explorerEmptyHint.style.display = empty ? 'block' : 'none';
            explorerEmptyHint.textContent = data.level === 0
                ? 'Todavía no hay productos en esta carpeta.'
                : 'Esta carpeta está vacía.';
        }

        function renderExplorerFolders() {
            const data = explorerData;
            if (!data) return;

            const term = explorerFilter.value.trim().toLowerCase();
            const folders = data.folders.filter((f) => f.label.toLowerCase().includes(term));

            explorerFolders.innerHTML = '';
            explorerFoldersSection.style.display = data.folders.length ? 'block' : 'none';

            folders.forEach((folder) => {
                const counts = folder.counts || { photos: 0, folders: 0 };
                let meta = `${counts.photos} foto(s)`;
                if (folder.type === 'product' && counts.folders) meta += ` · ${counts.folders} color(es)`;

                const el = document.createElement('button');
                el.type = 'button';
                el.className = 'folder-item';
                el.innerHTML = `
                    ${folder.type === 'color'
                        ? `<span class="folder-swatch" style="background:${escapeHtml(folder.hex)}"></span>`
                        : folderIconSvg}
                    <div class="folder-name">${escapeHtml(folder.label)}</div>
                    <div class="folder-meta">${escapeHtml(meta)}</div>
                `;
                el.addEventListener('click', () => cargarExplorador(folder.path));
                explorerFolders.appendChild(el);
            });
        }

        function renderExplorerFiles() {
            const data = explorerData;
            explorerFiles.innerHTML = '';
            explorerFilesSection.style.display = data.files.length ? 'block' : 'none';

            data.files.forEach((photo) => explorerFiles.appendChild(buildExplorerPhoto(photo)));
        }

        function buildExplorerPhoto(photo) {
            const item = document.createElement('div');
            item.className = 'photo-item';
            item.innerHTML = `
                <img src="${escapeHtml(photo.url)}" alt="${escapeHtml(photo.name)}">
                <button type="button" class="photo-delete-btn" title="Eliminar">&times;</button>
            `;
            item.querySelector('.photo-delete-btn').addEventListener('click', () => eliminarFotoExplorer(photo.name));
            return item;
        }

        function tokenExplorer() {
            return explorerTokenInput.value.trim();
        }

        async function pedirToken() {
            await Swal.fire({
                icon: 'warning',
                title: 'Token requerido',
                text: 'Ingresa tu token de Siigo para realizar esta acción.',
                confirmButtonText: 'Entendido'
            });
            explorerTokenInput.focus();
        }

        async function crearCarpeta() {
            if (!explorerData || explorerData.level === 2) return;

            if (!tokenExplorer()) {
                await pedirToken();
                return;
            }

            let options;

            if (explorerData.level === 0) {
                options = {
                    title: 'Nuevo producto',
                    input: 'text',
                    inputLabel: 'Referencia',
                    inputPlaceholder: 'Ej: VAMPELT',
                    inputValidator: (value) => (!value || !value.trim()) ? 'Ingresa la referencia.' : null,
                };
            } else {
                if (!explorerData.available_colors.length) {
                    Swal.fire({ icon: 'info', title: 'Sin colores disponibles', text: 'Este producto ya tiene una carpeta para cada color.' });
                    return;
                }

                // Select agrupado por macrocategoría (optgroup)
                const inputOptions = {};
                explorerData.available_colors.forEach((group) => {
                    inputOptions[group.macro] = {};
                    group.items.forEach((c) => { inputOptions[group.macro][c.carpeta] = c.nombre; });
                });

                options = {
                    title: 'Nuevo color',
                    input: 'select',
                    inputOptions,
                    inputPlaceholder: 'Selecciona un color',
                    inputValidator: (value) => !value ? 'Selecciona un color.' : null,
                };
            }

            const result = await Swal.fire({
                ...options,
                showCancelButton: true,
                confirmButtonText: 'Crear',
                cancelButtonText: 'Cancelar',
            });

            if (!result.isConfirmed) return;

            try {
                const res = await fetch(explorerCreateRoute, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ token: tokenExplorer(), path: explorerData.path, name: result.value }),
                });
                const data = await res.json();

                if (!res.ok || !data.success) {
                    throw new Error(data.error || data.message || 'No se pudo crear la carpeta.');
                }

                await cargarExplorador(data.path);
            } catch (err) {
                Swal.fire({ icon: 'error', title: 'Error', text: err.message || 'No se pudo crear la carpeta.' });
            }
        }

        async function eliminarFotoExplorer(filename) {
            const confirm = await Swal.fire({
                icon: 'warning',
                title: '¿Eliminar esta foto?',
                text: 'Esta acción no se puede deshacer.',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6'
            });

            if (!confirm.isConfirmed) return;

            if (!tokenExplorer()) {
                await pedirToken();
                return;
            }

            try {
                const res = await fetch(explorerDeleteRoute, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ token: tokenExplorer(), path: explorerData.path, filename }),
                });
                const data = await res.json();

                if (!res.ok || !data.success) {
                    throw new Error(data.error || 'No se pudo eliminar la foto.');
                }

                await cargarExplorador(explorerData.path);
            } catch (err) {
                Swal.fire({ icon: 'error', title: 'Error', text: err.message || 'No se pudo eliminar la foto.' });
            }
        }

        explorerDropzone.addEventListener('click', () => explorerPhotosInput.click());
        ['dragover', 'dragenter'].forEach(evt => explorerDropzone.addEventListener(evt, (e) => {
            e.preventDefault();
            explorerDropzone.classList.add('dragover');
        }));
        ['dragleave', 'dragend'].forEach(evt => explorerDropzone.addEventListener(evt, () => explorerDropzone.classList.remove('dragover')));
        explorerDropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            explorerDropzone.classList.remove('dragover');
            if (e.dataTransfer.files.length) subirFotosExplorer(Array.from(e.dataTransfer.files));
        });
        explorerPhotosInput.addEventListener('change', () => {
            if (explorerPhotosInput.files.length) {
                subirFotosExplorer(Array.from(explorerPhotosInput.files));
                explorerPhotosInput.value = '';
            }
        });

        async function subirFotosExplorer(files) {
            if (!explorerData || explorerData.level === 0) return;

            const valid = [];
            const rejected = [];

            files.forEach((file) => {
                const ext = file.name.split('.').pop().toLowerCase();
                if (!allowedExt.includes(ext)) rejected.push(`"${file.name}" no es un formato permitido`);
                else if (file.size / (1024 * 1024) > maxSizeMB) rejected.push(`"${file.name}" supera ${maxSizeMB} MB`);
                else valid.push(file);
            });

            if (rejected.length) {
                showError(explorerError, rejected.join('. ') + '.');
            } else {
                explorerError.classList.remove('show');
            }

            if (!valid.length) return;

            if (!tokenExplorer()) {
                await pedirToken();
                return;
            }

            const formData = new FormData();
            formData.append('token', tokenExplorer());
            formData.append('path', explorerData.path);
            valid.forEach((file) => formData.append('photos[]', file));

            Swal.fire({
                title: 'Subiendo fotos',
                text: 'Por favor espera...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => Swal.showLoading()
            });

            try {
                const res = await fetch(explorerUploadRoute, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });
                const data = await res.json();

                if (!res.ok || !data.success) {
                    throw new Error(data.error || data.message || 'No se pudieron subir las fotos.');
                }

                Swal.close();
                await cargarExplorador(explorerData.path);
            } catch (err) {
                Swal.close();
                Swal.fire({ icon: 'error', title: 'Error', text: err.message || 'No se pudieron subir las fotos.' });
            }
        }

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
