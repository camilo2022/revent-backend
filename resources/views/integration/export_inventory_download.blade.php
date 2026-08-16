<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exportación en proceso</title>

    <style>
        * { box-sizing: border-box; }

        body {
            background: #f3f4f6;
            font-family: 'Segoe UI', system-ui, sans-serif;
            margin: 0;
            padding: 2rem 1rem;
        }

        .result-wrapper {
            max-width: 580px;
            margin: 2rem auto;
        }

        .result-banner {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 14px;
            padding: 1.1rem 1.4rem;
            margin-bottom: 1.5rem;
        }

        .result-banner-icon {
            width: 40px;
            height: 40px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #16a34a;
            border-radius: 50%;
        }

        .result-banner-icon svg {
            width: 20px;
            height: 20px;
            stroke: #fff;
        }

        .result-banner-title {
            font-size: 1rem;
            font-weight: 600;
            color: #14532d;
        }

        .result-banner-subtitle {
            font-size: 0.82rem;
            color: #166534;
        }

        .info-card {
            background: #ffffff;
            border: 1px solid #eef0f2;
            border-radius: 16px;
            padding: 1.4rem 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
        }

        .info-card-title {
            font-size: 0.78rem;
            font-weight: 700;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            margin-bottom: 0.9rem;
        }

        .email-list {
            display: flex;
            flex-direction: column;
            gap: 0.55rem;
        }

        .email-item {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.6rem 0.8rem;
            background: #f9fafb;
            border: 1px solid #f1f3f5;
            border-radius: 10px;
        }

        .email-item-icon {
            width: 28px;
            height: 28px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eff6ff;
            border-radius: 8px;
        }

        .email-item-icon svg {
            width: 14px;
            height: 14px;
            stroke: #1d4ed8;
        }

        .email-item-text {
            font-size: 0.85rem;
            font-weight: 500;
            color: #374151;
            word-break: break-all;
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

<div class="result-wrapper">

    {{-- ================= BLOQUE DE ÉXITO ================= --}}
    <div class="result-banner">
        <div class="result-banner-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
        </div>
        <div>
            <div class="result-banner-title">Exportación en proceso</div>
            <div class="result-banner-subtitle">
                Te notificaremos por email cuando el reporte de inventario esté listo.
            </div>
        </div>
    </div>

    <div class="info-card">
        <div class="info-card-title">Se enviará a los siguientes correos</div>

        <div class="email-list">
            @foreach ($emails as $email)
                <div class="email-item">
                    <div class="email-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                    </div>
                    <div class="email-item-text">{{ $email }}</div>
                </div>
            @endforeach
        </div>
    </div>

    <a href="{{ route('home') }}" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Volver a acciones disponibles
    </a>

</div>

</body>
</html>
