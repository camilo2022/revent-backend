<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Consulta de inventario</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.13.5/cdn.min.js" defer></script>
    <style>
        * { box-sizing: border-box; }

        body {
            background: #f3f4f6;
            font-family: 'Segoe UI', system-ui, sans-serif;
            margin: 0;
            color: #1f2937;
        }

        /* ---------------------------------------------------------- */
        /* Pantalla 1: elegir tienda                                   */
        /* ---------------------------------------------------------- */
        .store-screen {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            box-sizing: border-box;
        }

        .store-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 2.5rem 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #eef0f2;

            width: 100%;
            max-width: 900px;

            text-align: center;
            box-sizing: border-box;
        }

        .store-card .excel-icon {
            margin-bottom: 1rem;
        }

        .store-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: .35rem;
        }

        .store-subtitle {
            font-size: .85rem;
            color: #6b7280;
            margin-bottom: 1.5rem;
        }

        /* =========================
        GRID ESCRITORIO
        ========================= */

        .store-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .75rem;
        }

        /* =========================
        BOTONES
        ========================= */

        .store-btn {
            width: 100%;
            min-height: 70px;

            padding: 1rem .75rem;

            border-radius: 12px;
            border: 1.5px solid #d1d5db;

            background: #f9fafb;

            font-size: .92rem;
            font-weight: 700;
            color: #1f2937;

            cursor: pointer;

            transition:
                border-color .2s ease,
                background-color .2s ease,
                color .2s ease,
                transform .15s ease;

            box-sizing: border-box;

            /* Permite nombres largos */
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .store-btn:hover {
            border-color: #16a34a;
            background: #f0fdf4;
            color: #15803d;
            transform: translateY(-1px);
        }

        .store-btn:active {
            transform: translateY(0);
        }

        .store-btn:disabled {
            opacity: .5;
            cursor: not-allowed;
            transform: none;
        }

        .store-empty {
            font-size: .85rem;
            color: #9ca3af;
            padding: 1rem 0;
        }


        /* =========================
        TABLET
        ========================= */

        @media (max-width: 768px) {

            .store-screen {
                padding: 1.5rem 1rem;
            }

            .store-card {
                max-width: 650px;
                padding: 2rem 1.5rem;
            }

            .store-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: .75rem;
            }

            .store-btn {
                min-height: 65px;
            }
        }


        /* =========================
        CELULAR
        ========================= */

        @media (max-width: 480px) {

            .store-screen {
                min-height: 100dvh;
                padding: 1rem .75rem;
                align-items: center;
            }

            .store-card {
                width: 100%;
                max-width: 100%;
                padding: 1.75rem 1rem;
                border-radius: 14px;
            }

            .store-title {
                font-size: 1.15rem;
            }

            .store-subtitle {
                font-size: .82rem;
                line-height: 1.4;
                margin-bottom: 1.25rem;
            }

            .store-grid {
                grid-template-columns: 1fr;
                gap: .65rem;
            }

            .store-btn {
                min-height: 58px;
                padding: .9rem .75rem;
                font-size: .9rem;
            }
        }

        /* ---------------------------------------------------------- */
        /* Pantalla 2: inventario, pantalla completa                   */
        /* ---------------------------------------------------------- */
        .app-shell { min-height: 100vh; }

        .app-topbar {
            background: #ffffff;
            border-bottom: 1px solid #eef0f2;
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .app-title { font-size: 1.15rem; font-weight: 700; color: #1f2937; margin: 0; }
        .app-subtitle { font-size: .78rem; color: #6b7280; margin: .1rem 0 0; }
        .store-pill {
            display: flex;
            align-items: center;
            gap: .5rem;
            background: #16a34a;
            color: #fff;
            padding: .5rem .9rem;
            border-radius: 999px;
            font-size: .82rem;
            font-weight: 700;
            border: none;
            cursor: pointer;
        }
        .store-pill:hover { background: #15803d; }

        .app-body { padding: 1.5rem 2rem 3rem; max-width: 1280px; margin: 0 auto; }

        .toolbar {
            display: flex;
            gap: .75rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }
        .excel-field-input, .excel-field-select {
            padding: 0.65rem 0.85rem;
            font-size: 0.88rem;
            color: #1f2937;
            background: #f9fafb;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            transition: border-color 0.2s ease, background 0.2s ease;
        }
        .search-wrap { position: relative; flex: 1; min-width: 240px; }
        .search-wrap input { width: 100%; padding-left: 2.4rem; }
        .search-wrap .icon { position: absolute; left: .85rem; top: 50%; transform: translateY(-50%); color: #9ca3af; }
        .search-wrap input:focus, .excel-field-select:focus {
            outline: none;
            border-color: #16a34a;
            background: #ffffff;
        }
        .clear-btn {
            position: absolute; right: .6rem; top: 50%; transform: translateY(-50%);
            border: none; background: transparent; cursor: pointer; color: #9ca3af; font-size: 1rem;
        }

        .chips { display: flex; gap: .5rem; flex-wrap: wrap; margin-bottom: 1.25rem; }
        .chip {
            padding: .4rem .9rem;
            border-radius: 999px;
            border: 1.5px solid #d1d5db;
            background: #fff;
            color: #4b5563;
            font-size: .8rem;
            font-weight: 600;
            cursor: pointer;
        }
        .chip.activo { background: #16a34a; border-color: #16a34a; color: #fff; }

        .contador { font-size: .78rem; color: #9ca3af; margin-bottom: 1rem; }

        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 1.1rem; }

        .prod-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            border: 1px solid #eef0f2;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .prod-head { display: flex; gap: 1rem; padding: 1.1rem 1.2rem .8rem; }
        .prod-thumb {
            width: 68px; height: 68px; border-radius: 10px; object-fit: cover;
            border: 1px solid #eef0f2; flex-shrink: 0; background: #f9fafb;
        }
        .prod-ref { font-size: .72rem; font-weight: 700; letter-spacing: .3px; color: #16a34a; text-transform: uppercase; }
        .prod-nombre { font-size: 1rem; font-weight: 700; color: #1f2937; line-height: 1.25; margin-top: .1rem; }
        .prod-meta { font-size: .78rem; color: #6b7280; margin-top: .15rem; }
        .prod-total { text-align: right; flex-shrink: 0; }
        .prod-total-num { font-size: 1.3rem; font-weight: 800; }
        .prod-total-label { font-size: .68rem; color: #9ca3af; }

        .color-tabs { display: flex; gap: .5rem; flex-wrap: wrap; padding: 0 1.2rem .7rem; }
        .color-tab {
            display: flex; align-items: center; gap: .4rem;
            padding: .3rem .6rem .3rem .3rem;
            border-radius: 999px; border: 1.5px solid #d1d5db; background: #fff;
            cursor: pointer; font-size: .78rem; font-weight: 600; color: #4b5563;
            position: relative;
        }
        .color-tab.activo { border-color: #16a34a; background: #f0fdf4; color: #15803d; }
        .color-dot { width: 16px; height: 16px; border-radius: 50%; border: 1px solid rgba(0,0,0,.12); flex-shrink: 0; position: relative; }
        .color-dot.agotado::after {
            content: ''; position: absolute; inset: 0; margin: auto; top: 50%;
            width: 140%; height: 1.5px; background: #dc2626; transform: rotate(45deg);
        }

        .photo-gallery {
            display: flex; gap: .5rem; overflow-x: auto; padding: 0 1.2rem .8rem;
        }
        .photo-gallery img {
            width: 84px; height: 84px; object-fit: cover; border-radius: 8px;
            border: 1px solid #eef0f2; flex-shrink: 0; background: #f9fafb;
        }

        .tallas-row { display: flex; gap: .4rem; flex-wrap: wrap; padding: 0 1.2rem .9rem; }
        .talla-chip {
            display: flex; flex-direction: column; align-items: center; min-width: 38px;
            padding: .35rem .3rem .4rem; border-radius: 8px; border: 1px solid #eef0f2;
        }
        .talla-num { font-weight: 700; font-size: .78rem; color: #1f2937; }
        .talla-stock { font-size: .68rem; font-weight: 700; margin-top: .1rem; }

        .prod-footer {
            border-top: 1px solid #eef0f2;
            background: #f9fafb;
            padding: .7rem 1.2rem;
            font-size: .74rem;
            color: #9ca3af;
            display: flex; justify-content: space-between; align-items: center;
            margin-top: auto;
        }

        .suggest-box {
            border-top: 1px solid #fde68a;
            background: #fffbeb;
            padding: .85rem 1.2rem 1rem;
        }
        .suggest-title {
            display: flex; align-items: center; gap: .4rem;
            font-size: .76rem; font-weight: 700; color: #92400e; margin-bottom: .55rem;
        }
        .suggest-item {
            display: flex; align-items: center; gap: .6rem;
            background: #fff; border: 1px solid #fde68a; border-radius: 10px;
            padding: .45rem .7rem; margin-bottom: .4rem; font-size: .78rem;
        }
        .suggest-item:last-child { margin-bottom: 0; }
        .suggest-dot { width: 14px; height: 14px; border-radius: 50%; border: 1px solid rgba(0,0,0,.12); flex-shrink: 0; }
        .suggest-name { font-weight: 700; color: #1f2937; }
        .suggest-reason { font-size: .7rem; color: #6b7280; }
        .suggest-stock { margin-left: auto; font-weight: 700; color: #16a34a; }

        .empty-state { text-align: center; padding: 3rem 1rem; color: #9ca3af; font-size: .9rem; }

        .prod-thumb, .photo-gallery img { cursor: zoom-in; transition: opacity .15s ease; }
        .prod-thumb:hover, .photo-gallery img:hover { opacity: .85; }

        /* ---------------------------------------------------------- */
        /* Estados de carga / error                                     */
        /* ---------------------------------------------------------- */
        .status-box {
            display: flex; align-items: center; gap: .6rem;
            padding: .9rem 1.1rem; border-radius: 12px; font-size: .85rem; font-weight: 600;
            margin-bottom: 1.1rem;
        }
        .status-box.cargando { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .status-box.error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .spinner {
            width: 16px; height: 16px; border-radius: 50%;
            border: 2px solid rgba(29,78,216,.25); border-top-color: #1d4ed8;
            animation: spin .7s linear infinite; flex-shrink: 0;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .retry-btn {
            margin-left: auto; border: none; background: #b91c1c; color: #fff;
            padding: .35rem .8rem; border-radius: 8px; font-size: .78rem; font-weight: 700; cursor: pointer;
        }

        /* ---------------------------------------------------------- */
        /* Lightbox / carrusel de fotos                                 */
        /* ---------------------------------------------------------- */
        .lightbox-overlay {
            position: fixed; inset: 0; background: rgba(17, 24, 39, .82);
            display: flex; align-items: center; justify-content: center;
            z-index: 100; padding: 2rem 1rem;
        }
        .lightbox-box {
            max-width: 720px; width: 100%; display: flex; flex-direction: column; align-items: center;
        }
        .lightbox-header {
            width: 100%; display: flex; justify-content: space-between; align-items: center;
            color: #fff; margin-bottom: .85rem;
        }
        .lightbox-title { font-size: .95rem; font-weight: 700; }
        .lightbox-sub { font-size: .78rem; color: #d1d5db; margin-top: .1rem; }
        .lightbox-close {
            background: rgba(255,255,255,.12); border: none; color: #fff; cursor: pointer;
            width: 34px; height: 34px; border-radius: 999px; font-size: 1.1rem; line-height: 1;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .lightbox-close:hover { background: rgba(255,255,255,.22); }
        .lightbox-stage { position: relative; width: 100%; display: flex; align-items: center; justify-content: center; }
        .lightbox-stage img {
            max-width: 100%; max-height: 62vh; object-fit: contain; border-radius: 12px;
            background: #fff;
        }
        .lightbox-nav {
            position: absolute; top: 50%; transform: translateY(-50%);
            background: rgba(255,255,255,.92); border: none; cursor: pointer;
            width: 42px; height: 42px; border-radius: 999px; font-size: 1.2rem; color: #1f2937;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 2px 10px rgba(0,0,0,.2);
        }
        .lightbox-nav:hover { background: #fff; }
        .lightbox-nav.prev { left: -8px; }
        .lightbox-nav.next { right: -8px; }
        .lightbox-thumbs { display: flex; gap: .5rem; margin-top: 1rem; flex-wrap: wrap; justify-content: center; }
        .lightbox-thumbs img {
            width: 56px; height: 56px; object-fit: cover; border-radius: 8px; cursor: pointer;
            border: 2px solid transparent; opacity: .65;
        }
        .lightbox-thumbs img.activa { border-color: #16a34a; opacity: 1; }
    </style>
</head>
<body>

{{-- Datos que vienen del controlador (SiigoInventoryService->auth() + $this->warehouses($token)) --}}
<script>
    window.WAREHOUSES = @json($warehouses ?? []);
    window.PRODUCTOS_INICIALES = @json($productos ?? []);
    window.INVENTORY_FILTER_URL = "{{ route('siigo.inventory_filter_search') }}";
</script>

<div x-data="inventarioApp()">

    {{-- ============ PASO 1: elegir tienda (pantalla completa) ============ --}}
    <template x-if="!tienda">
        <div class="store-screen">
            <div class="store-card">
                <div class="excel-icon" style="margin:0 auto 1rem;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        <polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                </div>
                <div class="store-title">¿En qué tienda estás?</div>
                <div class="store-subtitle">Elige tu punto de venta para ver la disponibilidad</div>

                <template x-if="warehouses.length > 0">
                    <div class="store-grid">
                        <template x-for="w in warehouses" :key="w.id">
                            <button class="store-btn" :disabled="cargando" @click="seleccionarTienda(w)" x-text="w.name"></button>
                        </template>
                    </div>
                </template>
                <template x-if="warehouses.length === 0">
                    <div class="store-empty">No hay bodegas disponibles desde Siigo en este momento.</div>
                </template>
            </div>
        </div>
    </template>

    {{-- ============ PASO 2: inventario a pantalla completa ============ --}}
    <template x-if="tienda">
        <div class="app-shell">
            <div class="app-topbar">
                <div>
                    <p class="app-title">Inventario disponible</p>
                    <p class="app-subtitle">Disponibilidad por color y talla de cada referencia</p>
                </div>
                <button class="store-pill" @click="cambiarTienda()">
                    <span x-text="tiendaNombre"></span>
                </button>
            </div>

            <div class="app-body">

                <template x-if="cargando">
                    <div class="status-box cargando">
                        <span class="spinner"></span>
                        Cargando inventario de <span x-text="tiendaNombre"></span>...
                    </div>
                </template>

                <template x-if="error && !cargando">
                    <div class="status-box error">
                        <span>⚠</span>
                        <span x-text="error"></span>
                        <button class="retry-btn" @click="cargarInventario()">Reintentar</button>
                    </div>
                </template>

                <template x-if="!cargando">
                    <div>
                        <div class="toolbar">
                            <div class="search-wrap">
                                <span class="icon">🔍</span>
                                <input type="text" class="excel-field-input" x-model="query" placeholder="Busca por referencia, nombre o color...">
                                <button class="clear-btn" x-show="query" @click="query = ''">✕</button>
                            </div>
                        </div>

                        <div class="chips">
                            <template x-for="c in categorias" :key="c">
                                <button class="chip" :class="{ activo: c === categoria }" @click="categoria = c" x-text="c"></button>
                            </template>
                        </div>

                        <div class="contador">
                            <span x-text="resultados.length"></span>
                            <span x-text="resultados.length === 1 ? 'referencia encontrada' : 'referencias encontradas'"></span>
                            en <span x-text="tiendaNombre"></span>
                        </div>

                        <div class="grid">
                            <template x-for="p in resultados" :key="p.id ?? p.referencia">
                                <div class="prod-card">
                                    <div class="prod-head">
                                        <img class="prod-thumb"
                                             :src="(p.colores[colorSeleccionado[p.id] ?? 0].fotos || [])[0] || p.imagen"
                                             :alt="p.nombre"
                                             @click="abrirLightbox(p, colorSeleccionado[p.id] ?? 0)">
                                        <div style="min-width:0; flex:1; cursor: pointer;" @click="abrirLightbox(p, colorSeleccionado[p.id] ?? 0)">
                                            <div class="prod-ref" x-text="p.referencia"></div>
                                            <div class="prod-nombre" x-text="p.nombre"></div>
                                            <div class="prod-meta" x-text="p.categoria + ' · ' + p.genero"></div>
                                        </div>
                                        <div class="prod-total">
                                            <div class="prod-total-num"
                                                 :style="{ color: totalProducto(p) > 0 ? '#16a34a' : '#dc2626' }"
                                                 x-text="totalProducto(p)"></div>
                                            <div class="prod-total-label">en <span x-text="tiendaNombre"></span></div>
                                        </div>
                                    </div>

                                    <div class="color-tabs">
                                        <template x-for="(c, i) in p.colores" :key="c.nombre">
                                            <button class="color-tab" :class="{ activo: (colorSeleccionado[p.id] ?? 0) === i }" @click="colorSeleccionado[p.id] = i">
                                                <span class="color-dot" :class="{ agotado: totalColor(c) === 0 }" :style="{ background: c.hex }"></span>
                                                <span x-text="c.nombre"></span>
                                            </button>
                                        </template>
                                    </div>

                                    <template x-if="(p.colores[colorSeleccionado[p.id] ?? 0].fotos || []).length > 0">
                                        <div class="photo-gallery">
                                            <template x-for="(foto, fi) in p.colores[colorSeleccionado[p.id] ?? 0].fotos" :key="fi">
                                                <img :src="foto" :alt="p.nombre + ' foto ' + (fi + 1)"
                                                     @click="abrirLightbox(p, colorSeleccionado[p.id] ?? 0, fi)">
                                            </template>
                                        </div>
                                    </template>

                                    <div class="tallas-row">
                                        <template x-for="[talla, qty] in Object.entries(p.colores[colorSeleccionado[p.id] ?? 0].tallas)" :key="talla">
                                            <div class="talla-chip" :style="qty > 0 ? { borderColor: nivel(qty).color, background: nivel(qty).color + '14' } : {}">
                                                <span class="talla-num" x-text="talla"></span>
                                                <span class="talla-stock" :style="{ color: qty > 0 ? nivel(qty).color : '#9ca3af' }" x-text="qty"></span>
                                            </div>
                                        </template>
                                    </div>

                                    <div class="prod-footer">
                                        <span>Total referencia: <strong x-text="totalProducto(p)"></strong> und.</span>
                                        <span x-text="p.colores.length + (p.colores.length === 1 ? ' color' : ' colores')"></span>
                                    </div>

                                    <template x-if="totalColor(p.colores[colorSeleccionado[p.id] ?? 0]) === 0">
                                        <div class="suggest-box">
                                            <div class="suggest-title">
                                                ⚠ Sin stock en <span x-text="p.colores[colorSeleccionado[p.id] ?? 0].nombre.toLowerCase()"></span> — alternativas
                                            </div>
                                            <template x-for="(s, idx) in sugerenciasPara(p, p.colores[colorSeleccionado[p.id] ?? 0])" :key="idx">
                                                <div class="suggest-item">
                                                    <span class="suggest-dot" :style="{ background: s.color.hex }"></span>
                                                    <div style="min-width:0;">
                                                        <div class="suggest-name" x-text="s.producto.referencia + ' · ' + s.producto.nombre"></div>
                                                        <div class="suggest-reason" x-text="s.motivo"></div>
                                                    </div>
                                                    <span class="suggest-stock" x-text="totalColor(s.color) + ' und.'"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <div class="empty-state" x-show="resultados.length === 0" style="grid-column: 1 / -1;">
                                No encontramos nada con esa búsqueda en <span x-text="tiendaNombre"></span>.
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </template>

    {{-- ============ Lightbox / carrusel de fotos ============ --}}
    <template x-if="lightbox.open">
        <div class="lightbox-overlay" @click.self="cerrarLightbox()" @keydown.window.escape="cerrarLightbox()">
            <div class="lightbox-box">
                <div class="lightbox-header">
                    <div>
                        <div class="lightbox-title" x-text="lightbox.referencia + ' · ' + lightbox.nombre"></div>
                        <div class="lightbox-sub" x-text="lightbox.colorNombre + ' — foto ' + (lightbox.index + 1) + ' de ' + lightbox.fotos.length"></div>
                    </div>
                    <button class="lightbox-close" @click="cerrarLightbox()">✕</button>
                </div>

                <div class="lightbox-stage">
                    <button class="lightbox-nav prev" @click="moverLightbox(-1)" x-show="lightbox.fotos.length > 1">‹</button>
                    <img :src="lightbox.fotos[lightbox.index]" :alt="lightbox.nombre">
                    <button class="lightbox-nav next" @click="moverLightbox(1)" x-show="lightbox.fotos.length > 1">›</button>
                </div>

                <div class="lightbox-thumbs" x-show="lightbox.fotos.length > 1">
                    <template x-for="(foto, fi) in lightbox.fotos" :key="fi">
                        <img :src="foto" :class="{ activa: fi === lightbox.index }" @click="lightbox.index = fi">
                    </template>
                </div>
            </div>
        </div>
    </template>
</div>

<script>
    function inventarioApp() {
        return {
            // ------------------------------------------------------------
            // Bodegas ($warehouses -> [{id, name}]) y token CSRF inyectados
            // por el controlador Blade (InventoryFilterSiigoController).
            // ------------------------------------------------------------
            warehouses: window.WAREHOUSES || [],
            csrfToken: document.querySelector('meta[name="csrf-token"]')?.content || '',
            filterUrl: window.INVENTORY_FILTER_URL,

            // Productos: se llenan al seleccionar tienda con la respuesta
            // de POST /siigo/inventory_filter_search (warehouse_id).
            // Si el backend ya te manda una lista inicial (window.PRODUCTOS_INICIALES)
            // y quieres precargarla antes de elegir tienda, cámbiala aquí.
            productos: [],

            tienda: null,        // id de la bodega seleccionada
            tiendaNombre: '',    // nombre para mostrar en UI
            query: '',
            categoria: 'Todos',
            colorSeleccionado: {},

            cargando: false,
            error: null,

            lightbox: {
                open: false,
                fotos: [],
                index: 0,
                referencia: '',
                nombre: '',
                colorNombre: '',
            },

            seleccionarTienda(w) {
                this.tienda = w.id;
                this.tiendaNombre = w.name;
                this.query = '';
                this.categoria = 'Todos';
                this.colorSeleccionado = {};
                this.cargarInventario();
            },

            cambiarTienda() {
                this.tienda = null;
                this.tiendaNombre = '';
                this.productos = [];
                this.query = '';
                this.categoria = 'Todos';
                this.colorSeleccionado = {};
                this.error = null;
            },

            async cargarInventario() {
                this.cargando = true;
                this.error = null;

                try {
                    const res = await fetch(this.filterUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ warehouse_id: this.tienda }),
                    });

                    if (!res.ok) {
                        throw new Error('HTTP ' + res.status);
                    }

                    const data = await res.json();

                    // Acepta tanto { productos: [...] } como un array plano.
                    const lista = Array.isArray(data) ? data : (data.productos ?? []);
                    this.productos = lista.map(p => this.normalizarProducto(p));
                    this.colorSeleccionado = {};
                } catch (e) {
                    console.error('Error cargando inventario:', e);
                    this.error = 'No pudimos cargar el inventario de esta bodega. Intenta de nuevo.';
                    this.productos = [];
                } finally {
                    this.cargando = false;
                }
            },

            // Rellena campos que el front-end espera (fotos, etc.) por si
            // el JSON de Siigo/tu controlador no trae todo el shape exacto.
            normalizarProducto(p) {
                return {
                    ...p,
                    colores: (p.colores || []).map(c => ({
                        ...c,
                        tallas: c.tallas || {},
                        fotos: c.fotos || [],
                    })),
                };
            },

            get categorias() {
                const cats = [...new Set(this.productos.map(p => p.categoria))];
                return ['Todos', ...cats];
            },

            get resultados() {
                const q = this.query.trim().toLowerCase();
                return this.productos.filter(p => {
                    if (this.categoria !== 'Todos' && p.categoria !== this.categoria) return false;
                    if (!q) return true;
                    return (
                        p.referencia.toLowerCase().includes(q) ||
                        p.nombre.toLowerCase().includes(q) ||
                        p.categoria.toLowerCase().includes(q) ||
                        p.colores.some(c => c.nombre.toLowerCase().includes(q))
                    );
                });
            },

            totalColor(color) {
                return Object.values(color.tallas).reduce((a, b) => a + b, 0);
            },

            totalProducto(p) {
                return p.colores.reduce((a, c) => a + this.totalColor(c), 0);
            },

            nivel(qty) {
                if (qty <= 0) return { color: '#dc2626' };
                if (qty <= 2) return { color: '#d97706' };
                return { color: '#16a34a' };
            },

            sugerenciasPara(producto, colorSeleccionado) {
                const sugerencias = [];

                producto.colores.forEach(c => {
                    if (c.nombre !== colorSeleccionado.nombre && this.totalColor(c) > 0) {
                        sugerencias.push({ producto, color: c, motivo: `Mismo modelo en ${c.nombre}` });
                    }
                });

                this.productos.forEach(p => {
                    if (p.id === producto.id) return;
                    if (p.categoria !== producto.categoria) return;
                    p.colores.forEach(c => {
                        if (c.nombre.toLowerCase() === colorSeleccionado.nombre.toLowerCase() && this.totalColor(c) > 0) {
                            sugerencias.push({ producto: p, color: c, motivo: `${c.nombre} en ${p.categoria.toLowerCase()}` });
                        }
                    });
                });

                if (sugerencias.length === 0) {
                    this.productos.forEach(p => {
                        if (p.id === producto.id) return;
                        if (p.categoria !== producto.categoria) return;
                        p.colores.forEach(c => {
                            if (this.totalColor(c) > 0) {
                                sugerencias.push({ producto: p, color: c, motivo: `Otra opción en ${p.categoria.toLowerCase()}` });
                            }
                        });
                    });
                }

                return sugerencias.slice(0, 4);
            },

            abrirLightbox(producto, colorIdx, fotoIdx = 0) {
                const color = producto.colores[colorIdx];
                const fotos = (color.fotos && color.fotos.length > 0) ? color.fotos : [producto.imagen];
                this.lightbox = {
                    open: true,
                    fotos,
                    index: Math.min(fotoIdx, fotos.length - 1),
                    referencia: producto.referencia,
                    nombre: producto.nombre,
                    colorNombre: color.nombre,
                };
            },

            cerrarLightbox() {
                this.lightbox.open = false;
            },

            moverLightbox(delta) {
                const total = this.lightbox.fotos.length;
                this.lightbox.index = (this.lightbox.index + delta + total) % total;
            },
        };
    }
</script>

</body>
</html>
