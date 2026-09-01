<!-- resources/views/integration/purchase_order_result.blade.php -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultado orden de compra</title>

    <style>
        * { box-sizing: border-box; }

        body {
            background: #f3f4f6;
            font-family: 'Segoe UI', system-ui, sans-serif;
            margin: 0;
            padding: 2rem 1rem;
        }

        .result-wrapper {
            max-width: 620px;
            margin: 2rem auto;
        }

        .result-banner {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            border-radius: 14px;
            padding: 1.1rem 1.4rem;
            margin-bottom: 1.5rem;
        }

        .result-banner.success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
        }

        .result-banner.error {
            background: #fef2f2;
            border: 1px solid #fecaca;
        }

        .result-banner-icon {
            width: 40px;
            height: 40px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .result-banner.success .result-banner-icon { background: #16a34a; }
        .result-banner.error .result-banner-icon { background: #dc2626; }

        .result-banner-icon svg {
            width: 20px;
            height: 20px;
            stroke: #fff;
        }

        .result-banner-title {
            font-size: 1rem;
            font-weight: 600;
        }

        .result-banner.success .result-banner-title { color: #14532d; }
        .result-banner.error .result-banner-title { color: #7f1d1d; }

        .result-banner-subtitle {
            font-size: 0.82rem;
        }

        .result-banner.success .result-banner-subtitle { color: #166534; }
        .result-banner.error .result-banner-subtitle { color: #991b1b; }

        .order-card {
            background: #ffffff;
            border: 1px solid #eef0f2;
            border-radius: 16px;
            padding: 1.4rem 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
            margin-bottom: 1rem;
        }

        .order-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 0.9rem;
        }

        .order-card-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            margin-bottom: 0.15rem;
        }

        .order-card-number {
            font-size: 1.05rem;
            font-weight: 700;
            color: #111827;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.65rem;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 600;
            background: #dcfce7;
            color: #166534;
            white-space: nowrap;
        }

        .order-card-details {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.6rem;
            padding-top: 0.9rem;
            margin-top: 0.9rem;
            border-top: 1px solid #f1f3f5;
        }

        .detail-label {
            font-size: 0.72rem;
            font-weight: 700;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            margin-bottom: 0.1rem;
        }

        .detail-value {
            font-size: 0.88rem;
            font-weight: 500;
            color: #374151;
        }

        .order-card-footer {
            margin-top: 1rem;
        }

        .btn-link {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.55rem 1rem;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            background: #f9fafb;
            color: #4338ca;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
        }

        .btn-link:hover {
            background: #eef2ff;
        }

        .btn-link svg {
            width: 15px;
            height: 15px;
        }

        .error-card {
            background: #ffffff;
            border: 1px solid #fecaca;
            border-radius: 14px;
            padding: 1rem 1.2rem;
            margin-bottom: 0.75rem;
        }

        .error-card-title {
            font-size: 0.82rem;
            font-weight: 700;
            color: #7f1d1d;
            margin-bottom: 0.3rem;
        }

        .error-card-text {
            font-size: 0.85rem;
            color: #374151;
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

    @if (!empty($errors))
        {{-- ================= BLOQUE DE ERRORES ================= --}}
        <div class="result-banner error">
            <div class="result-banner-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </div>
            <div>
                <div class="result-banner-title">No se pudo generar la orden de compra</div>
                <div class="result-banner-subtitle">Corrige los siguientes errores e intenta nuevamente.</div>
            </div>
        </div>

        @foreach ($errors as $error)
            <div class="error-card">
                <div class="error-card-title">
                    @if (isset($error['ProductCode']))
                        Fila {{ $error['Row'] }} — {{ $error['ProductCode'] }}
                        @if (!empty($error['WarehouseCode']))
                            ({{ $error['WarehouseCode'] }})
                        @endif
                    @else
                        {{ $error['Row'] }}
                    @endif
                </div>
                <div class="error-card-text">{{ $error['Error'] }}</div>
            </div>
        @endforeach
    @endif
    @if(!empty($ordenes_compra))
        {{-- ================= BLOQUE DE ÉXITO ================= --}}
        <div class="result-banner success">
            <div class="result-banner-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
            </div>
            <div>
                <div class="result-banner-title">{{ count($ordenes_compra) }} orden(es) de compra generada(s) correctamente</div>
                <div class="result-banner-subtitle">Puedes ver el detalle de cada orden en Siigo desde el botón "Ver orden".</div>
            </div>
        </div>

        @foreach ($ordenes_compra as $index => $orden)
            <div class="order-card">
                <div class="order-card-header">
                    <div>
                        <div class="order-card-label">Orden de compra #{{ $index + 1 }}</div>
                        <div class="order-card-number">{{ $orden['documento'] ?? 'Sin número' }}</div>
                    </div>
                    <span class="badge">Generada</span>
                </div>

                <div class="order-card-details">
                    <div>
                        <div class="detail-label">Bodega</div>
                        <div class="detail-value">{{ $orden['bodega']['id'] }} - {{ $orden['bodega']['name'] }}</div>
                    </div>
                </div>

                <div class="order-card-footer">
                    @if (!empty($orden['url']))
                        <a href="{{ $orden['url'] }}" target="_blank" class="btn-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                            </svg>
                            Ver orden
                        </a>
                    @endif
                </div>
            </div>
        @endforeach
    @endif

    <a href="{{ route('home') }}" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Volver a acciones disponibles
    </a>

</div>

</body>
</html>
