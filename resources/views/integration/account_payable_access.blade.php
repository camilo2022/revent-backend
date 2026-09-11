<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Acceso a Cuentas por pagar</title>
    <style>
        * { box-sizing: border-box; }

        body {
            background: #f3f4f6;
            font-family: 'Segoe UI', system-ui, sans-serif;
            margin: 0;
            padding: 2rem 1rem;
        }

        .excel-upload-wrapper {
            max-width: 480px;
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

        .excel-field-input.is-invalid {
            border-color: #fca5a5;
        }

        .excel-field-hint {
            font-size: 0.75rem;
            color: #9ca3af;
            margin-top: 0.3rem;
        }

        .excel-submit-btn {
            width: 100%;
            margin-top: 0.5rem;
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

        .excel-submit-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .btn-spinner {
            display: none;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255, 255, 255, 0.4);
            border-top-color: #ffffff;
            border-radius: 50%;
            margin-right: 0.5rem;
            vertical-align: -2px;
            animation: btn-spin 0.7s linear infinite;
        }

        .excel-submit-btn.is-loading .btn-spinner {
            display: inline-block;
        }

        @keyframes btn-spin {
            to { transform: rotate(360deg); }
        }

        .excel-error {
            margin-top: 0.75rem;
            font-size: 0.8rem;
            color: #dc2626;
            background: #fef2f2;
            border: 1px solid #fecaca;
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
        }

        .excel-status {
            margin-bottom: 1.1rem;
            font-size: 0.8rem;
            color: #166534;
            background: #f0fdf4;
            border: 1px solid #86efac;
            padding: 0.6rem 0.85rem;
            border-radius: 8px;
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

            <div class="excel-upload-title">Acceso a Cuentas por pagar</div>
            <div class="excel-upload-subtitle">
                Escribe tu correo autorizado y te enviaremos un enlace de acceso.
            </div>

            @if (session('status'))
                <div class="excel-status">{{ session('status') }}</div>
            @endif

            <form action="{{ route('siigo.account_payable_send_access_link') }}" method="POST" id="accessForm">
                @csrf

                <div class="excel-field-group">
                    <label for="email" class="excel-field-label">
                        Correo electrónico <span class="required-mark">*</span>
                    </label>
                    <input type="email" name="email" id="email"
                        class="excel-field-input @error('email') is-invalid @enderror"
                        placeholder="nombre@revent.com.co" value="{{ old('email') }}" required autofocus>
                    <div class="excel-field-hint">
                        Solo correos autorizados del equipo de contabilidad/tecnología pueden solicitar acceso.
                    </div>
                </div>

                @error('email')
                    <div class="excel-error">{{ $message }}</div>
                @enderror

                <button type="submit" class="excel-submit-btn" id="submitBtn">
                    <span class="btn-spinner"></span>
                    <span class="btn-label">Enviar enlace de acceso</span>
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
            var form = document.getElementById('accessForm');
            var submitBtn = document.getElementById('submitBtn');
            var btnLabel = submitBtn.querySelector('.btn-label');

            form.addEventListener('submit', function (e) {
                // Si el navegador bloquea el envio por validacion HTML5 (campo vacio/invalido),
                // el evento 'submit' no llega aqui, asi que es seguro deshabilitar directo.
                submitBtn.disabled = true;
                submitBtn.classList.add('is-loading');
                btnLabel.textContent = 'Enviando...';
            });
        })();
    </script>

</body>
</html>
