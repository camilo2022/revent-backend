<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Reportes disponibles</title>

    <style>
        * { box-sizing: border-box; }

        body {
            background: #f3f4f6;
            font-family: 'Segoe UI', system-ui, sans-serif;
            margin: 0;
            padding: 2rem 1rem;
        }

        .wrapper {
            max-width: 900px;
            margin: 0 auto;
        }

        .page-header {
            margin-bottom: 1.6rem;
        }

        .page-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .page-subtitle {
            font-size: 0.85rem;
            color: #6b7280;
            margin-top: 0.3rem;
        }

        .reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 1rem;
        }

        .report-card {
            background: #ffffff;
            border: 1px solid #eef0f2;
            border-radius: 16px;
            padding: 1.4rem 1.3rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
            text-decoration: none;
            display: flex;
            flex-direction: column;
            gap: 0.9rem;
            transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
        }

        .report-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
        }

        .report-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .report-icon svg {
            width: 22px;
            height: 22px;
            stroke: #fff;
        }

        /* Colores por tipo de reporte */
        .icon-inventario { background: #4f46e5; }
        .icon-facturacion { background: #16a34a; }
        .icon-facturacion360 { background: #d97706; }
        .icon-compra { background: #dc2626; }
        .icon-traslado { background: #7c3aed; }
        .icon-trazabilidad { background: #0891b2; }

        .report-body {
            flex: 1;
        }

        .report-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }

        .report-desc {
            font-size: 0.78rem;
            color: #9ca3af;
            line-height: 1.4;
        }

        .report-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 0.8rem;
            border-top: 1px solid #f1f3f5;
        }

        .report-action {
            font-size: 0.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.35rem;
            color: #1f2937;
        }

        /* Color del texto/flecha según el ícono de la tarjeta (no depende de la posición) */
        .report-card:has(.icon-inventario) .report-action { color: #4f46e5; }
        .report-card:has(.icon-facturacion) .report-action { color: #16a34a; }
        .report-card:has(.icon-facturacion360) .report-action { color: #d97706; }
        .report-card:has(.icon-compra) .report-action { color: #dc2626; }
        .report-card:has(.icon-traslado) .report-action { color: #7c3aed; }
        .report-card:has(.icon-trazabilidad) .report-action { color: #0891b2; }

        .report-action svg {
            width: 14px;
            height: 14px;
            transition: transform 0.15s ease;
        }

        .report-card:hover .report-action svg {
            transform: translateX(2px);
        }
    </style>
</head>
<body>

<div class="wrapper">

    <div class="page-header">
        <div class="page-title">⚙️ Acciones disponibles</div>
        <div class="page-subtitle">Acciones desarrolladas por el equipo de tecnología de Revent para integración con Siigo.</div>
    </div>

    <div class="reports-grid">

        <a href="{{ route('siigo.export_inventory') }}" class="report-card">
            <div class="report-icon icon-inventario">
                <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                    <line x1="12" y1="22.08" x2="12" y2="12"/>
                </svg>
            </div>
            <div class="report-body">
                <div class="report-title">Reporte de Inventario</div>
                <div class="report-desc">Consulta existencias y movimientos de productos por bodega.</div>
            </div>
            <div class="report-footer">
                <span class="report-action">
                    Generar reporte
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </span>
            </div>
        </a>

        <a href="{{ route('siigo.export_invoice') }}" class="report-card">
            <div class="report-icon icon-facturacion">
                <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                    <polyline points="10 9 9 9 8 9"/>
                </svg>
            </div>
            <div class="report-body">
                <div class="report-title">Reporte de Facturación</div>
                <div class="report-desc">Detalle de facturas emitidas en un rango de fechas.</div>
            </div>
            <div class="report-footer">
                <span class="report-action">
                    Generar reporte
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </span>
            </div>
        </a>

        <a href="{{ route('siigo.export_invoice_360') }}" class="report-card">
            <div class="report-icon icon-facturacion360">
                <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="2" y1="12" x2="22" y2="12"/>
                    <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                </svg>
            </div>
            <div class="report-body">
                <div class="report-title">Reporte de Facturación 360</div>
                <div class="report-desc">Vista integral de facturación con indicadores y cruces por cliente.</div>
            </div>
            <div class="report-footer">
                <span class="report-action">
                    Generar reporte
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </span>
            </div>
        </a>

        <a href="{{ route('siigo.export_purchase') }}" class="report-card">
            <div class="report-icon icon-compra">
                <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="9" cy="21" r="1"/>
                    <circle cx="20" cy="21" r="1"/>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                </svg>
            </div>
            <div class="report-body">
                <div class="report-title">Reporte de Compra</div>
                <div class="report-desc">Resumen de compras realizadas a proveedores por periodo.</div>
            </div>
            <div class="report-footer">
                <span class="report-action">
                    Generar reporte
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </span>
            </div>
        </a>

        <a href="{{ route('siigo.masive_transfer') }}" class="report-card">
            <div class="report-icon icon-traslado">
                <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="17 1 21 5 17 9"/>
                    <path d="M3 11V9a4 4 0 0 1 4-4h14"/>
                    <polyline points="7 23 3 19 7 15"/>
                    <path d="M21 13v2a4 4 0 0 1-4 4H3"/>
                </svg>
            </div>
            <div class="report-body">
                <div class="report-title">Carga de Traslados Masivos</div>
                <div class="report-desc">Sube un archivo Excel para generar múltiples traslados entre bodegas.</div>
            </div>
            <div class="report-footer">
                <span class="report-action">
                    Cargar traslados
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </span>
            </div>
        </a>

        <a href="{{ route('siigo.product_traceability') }}" class="report-card">
            <div class="report-icon icon-trazabilidad">
                <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    <path d="M11 8v3l2 2"/>
                </svg>
            </div>
            <div class="report-body">
                <div class="report-title">Trazabilidad de Producto</div>
                <div class="report-desc">Consulta el historial de movimientos de una referencia por bodega y fecha.</div>
            </div>
            <div class="report-footer">
                <span class="report-action">
                    Consultar trazabilidad
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </span>
            </div>
        </a>

    </div>

</div>

</body>
</html>
