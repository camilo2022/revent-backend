<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Buscar documento Siigo</title>
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

            <div class="excel-upload-title">Buscar documento Siigo</div>

            <div class="excel-upload-subtitle">
                Selecciona el tipo de documento y escribe el código. Esto generará un pdf con la información
                del documento en Siigo y las imagenes de los productos que contiene.
            </div>

            <form id="searchForm" autocomplete="off">

                <div class="excel-field-group">
                    <label for="type" class="excel-field-label">
                        Tipo de documento <span class="required-mark">*</span>
                    </label>
                    <select id="type" class="excel-field-input" required autofocus>
                        <option value="">Selecciona...</option>
                        @foreach ($document_types as $index => $type)
                            <option value="{{ $index }}" data-url="{{ route($type['route']) }}" data-token-hint="{{ $type['token_hint'] }}">
                                {{ $index }} - {{ $type['name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="excel-field-group">
                    <label for="token" class="excel-field-label">
                        Token <span class="required-mark">*</span>
                    </label>
                    <input type="text" id="token" class="excel-field-input" required>
                    <div class="excel-field-hint" id="tokenHint">
                        Selecciona primero el tipo de documento.
                    </div>
                </div>

                <div class="excel-field-group">
                    <label for="query" class="excel-field-label">
                        Código <span class="required-mark">*</span>
                    </label>
                    <input type="text" id="query" class="excel-field-input" placeholder="Ej: NT-53384" required>
                    <div class="excel-field-hint">
                        Escribe el código exacto tal como aparece en Siigo.
                    </div>
                </div>

                <div class="excel-error" id="searchError"></div>

                <button type="submit" class="excel-submit-btn" id="submitBtn">
                    <span class="spinner"></span>
                    <span class="excel-submit-btn-text">Buscar</span>
                </button>
            </form>

        </div>

        <a href="{{ route('home') }}" class="back-link">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            Volver a acciones disponibles
        </a>
    </div>

    <script>
        (function () {
            const form = document.getElementById('searchForm');
            const tokenInput = document.getElementById('token');
            const tokenHint = document.getElementById('tokenHint');
            const typeInput = document.getElementById('type');
            const queryInput = document.getElementById('query');
            const submitBtn = document.getElementById('submitBtn');
            const submitBtnText = submitBtn.querySelector('.excel-submit-btn-text');
            const errorBox = document.getElementById('searchError');

            function showError(message) {
                errorBox.textContent = message;
                errorBox.classList.add('show');
            }

            function downloadBlob(blob, fileName) {
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = fileName;
                document.body.appendChild(link);
                link.click();
                link.remove();
                setTimeout(function () { URL.revokeObjectURL(link.href); }, 1000);
            }

            // Cada tipo pide un formato de token distinto (NT: JWT, OC: cookie completa)
            typeInput.addEventListener('change', function () {
                const option = typeInput.selectedOptions[0];
                tokenHint.textContent = option && option.dataset.tokenHint
                    ? option.dataset.tokenHint
                    : 'Selecciona primero el tipo de documento.';
            });

            form.addEventListener('submit', async function (event) {
                event.preventDefault();
                errorBox.classList.remove('show');

                // Ruta del option seleccionado
                const url = typeInput.selectedOptions[0] ? typeInput.selectedOptions[0].dataset.url : null;

                if (!url) {
                    showError('El tipo de documento seleccionado no tiene ruta asignada.');
                    return;
                }

                const params = new URLSearchParams({
                    token: tokenInput.value.trim(),
                    type: typeInput.value,
                    query: queryInput.value.trim(),
                });

                submitBtn.disabled = true;
                submitBtn.classList.add('is-loading');
                submitBtnText.textContent = 'Buscando...';

                try {
                    const response = await fetch(url + '?' + params.toString(), {
                        method: 'GET',
                        headers: { 'Accept': 'application/json, application/pdf' },
                    });

                    console.log('Ruta:', url);
                    console.log('Status:', response.status);

                    const contentType = response.headers.get('Content-Type') || '';

                    if (contentType.includes('application/pdf')) {
                        const disposition = response.headers.get('Content-Disposition') || '';
                        const match = disposition.match(/filename="?([^";]+)"?/i);

                        downloadBlob(await response.blob(), match ? match[1] : 'documento.pdf');
                        console.log('PDF descargado.');
                        return;
                    }

                    const data = await response.json();
                    console.log('Respuesta:', data);

                    if (data.error) {
                        showError(data.error);
                    } else if (data.message) {
                        showError(data.message);
                    } else if (data.document === null) {
                        showError('No se encontró el documento en Siigo.');
                    }
                } catch (error) {
                    console.error('Error en la consulta:', error);
                    showError('No se pudo realizar la consulta. Revisa la consola para más detalle.');
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('is-loading');
                    submitBtnText.textContent = 'Buscar';
                }
            });
        })();
    </script>
</body>

</html>
