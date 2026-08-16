<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultado de traslados masivos</title>

    <style>
        * { box-sizing: border-box; }

        body {
            background: #f3f4f6;
            font-family: 'Segoe UI', system-ui, sans-serif;
            margin: 0;
            padding: 2rem 1rem;
        }

        .result-wrapper {
            max-width: 760px;
            margin: 0 auto;
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

        /* ---- Banner de errores ---- */
        .result-banner-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
        }

        .result-banner-error .result-banner-icon {
            background: #dc2626;
        }

        .result-banner-error .result-banner-title {
            color: #7f1d1d;
        }

        .result-banner-error .result-banner-subtitle {
            color: #991b1b;
        }

        .error-card {
            background: #ffffff;
            border: 1px solid #fee2e2;
            border-radius: 16px;
            padding: 1.1rem 1.4rem;
            margin-bottom: 0.9rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
        }

        .error-card-header {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin-bottom: 0.7rem;
        }

        .error-card-icon {
            width: 26px;
            height: 26px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fee2e2;
            border-radius: 50%;
        }

        .error-card-icon svg {
            width: 14px;
            height: 14px;
            stroke: #dc2626;
        }

        .error-card-row {
            font-size: 0.9rem;
            font-weight: 600;
            color: #7f1d1d;
        }

        .error-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem 1.3rem;
            margin-bottom: 0.7rem;
        }

        .error-meta-item {
            display: flex;
            flex-direction: column;
            gap: 0.1rem;
        }

        .error-meta-label {
            font-size: 0.66rem;
            font-weight: 600;
            color: #b91c1c;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .error-meta-value {
            font-size: 0.8rem;
            font-weight: 500;
            color: #374151;
        }

        .error-message {
            font-size: 0.83rem;
            color: #991b1b;
            background: #fef2f2;
            border: 1px solid #fee2e2;
            border-radius: 8px;
            padding: 0.6rem 0.8rem;
            line-height: 1.4;
        }
        /* ---- Fin estilos de errores ---- */

        .transfer-card {
            background: #ffffff;
            border: 1px solid #eef0f2;
            border-radius: 16px;
            padding: 1.4rem 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
        }

        .transfer-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 0.9rem;
        }

        .transfer-number {
            font-size: 0.78rem;
            font-weight: 600;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            margin-bottom: 0.2rem;
        }

        .transfer-title {
            font-size: 0.98rem;
            font-weight: 600;
            color: #1f2937;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.72rem;
            font-weight: 600;
            padding: 0.3rem 0.65rem;
            border-radius: 999px;
            white-space: nowrap;
        }

        .badge-approved {
            background: #dcfce7;
            color: #15803d;
        }

        .badge-pending {
            background: #fef3c7;
            color: #b45309;
        }

        .transfer-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem 1.4rem;
            margin: -0.3rem 0 0.9rem;
            padding-bottom: 0.9rem;
            border-bottom: 1px solid #f1f3f5;
        }

        .transfer-meta-item {
            display: flex;
            flex-direction: column;
            gap: 0.1rem;
        }

        .transfer-meta-label {
            font-size: 0.68rem;
            font-weight: 600;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .transfer-meta-value {
            font-size: 0.82rem;
            font-weight: 500;
            color: #374151;
        }

        .transfer-links {
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem;
            margin-bottom: 0.9rem;
        }

        .transfer-link {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.83rem;
            font-weight: 500;
            padding: 0.5rem 0.9rem;
            border-radius: 8px;
            text-decoration: none;
            transition: background 0.2s ease;
        }

        .transfer-link svg {
            width: 15px;
            height: 15px;
        }

        .link-preview {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .link-preview:hover {
            background: #dbeafe;
        }

        .link-download {
            background: #f9fafb;
            color: #374151;
            border: 1px solid #e5e7eb;
        }

        .link-download:hover {
            background: #f3f4f6;
        }

        .transit-box {
            margin-top: 0.9rem;
            padding: 1rem 1.1rem;
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 12px;
        }

        .transit-box-title {
            font-size: 0.85rem;
            font-weight: 600;
            color: #92400e;
            margin-bottom: 0.3rem;
        }

        .transit-box-text {
            font-size: 0.8rem;
            color: #a16207;
            margin-bottom: 0.85rem;
            line-height: 1.4;
        }

        .transit-box-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem;
            align-items: center;
        }

        .btn-download-approval {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.82rem;
            font-weight: 600;
            padding: 0.55rem 1rem;
            border-radius: 8px;
            background: #d97706;
            color: #fff;
            text-decoration: none;
            transition: background 0.2s ease;
        }

        .btn-download-approval:hover {
            background: #b45309;
        }

        .btn-download-approval svg {
            width: 15px;
            height: 15px;
            stroke: #fff;
        }

        .confirm-form {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
        }

        .confirm-input-label {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.8rem;
            font-weight: 600;
            padding: 0.55rem 1rem;
            border-radius: 8px;
            background: #ffffff;
            color: #92400e;
            border: 1px dashed #f59e0b;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .confirm-input-label:hover {
            background: #fef3c7;
        }

        .confirm-input-label input {
            display: none;
        }

        .confirm-file-name {
            font-size: 0.76rem;
            color: #78716c;
            max-width: 180px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .btn-confirm-submit {
            font-size: 0.8rem;
            font-weight: 600;
            padding: 0.55rem 1rem;
            border-radius: 8px;
            background: #16a34a;
            color: #fff;
            border: none;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .btn-confirm-submit:hover {
            background: #15803d;
        }

        .btn-confirm-submit:disabled {
            background: #d1d5db;
            cursor: not-allowed;
        }

        .confirmed-flag {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.78rem;
            font-weight: 600;
            color: #15803d;
        }

        .confirmed-flag svg {
            width: 14px;
            height: 14px;
            stroke: #15803d;
        }
    </style>
</head>
<body>

<div class="result-wrapper">

    @if (isset($errors) && count($errors) > 0)

        {{-- ================= BLOQUE DE ERRORES ================= --}}
        <div class="result-banner result-banner-error">
            <div class="result-banner-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </div>
            <div>
                <div class="result-banner-title">{{ count($errors) }} error(es) de validación</div>
                <div class="result-banner-subtitle">
                    No fue posible procesar el cargue. Corrige los siguientes errores y vuelve a intentarlo.
                </div>
            </div>
        </div>

        @foreach ($errors as $index => $error)
            <div class="error-card">
                <div class="error-card-header">
                    <div class="error-card-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </div>
                    <div class="error-card-row">Fila: {{ $error['Row'] ?? '-' }}</div>
                </div>

                @if (isset($error['ProductCode']) || isset($error['Description']) || isset($error['WarehouseCode']) || isset($error['AvailableQuantity']) || isset($error['RequiredQuantity']))
                    <div class="error-meta">
                        @if (isset($error['ProductCode']))
                            <div class="error-meta-item">
                                <span class="error-meta-label">Producto</span>
                                <span class="error-meta-value">{{ $error['ProductCode'] }}</span>
                            </div>
                        @endif
                        @if (isset($error['Description']))
                            <div class="error-meta-item">
                                <span class="error-meta-label">Descripción</span>
                                <span class="error-meta-value">{{ $error['Description'] }}</span>
                            </div>
                        @endif
                        @if (isset($error['WarehouseCode']))
                            <div class="error-meta-item">
                                <span class="error-meta-label">Bodega</span>
                                <span class="error-meta-value">{{ $error['WarehouseCode'] }}</span>
                            </div>
                        @endif
                        @if (isset($error['AvailableQuantity']))
                            <div class="error-meta-item">
                                <span class="error-meta-label">Disponible</span>
                                <span class="error-meta-value">{{ $error['AvailableQuantity'] ?? 0 }}</span>
                            </div>
                        @endif
                        @if (isset($error['RequiredQuantity']))
                            <div class="error-meta-item">
                                <span class="error-meta-label">Requerido</span>
                                <span class="error-meta-value">{{ $error['RequiredQuantity'] ?? 0 }}</span>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="error-message">
                    {{ $error['Error'] ?? '' }}
                </div>
            </div>
        @endforeach
        {{-- =============== FIN BLOQUE DE ERRORES =============== --}}

    @endif
    @if (isset($traslados) && count($traslados) > 0)

        {{-- ================= BLOQUE DE ÉXITO ================= --}}
        <div class="result-banner">
            <div class="result-banner-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
            </div>
            <div>
                <div class="result-banner-title">{{ count($traslados) }} traslado(s) generado(s) correctamente</div>
                <div class="result-banner-subtitle">
                    Los traslados en tránsito requieren descargar el archivo de aprobación, cargarlo de nuevo y confirmarlo.
                </div>
            </div>
        </div>

        @foreach ($traslados as $index => $traslado)
            <div class="transfer-card">
                <div class="transfer-card-header">
                    <div>
                        <div class="transfer-number">Traslado #{{ $index + 1 }}</div>
                        <div class="transfer-title">{{ $traslado['data']['document'] }}</div>
                    </div>

                    @if (!is_null($traslado['approved']))
                        <span class="badge badge-pending">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            En tránsito
                        </span>
                    @else
                        <span class="badge badge-approved">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            Directo
                        </span>
                    @endif
                </div>

                <div class="transfer-meta">
                    <div class="transfer-meta-item">
                        <span class="transfer-meta-label">Usuario</span>
                        <span class="transfer-meta-value">{{ $traslado['data']['user'] }}</span>
                    </div>
                    <div class="transfer-meta-item">
                        <span class="transfer-meta-label">Creado</span>
                        <span class="transfer-meta-value">{{ $traslado['data']['created_at'] }}</span>
                    </div>
                    <div class="transfer-meta-item">
                        <span class="transfer-meta-label">Fecha traslado</span>
                        <span class="transfer-meta-value">{{ \Carbon\Carbon::parse($traslado['data']['date'])->format('d/m/Y') }}</span>
                    </div>
                    <div class="transfer-meta-item">
                        <span class="transfer-meta-label">ID</span>
                        <span class="transfer-meta-value">{{ $traslado['data']['id'] }}</span>
                    </div>
                </div>

                <div class="transfer-links">
                    <a href="{{ $traslado['preview'] }}" target="_blank" class="transfer-link link-preview">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                        Ver traslado
                    </a>
                    <a href="{{ $traslado['preview_download'] }}" target="_blank" class="transfer-link link-download">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Descargar traslado
                    </a>
                </div>

                @if (!is_null($traslado['approved']))
                    <div class="transit-box">
                        <div class="transit-box-title">Traslado en tránsito</div>
                        <div class="transit-box-text">
                            Este traslado quedó en tránsito. Cuando la mercancía llegue físicamente a la bodega de destino, descarga este archivo y vuelve a cargarlo para confirmarlo en Siigo.
                        </div>

                        <div class="transit-box-actions">
                            <a href="{{ $traslado['approved']['download_url'] }}" class="btn-download-approval" download>
                                <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                {{ $traslado['approved']['name'] }}
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
        {{-- =============== FIN BLOQUE DE ÉXITO =============== --}}

    @endif

</div>

</body>
</html>
