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

        body { background: #f3f4f6; font-family: 'Segoe UI', system-ui, sans-serif; margin: 0; color: #1f2937; }

        /* ---------------------------------------------------------- */
        /* Pantalla 1: elegir tienda                                  */
        /* ---------------------------------------------------------- */

        .store-screen { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem 1rem; }

        .store-card {
            background: #fff; border-radius: 16px; padding: 2.5rem 2rem; width: 100%; max-width: 900px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .08); border: 1px solid #eef0f2; text-align: center;
        }

        .store-card .excel-icon { margin-bottom: 1rem; }
        .store-title { font-size: 1.25rem; font-weight: 700; margin-bottom: .35rem; }
        .store-subtitle { font-size: .85rem; color: #6b7280; margin-bottom: 1.5rem; }

        .store-search-wrap { position: relative; max-width: 420px; margin: 0 auto 1.5rem; }
        .store-search-wrap input { width: 100%; padding-left: 2.4rem; }
        .store-search-wrap .icon { position: absolute; left: .85rem; top: 50%; transform: translateY(-50%); color: #9ca3af; }

        .store-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; }

        .store-btn {
            width: 100%; min-height: 70px; padding: 1rem .75rem; border-radius: 12px; border: 1.5px solid #d1d5db;
            background: #f9fafb; font-size: .92rem; font-weight: 700; color: #1f2937; cursor: pointer;
            transition: border-color .2s ease, background-color .2s ease, color .2s ease, transform .15s ease;
            overflow-wrap: anywhere; word-break: break-word;
        }

        .store-btn:hover { border-color: #16a34a; background: #f0fdf4; color: #15803d; transform: translateY(-1px); }
        .store-btn:active { transform: translateY(0); }
        .store-btn:disabled { opacity: .5; cursor: not-allowed; transform: none; }

        .store-empty { font-size: .85rem; color: #9ca3af; padding: 1rem 0; }

        @media (max-width: 768px) {
            .store-screen { padding: 1.5rem 1rem; }
            .store-card { max-width: 650px; padding: 2rem 1.5rem; }
            .store-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .store-btn { min-height: 65px; }
        }

        @media (max-width: 480px) {
            .store-screen { min-height: 100dvh; padding: 1rem .75rem; }
            .store-card { max-width: 100%; padding: 1.75rem 1rem; border-radius: 14px; }
            .store-title { font-size: 1.15rem; }
            .store-subtitle { font-size: .82rem; line-height: 1.4; margin-bottom: 1.25rem; }
            .store-grid { grid-template-columns: 1fr; gap: .65rem; }
            .store-btn { min-height: 58px; padding: .9rem .75rem; font-size: .9rem; }
        }

        /* ---------------------------------------------------------- */
        /* Pantalla 2: inventario                                     */
        /* ---------------------------------------------------------- */

        .app-shell { min-height: 100vh; }

        /* Header sticky: título + tienda + buscador siempre visibles */
        .app-header {
            position: sticky; top: 0; z-index: 20; background: #fff;
            border-bottom: 1px solid #eef0f2; box-shadow: 0 2px 10px rgba(0, 0, 0, .05);
        }

        .header-inner { max-width: 1280px; margin: 0 auto; padding: .75rem 2rem; }

        .header-top { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }

        .app-title { font-size: 1.15rem; font-weight: 700; margin: 0; }
        .app-subtitle { font-size: .78rem; color: #6b7280; margin: .1rem 0 0; }

        .store-pill {
            display: flex; align-items: center; gap: .5rem; background: #16a34a; color: #fff; padding: .5rem .9rem;
            border-radius: 999px; font-size: .82rem; font-weight: 700; border: none; cursor: pointer;
            max-width: 50%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }

        .store-pill:hover { background: #15803d; }

        .app-body { padding: 1.25rem 2rem 3rem; max-width: 1280px; margin: 0 auto; }

        .toolbar { display: flex; gap: .6rem; margin-top: .75rem; }

        .excel-field-input, .excel-field-select {
            padding: .65rem .85rem; font-size: .88rem; color: #1f2937; background: #f9fafb;
            border: 1px solid #d1d5db; border-radius: 10px; transition: border-color .2s ease, background .2s ease;
        }

        .excel-field-select { cursor: pointer; width: 100%; }

        .search-wrap { position: relative; flex: 1; min-width: 0; }
        .search-wrap input { width: 100%; padding-left: 2.4rem; padding-right: 2.2rem; }
        .search-wrap .icon { position: absolute; left: .85rem; top: 50%; transform: translateY(-50%); color: #9ca3af; }

        .search-wrap input:focus, .excel-field-select:focus { outline: none; border-color: #16a34a; background: #fff; }

        .clear-btn {
            position: absolute; right: .6rem; top: 50%; transform: translateY(-50%);
            border: none; background: transparent; cursor: pointer; color: #9ca3af; font-size: 1rem;
        }

        /* Botón de filtros avanzados */
        .filter-btn {
            display: flex; align-items: center; gap: .45rem; padding: 0 1rem; border-radius: 10px;
            border: 1px solid #d1d5db; background: #f9fafb; color: #1f2937; font-size: .85rem; font-weight: 700;
            cursor: pointer; flex-shrink: 0; transition: border-color .2s ease, background .2s ease;
        }

        .filter-btn:hover { border-color: #16a34a; background: #f0fdf4; color: #15803d; }
        .filter-btn.activo { border-color: #16a34a; background: #f0fdf4; color: #15803d; }

        .filter-badge {
            min-width: 20px; height: 20px; padding: 0 .35rem; border-radius: 999px; background: #16a34a;
            color: #fff; font-size: .7rem; font-weight: 800; display: flex; align-items: center; justify-content: center;
        }

        /* Autocompletar */
        .autocomplete-list {
            position: absolute; top: calc(100% + 6px); left: 0; right: 0; background: #fff; border: 1px solid #d1d5db;
            border-radius: 10px; box-shadow: 0 8px 24px rgba(0, 0, 0, .1); max-height: 280px; overflow-y: auto;
            z-index: 30; text-align: left;
        }

        .autocomplete-item { padding: .6rem .9rem; font-size: .85rem; cursor: pointer; display: flex; align-items: baseline; gap: .35rem; }
        .autocomplete-item strong { color: #16a34a; }
        .autocomplete-item:hover { background: #f0fdf4; }
        .autocomplete-item + .autocomplete-item { border-top: 1px solid #f3f4f6; }

        .filtro-label { font-size: .72rem; font-weight: 700; letter-spacing: .3px; text-transform: uppercase; color: #9ca3af; margin: 0 0 .4rem; }

        /* Chips de filtros activos */
        .chips { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem; margin-bottom: .9rem; }

        .chip {
            display: inline-flex; align-items: center; gap: .4rem; padding: .25rem .35rem .25rem .7rem; border-radius: 999px;
            background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; font-size: .76rem; font-weight: 700;
        }

        .chip button {
            border: none; background: rgba(21, 128, 61, .12); color: #15803d; width: 18px; height: 18px;
            border-radius: 50%; font-size: .65rem; cursor: pointer; line-height: 1;
        }

        .chip-clear { border: none; background: transparent; color: #6b7280; font-size: .76rem; font-weight: 700; cursor: pointer; text-decoration: underline; }

        .swatch-inline { width: 16px; height: 16px; border-radius: 50%; border: 1px solid rgba(0, 0, 0, .12); flex-shrink: 0; }

        .contador { font-size: .78rem; color: #9ca3af; margin-bottom: 1rem; }

        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 1.1rem; }

        .prod-card {
            background: #fff; border-radius: 16px; box-shadow: 0 4px 20px rgba(0, 0, 0, .06);
            border: 1px solid #eef0f2; overflow: hidden; display: flex; flex-direction: column;
        }

        .prod-head { display: flex; gap: 1rem; padding: 1.1rem 1.2rem .8rem; }

        .prod-thumb {
            width: 68px; height: 68px; border-radius: 10px; object-fit: cover; border: 1px solid #eef0f2;
            flex-shrink: 0; background: #f9fafb; cursor: zoom-in; transition: opacity .15s ease;
        }

        .prod-thumb:hover { opacity: .85; }
        .prod-thumb.no-zoom { cursor: default; opacity: 1; }

        .prod-info { min-width: 0; flex: 1; cursor: pointer; }

        .prod-ref { font-size: .72rem; font-weight: 700; letter-spacing: .3px; color: #16a34a; text-transform: uppercase; }
        .prod-nombre { font-size: 1rem; font-weight: 700; line-height: 1.25; margin-top: .1rem; }
        .prod-meta { font-size: .78rem; color: #6b7280; margin-top: .15rem; }
        .prod-precio { font-size: 1.1rem; font-weight: 700; color: #000; margin-top: .25rem; }

        .prod-total { text-align: right; flex-shrink: 0; }
        .prod-total-num { font-size: 1.3rem; font-weight: 800; }
        .prod-total-label { font-size: .68rem; color: #9ca3af; }

        .color-tabs { display: flex; gap: .5rem; flex-wrap: wrap; padding: 0 1.2rem .7rem; }

        .color-tab {
            display: flex; align-items: center; gap: .4rem; padding: .3rem .6rem .3rem .3rem; border-radius: 999px;
            border: 1.5px solid #d1d5db; background: #fff; cursor: pointer; font-size: .78rem; font-weight: 600;
            color: #4b5563; position: relative;
        }

        .color-tab.activo { border-color: #16a34a; background: #f0fdf4; color: #15803d; }
        .color-tab.coincide { border-color: #d97706; box-shadow: 0 0 0 1px #d97706 inset; }

        .color-dot { width: 16px; height: 16px; border-radius: 50%; border: 1px solid rgba(0, 0, 0, .12); flex-shrink: 0; position: relative; }

        .color-dot.agotado::after {
            content: ''; position: absolute; inset: 0; margin: auto; top: 50%; width: 140%; height: 1.5px;
            background: #dc2626; transform: rotate(45deg);
        }

        .tallas-row { display: flex; gap: .4rem; flex-wrap: wrap; padding: 0 1.2rem .9rem; }

        .talla-chip { display: flex; flex-direction: column; align-items: center; min-width: 38px; padding: .35rem .3rem .4rem; border-radius: 8px; border: 1px solid #eef0f2; }
        .talla-num { font-weight: 700; font-size: .78rem; }
        .talla-stock { font-size: .68rem; font-weight: 700; margin-top: .1rem; }

        .prod-footer {
            border-top: 1px solid #eef0f2; background: #f9fafb; padding: .7rem 1.2rem; font-size: .74rem; color: #9ca3af;
            display: flex; justify-content: space-between; align-items: center; margin-top: auto;
        }

        .suggest-box { border-top: 1px solid #fde68a; background: #fffbeb; padding: .85rem 1.2rem 1rem; }
        .suggest-title { display: flex; align-items: center; gap: .4rem; font-size: .76rem; font-weight: 700; color: #92400e; margin-bottom: .55rem; }

        .suggest-item {
            display: flex; align-items: center; gap: .6rem; background: #fff; border: 1px solid #fde68a;
            border-radius: 10px; padding: .45rem .7rem; margin-bottom: .4rem; font-size: .78rem;
        }

        .suggest-item:last-child { margin-bottom: 0; }

        .suggest-dot { width: 14px; height: 14px; border-radius: 50%; border: 1px solid rgba(0, 0, 0, .12); flex-shrink: 0; }
        .suggest-name { font-weight: 700; }
        .suggest-reason { font-size: .7rem; color: #6b7280; }
        .suggest-stock { margin-left: auto; font-weight: 700; color: #16a34a; }

        .empty-state { text-align: center; padding: 3rem 1rem; color: #9ca3af; font-size: .9rem; }
        .empty-suggest-wrap { margin-top: 1.25rem; text-align: left; max-width: 520px; margin-left: auto; margin-right: auto; }

        /* ---------------------------------------------------------- */
        /* Estados de carga / error                                   */
        /* ---------------------------------------------------------- */

        .status-box { display: flex; align-items: center; gap: .6rem; padding: .9rem 1.1rem; border-radius: 12px; font-size: .85rem; font-weight: 600; margin-bottom: 1.1rem; }
        .status-box.cargando { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .status-box.error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }

        .spinner { width: 16px; height: 16px; border-radius: 50%; border: 2px solid rgba(29, 78, 216, .25); border-top-color: #1d4ed8; animation: spin .7s linear infinite; flex-shrink: 0; }

        @keyframes spin { to { transform: rotate(360deg); } }

        .retry-btn { margin-left: auto; border: none; background: #b91c1c; color: #fff; padding: .35rem .8rem; border-radius: 8px; font-size: .78rem; font-weight: 700; cursor: pointer; }

        /* ---------------------------------------------------------- */
        /* Modal de filtros                                           */
        /* ---------------------------------------------------------- */

        .modal-overlay {
            position: fixed; inset: 0; background: rgba(17, 24, 39, .55); z-index: 90;
            display: flex; align-items: center; justify-content: center; padding: 1rem;
        }

        .modal-box {
            background: #fff; border-radius: 16px; width: 100%; max-width: 540px; max-height: 90vh;
            display: flex; flex-direction: column; box-shadow: 0 20px 50px rgba(0, 0, 0, .25);
        }

        .modal-header { display: flex; align-items: center; justify-content: space-between; padding: 1.1rem 1.25rem; border-bottom: 1px solid #eef0f2; }
        .modal-title { font-size: 1.05rem; font-weight: 700; margin: 0; }

        .modal-close {
            border: none; background: #f3f4f6; color: #4b5563; width: 32px; height: 32px;
            border-radius: 999px; font-size: 1rem; cursor: pointer; line-height: 1;
        }

        .modal-close:hover { background: #e5e7eb; }

        .modal-body { padding: 1.25rem; overflow-y: auto; display: grid; grid-template-columns: 1fr 1fr; gap: 1rem 1rem; }
        .modal-body .filtro-bloque.full { grid-column: 1 / -1; }

        .modal-footer { display: flex; gap: .6rem; justify-content: space-between; padding: 1rem 1.25rem; border-top: 1px solid #eef0f2; background: #f9fafb; border-radius: 0 0 16px 16px; }

        .btn { border-radius: 10px; padding: .65rem 1.1rem; font-size: .85rem; font-weight: 700; cursor: pointer; border: 1px solid transparent; }
        .btn-ghost { background: #fff; border-color: #d1d5db; color: #4b5563; }
        .btn-ghost:hover { background: #f3f4f6; }
        .btn-primary { background: #16a34a; color: #fff; flex: 1; }
        .btn-primary:hover { background: #15803d; }

        /* ---------------------------------------------------------- */
        /* Lightbox                                                   */
        /* ---------------------------------------------------------- */

        .lightbox-overlay { position: fixed; inset: 0; background: rgba(17, 24, 39, .82); display: flex; align-items: center; justify-content: center; z-index: 100; padding: 2rem 1rem; }
        .lightbox-box { max-width: 720px; width: 100%; display: flex; flex-direction: column; align-items: center; }
        .lightbox-header { width: 100%; display: flex; justify-content: space-between; align-items: center; color: #fff; margin-bottom: .85rem; }
        .lightbox-title { font-size: .95rem; font-weight: 700; }
        .lightbox-sub { font-size: .78rem; color: #d1d5db; margin-top: .1rem; }

        .lightbox-close {
            background: rgba(255, 255, 255, .12); border: none; color: #fff; cursor: pointer; width: 34px; height: 34px;
            border-radius: 999px; font-size: 1.1rem; line-height: 1; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }

        .lightbox-close:hover { background: rgba(255, 255, 255, .22); }

        .lightbox-stage { position: relative; width: 100%; display: flex; align-items: center; justify-content: center; }
        .lightbox-stage img { max-width: 100%; max-height: 62vh; object-fit: contain; border-radius: 12px; background: #fff; }

        .lightbox-msg { color: #fff; padding: 2rem; font-size: .9rem; }
        .lightbox-empty { display: flex; flex-direction: column; align-items: center; gap: .75rem; color: #e5e7eb; font-size: .85rem; }
        .lightbox-stage .lightbox-empty img { width: 260px; height: 260px; }

        .lightbox-nav {
            position: absolute; top: 50%; transform: translateY(-50%); background: rgba(255, 255, 255, .92); border: none;
            cursor: pointer; width: 42px; height: 42px; border-radius: 999px; font-size: 1.2rem; color: #1f2937;
            display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 10px rgba(0, 0, 0, .2);
        }

        .lightbox-nav:hover { background: #fff; }
        .lightbox-nav.prev { left: -8px; }
        .lightbox-nav.next { right: -8px; }

        .lightbox-thumbs { display: flex; gap: .5rem; margin-top: 1rem; flex-wrap: wrap; justify-content: center; }
        .lightbox-thumbs img { width: 56px; height: 56px; object-fit: cover; border-radius: 8px; cursor: pointer; border: 2px solid transparent; opacity: .65; }
        .lightbox-thumbs img.activa { border-color: #16a34a; opacity: 1; }

        .lightbox-pager { display: flex; align-items: center; justify-content: center; gap: .75rem; margin-top: .75rem; color: #fff; font-size: .78rem; }
        .lightbox-pager .lightbox-close { width: auto; padding: 0 .8rem; opacity: 1; }
        .lightbox-pager .lightbox-close:disabled { opacity: .4; cursor: not-allowed; }

        /* ---------------------------------------------------------- */
        /* Responsive pantalla 2                                      */
        /* ---------------------------------------------------------- */

        @media (max-width: 600px) {
            .header-inner { padding: .6rem 1rem; }
            .app-subtitle { display: none; }
            .app-title { font-size: 1.05rem; }
            .app-body { padding: 1rem 1rem 2.5rem; }
            .grid { grid-template-columns: 1fr; }
            .filter-btn-text { display: none; }
            .filter-btn { padding: 0 .85rem; }
            .modal-body { grid-template-columns: 1fr; }
        }
    </style>
</head>

<body>

    {{-- ==========================================================
         DATOS DESDE LARAVEL
    =========================================================== --}}

    <script>
        window.WAREHOUSES = @json($warehouses ?? []);
        window.PRODUCTOS_INICIALES = @json($productos ?? []);
        window.COLOR_GROUPS = @json($color_groups ?? []);
        window.INVENTORY_FILTER_URL = "{{ route('siigo.inventory_filter_search') }}";
        window.INVENTORY_FILTER_IMAGES_URL = "{{ route('siigo.inventory_filter_images') }}";
    </script>


    {{-- ==========================================================
         APLICACIÓN ALPINE
    =========================================================== --}}

    <div x-data="inventarioApp()">

        {{-- ======================================================
             PASO 1: SELECCIONAR TIENDA (buscable)
        ======================================================= --}}

        <template x-if="!tienda">
            <div class="store-screen">
                <div class="store-card">

                    <div class="excel-icon" style="margin:0 auto 1rem;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                            <polyline points="9 22 9 12 15 12 15 22" />
                        </svg>
                    </div>

                    <div class="store-title">¿En qué tienda estás?</div>
                    <div class="store-subtitle">Escribe para buscar tu punto de venta y selecciónalo</div>

                    <template x-if="warehouses.length > 0">
                        <div>
                            <div class="store-search-wrap">
                                <span class="icon">🔍</span>
                                <input type="text" class="excel-field-input" x-model="storeQuery" placeholder="Busca tu tienda...">
                            </div>

                            <template x-if="warehousesFiltrados.length > 0">
                                <div class="store-grid">
                                    <template x-for="w in warehousesFiltrados" :key="w.id">
                                        <button type="button" class="store-btn" :disabled="cargando" @click="seleccionarTienda(w)" x-text="w.name"></button>
                                    </template>
                                </div>
                            </template>

                            <template x-if="warehousesFiltrados.length === 0">
                                <div class="store-empty">No encontramos una tienda con ese nombre.</div>
                            </template>
                        </div>
                    </template>

                    <template x-if="warehouses.length === 0">
                        <div class="store-empty">No hay bodegas disponibles desde Siigo en este momento.</div>
                    </template>

                </div>
            </div>
        </template>


        {{-- ======================================================
             PASO 2: INVENTARIO
        ======================================================= --}}

        <template x-if="tienda">
            <div class="app-shell">

                {{-- HEADER STICKY: título + tienda + buscador + botón de filtros --}}

                <div class="app-header">
                    <div class="header-inner">

                        <div class="header-top">
                            <div>
                                <p class="app-title">Inventario disponible</p>
                                <p class="app-subtitle">Disponibilidad por color y talla de cada referencia</p>
                            </div>

                            <button type="button" class="store-pill" @click="cambiarTienda()">
                                <span x-text="tiendaNombre"></span>
                            </button>
                        </div>

                        <div class="toolbar" x-show="!cargando">

                            <div class="search-wrap" @click.outside="refSuggestOpen = false">
                                <span class="icon">🔍</span>

                                <input type="text" class="excel-field-input" x-model="query"
                                    @focus="refSuggestOpen = true" @input="refSuggestOpen = true"
                                    placeholder="Busca por referencia, nombre o color...">

                                <button type="button" class="clear-btn" x-show="query" @click="query = ''; refSuggestOpen = false">✕</button>

                                {{-- Referencias sugeridas mientras se escribe --}}
                                <template x-if="refSuggestOpen && query && referenciasSugeridas.length > 0">
                                    <div class="autocomplete-list">
                                        <template x-for="r in referenciasSugeridas" :key="r.referencia">
                                            <div class="autocomplete-item" @click="seleccionarReferencia(r.referencia)">
                                                <strong x-text="r.referencia"></strong>
                                                <span x-text="' · ' + (r.nombre || '')"></span>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>

                            <button type="button" class="filter-btn" :class="{ activo: filtrosActivos > 0 }" @click="filtrosOpen = true">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="4" y1="6" x2="20" y2="6" />
                                    <line x1="7" y1="12" x2="17" y2="12" />
                                    <line x1="10" y1="18" x2="14" y2="18" />
                                </svg>
                                <span class="filter-btn-text">Filtros</span>
                                <span class="filter-badge" x-show="filtrosActivos > 0" x-text="filtrosActivos"></span>
                            </button>

                        </div>

                    </div>
                </div>


                {{-- BODY --}}

                <div class="app-body">

                    {{-- CARGANDO --}}

                    <template x-if="cargando">
                        <div class="status-box cargando">
                            <span class="spinner"></span>
                            <span>Cargando inventario de <span x-text="tiendaNombre"></span>...</span>
                        </div>
                    </template>

                    {{-- ERROR --}}

                    <template x-if="error && !cargando">
                        <div class="status-box error">
                            <span>⚠</span>
                            <span x-text="error"></span>
                            <button type="button" class="retry-btn" @click="cargarInventario()">Reintentar</button>
                        </div>
                    </template>


                    {{-- CONTENIDO --}}

                    <template x-if="!cargando">
                        <div>

                            {{-- CHIPS DE FILTROS ACTIVOS --}}

                            <template x-if="filtrosActivos > 0">
                                <div class="chips">
                                    <template x-if="categoria !== 'Todos'">
                                        <span class="chip"><span x-text="categoria"></span><button type="button" @click="categoria = 'Todos'">✕</button></span>
                                    </template>

                                    <template x-if="macroColor !== 'Todos'">
                                        <span class="chip"><span x-text="'Tono: ' + macroColor"></span><button type="button" @click="macroColor = 'Todos'; colorEspecifico = ''">✕</button></span>
                                    </template>

                                    <template x-if="colorEspecifico">
                                        <span class="chip"><span x-text="'Color: ' + colorEspecifico"></span><button type="button" @click="colorEspecifico = ''">✕</button></span>
                                    </template>

                                    <template x-if="talla">
                                        <span class="chip"><span x-text="'Talla: ' + talla"></span><button type="button" @click="talla = ''">✕</button></span>
                                    </template>

                                    <button type="button" class="chip-clear" @click="limpiarFiltros()">Limpiar todo</button>
                                </div>
                            </template>


                            {{-- CONTADOR --}}

                            <div class="contador">
                                <span x-text="resultados.length"></span>
                                <span x-text="resultados.length === 1 ? 'referencia encontrada' : 'referencias encontradas'"></span>
                                en <span x-text="tiendaNombre"></span>
                            </div>


                            {{-- PRODUCTOS --}}

                            <div class="grid">

                                <template x-for="p in resultados" :key="p.id ?? p.referencia">
                                    <div class="prod-card">

                                        {{-- CABECERA --}}
                                        <div class="prod-head">

                                            <img class="prod-thumb" :class="{ 'no-zoom': !colorActual(p) }"
                                                :src="imagenActual(p)" :alt="p.nombre || p.referencia"
                                                loading="lazy" decoding="async"
                                                x-on:error="$event.target.src = placeholderThumb"
                                                @click="colorActual(p) && abrirLightbox(p, indiceColorActual(p))">

                                            <div class="prod-info" @click="colorActual(p) && abrirLightbox(p, indiceColorActual(p))">
                                                <div class="prod-ref" x-text="p.referencia || ''"></div>
                                                <div class="prod-nombre" x-text="p.nombre || ''"></div>
                                                <div class="prod-meta" x-text="(p.categoria || '') + (p.genero ? ' · ' + p.genero : '')"></div>
                                                <div class="prod-precio" x-text="formatPrecio(p.precio)"></div>
                                            </div>

                                            <div class="prod-total">
                                                <div class="prod-total-num" :style="{ color: totalColorActual(p) > 0 ? '#16a34a' : '#dc2626' }" x-text="totalColorActual(p)"></div>
                                                <div class="prod-total-label">en <span x-text="tiendaNombre"></span></div>
                                            </div>

                                        </div>

                                        {{-- COLORES --}}
                                        <template x-if="p.colores.length > 0">
                                            <div class="color-tabs">
                                                <template x-for="(c, i) in p.colores" :key="c.nombre + '-' + i">
                                                    <button type="button" class="color-tab"
                                                        :class="{ activo: (colorSeleccionado[p.id] ?? 0) === i, coincide: coincideFiltroColor(c) }"
                                                        @click="seleccionarColor(p, i)">
                                                        <span class="color-dot" :class="{ agotado: totalColor(c) === 0 }" :style="{ background: c.hex || '#9CA3AF' }"></span>
                                                        <span x-text="c.nombre || ''"></span>
                                                    </button>
                                                </template>
                                            </div>
                                        </template>

                                        {{-- TALLAS --}}
                                        <template x-if="colorActual(p)">
                                            <div class="tallas-row">
                                                <template x-for="[talla, qty] in Object.entries(colorActual(p).tallas || {})" :key="talla">
                                                    <div class="talla-chip" :style="qty > 0 ? { borderColor: nivel(qty).color, background: nivel(qty).color + '14' } : {}">
                                                        <span class="talla-num" x-text="talla"></span>
                                                        <span class="talla-stock" :style="{ color: qty > 0 ? nivel(qty).color : '#9ca3af' }" x-text="qty"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>

                                        {{-- FOOTER --}}
                                        <div class="prod-footer">
                                            <span>Total referencia: <strong x-text="totalProducto(p)"></strong> und.</span>
                                            <span x-text="p.colores.length + (p.colores.length === 1 ? ' color' : ' colores')"></span>
                                        </div>

                                        {{-- SUGERENCIAS (sin stock en el color elegido) --}}
                                        <template x-if="colorActual(p) && totalColor(colorActual(p)) === 0">
                                            <div class="suggest-box">
                                                <div class="suggest-title">
                                                    ⚠ Sin stock en <span x-text="(colorActual(p).nombre || '').toLowerCase()"></span> — alternativas
                                                </div>

                                                <template x-for="(s, idx) in sugerenciasPara(p, colorActual(p))" :key="idx">
                                                    <div class="suggest-item">
                                                        <span class="suggest-dot" :style="{ background: s.color.hex || '#9CA3AF' }"></span>
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


                                {{-- SIN RESULTADOS: sugerir alternativas --}}

                                <div class="empty-state" x-show="resultados.length === 0" style="grid-column:1 / -1;">

                                    <div>No encontramos nada con esa búsqueda en <span x-text="tiendaNombre"></span>.</div>

                                    <template x-if="sugerenciasVacio.length > 0">
                                        <div class="empty-suggest-wrap">
                                            <p class="filtro-label" style="text-align:center;">Quizás te sirva esto</p>

                                            <template x-for="(s, idx) in sugerenciasVacio" :key="idx">
                                                <div class="suggest-item">
                                                    <span class="suggest-dot" :style="{ background: s.color.hex || '#9CA3AF' }"></span>
                                                    <div style="min-width:0;">
                                                        <div class="suggest-name" x-text="s.producto.referencia + ' · ' + s.producto.nombre"></div>
                                                        <div class="suggest-reason" x-text="(s.color.nombre || '') + ' · ' + (s.producto.categoria || '')"></div>
                                                    </div>
                                                    <span class="suggest-stock" x-text="s.stock + ' und.'"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </template>

                                </div>


                                <template x-if="productoExacto && sugerenciasProducto.length > 0">
                                    <p class="filtro-label" style="grid-column:1 / -1; text-align:center; margin-top:.5rem;">También te puede interesar</p>
                                </template>


                                {{-- TARJETAS "TAMBIÉN TE PUEDE INTERESAR" --}}

                                <template x-for="p in sugerenciasProducto" :key="'sugerido-' + (p.id ?? p.referencia)">
                                    <div class="prod-card" x-init="colorSeleccionado[p.id] = colorSugeridoIndex(p)">

                                        <div class="prod-head">

                                            <img class="prod-thumb" :class="{ 'no-zoom': !colorActual(p) }"
                                                :src="imagenActual(p)" :alt="p.nombre || p.referencia"
                                                loading="lazy" decoding="async"
                                                x-on:error="$event.target.src = placeholderThumb"
                                                @click="colorActual(p) && abrirLightbox(p, indiceColorActual(p))">

                                            <div class="prod-info" @click="colorActual(p) && abrirLightbox(p, indiceColorActual(p))">
                                                <div class="prod-ref" x-text="p.referencia || ''"></div>
                                                <div class="prod-nombre" x-text="p.nombre || ''"></div>
                                                <div class="prod-meta" x-text="(p.categoria || '') + (p.genero ? ' · ' + p.genero : '')"></div>
                                                <div class="prod-precio" x-text="formatPrecio(p.precio)"></div>
                                            </div>

                                            <div class="prod-total">
                                                <div class="prod-total-num" :style="{ color: totalColorActual(p) > 0 ? '#16a34a' : '#dc2626' }" x-text="totalColorActual(p)"></div>
                                                <div class="prod-total-label">en <span x-text="tiendaNombre"></span></div>
                                            </div>

                                        </div>

                                        <template x-if="p.colores.length > 0">
                                            <div class="color-tabs">
                                                <template x-for="(c, i) in p.colores" :key="c.nombre + '-' + i">
                                                    <button type="button" class="color-tab"
                                                        :class="{ activo: (colorSeleccionado[p.id] ?? 0) === i, coincide: coincideFiltroColor(c) }"
                                                        @click="seleccionarColor(p, i)">
                                                        <span class="color-dot" :class="{ agotado: totalColor(c) === 0 }" :style="{ background: c.hex || '#9CA3AF' }"></span>
                                                        <span x-text="c.nombre || ''"></span>
                                                    </button>
                                                </template>
                                            </div>
                                        </template>

                                        <template x-if="colorActual(p)">
                                            <div class="tallas-row">
                                                <template x-for="[talla, qty] in Object.entries(colorActual(p).tallas || {})" :key="talla">
                                                    <div class="talla-chip" :style="qty > 0 ? { borderColor: nivel(qty).color, background: nivel(qty).color + '14' } : {}">
                                                        <span class="talla-num" x-text="talla"></span>
                                                        <span class="talla-stock" :style="{ color: qty > 0 ? nivel(qty).color : '#9ca3af' }" x-text="qty"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>

                                        <div class="prod-footer">
                                            <span>Total referencia: <strong x-text="totalProducto(p)"></strong> und.</span>
                                            <span x-text="p.colores.length + (p.colores.length === 1 ? ' color' : ' colores')"></span>
                                        </div>

                                        <template x-if="colorActual(p) && totalColor(colorActual(p)) === 0">
                                            <div class="suggest-box">
                                                <div class="suggest-title">
                                                    ⚠ Sin stock en <span x-text="(colorActual(p).nombre || '').toLowerCase()"></span> — alternativas
                                                </div>

                                                <template x-for="(s, idx) in sugerenciasPara(p, colorActual(p))" :key="idx">
                                                    <div class="suggest-item">
                                                        <span class="suggest-dot" :style="{ background: s.color.hex || '#9CA3AF' }"></span>
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

                            </div>

                        </div>
                    </template>

                </div>

            </div>
        </template>


        {{-- ======================================================
             MODAL: FILTROS AVANZADOS
        ======================================================= --}}

        <template x-if="filtrosOpen">
            <div class="modal-overlay" @click.self="filtrosOpen = false" @keydown.window.escape="filtrosOpen = false">
                <div class="modal-box">

                    <div class="modal-header">
                        <h3 class="modal-title">Filtros avanzados</h3>
                        <button type="button" class="modal-close" @click="filtrosOpen = false">✕</button>
                    </div>

                    <div class="modal-body">

                        {{-- TIPO DE PRODUCTO --}}
                        <div class="filtro-bloque full">
                            <p class="filtro-label">Tipo de producto</p>
                            <select class="excel-field-select" x-model="categoria">
                                <template x-for="c in categorias" :key="c">
                                    <option :value="c" :selected="c === categoria" x-text="c"></option>
                                </template>
                            </select>
                        </div>

                        {{-- TONO --}}
                        <template x-if="macroCategorias.length > 1">
                            <div class="filtro-bloque">
                                <p class="filtro-label">Tono</p>
                                <select class="excel-field-select" x-model="macroColor" @change="colorEspecifico = ''">
                                    <template x-for="m in macroCategorias" :key="m">
                                        <option :value="m" :selected="m === macroColor" x-text="m"></option>
                                    </template>
                                </select>
                            </div>
                        </template>

                        {{-- COLOR ESPECÍFICO --}}
                        <template x-if="macroColor !== 'Todos' && coloresDelMacro.length > 0">
                            <div class="filtro-bloque">
                                <p class="filtro-label">Color</p>
                                <div style="display:flex;align-items:center;gap:.5rem;">
                                    <span class="swatch-inline" :style="{ background: colorEspecificoHex || '#9CA3AF' }"></span>
                                    <select class="excel-field-select" x-model="colorEspecifico">
                                        <option value="" :selected="!colorEspecifico">Todos</option>
                                        <template x-for="c in coloresDelMacro" :key="c.nombre">
                                            <option :value="c.nombre" :selected="c.nombre === colorEspecifico" x-text="c.nombre"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>
                        </template>

                        {{-- TALLA --}}
                        <template x-if="tallas.length > 0">
                            <div class="filtro-bloque">
                                <p class="filtro-label">Talla</p>
                                <select class="excel-field-select" x-model="talla">
                                    <option value="" :selected="!talla">Todas</option>
                                    <template x-for="t in tallas" :key="t">
                                        <option :value="t" :selected="t === talla" x-text="t"></option>
                                    </template>
                                </select>
                            </div>
                        </template>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-ghost" @click="limpiarFiltros()">Limpiar</button>
                        <button type="button" class="btn btn-primary" @click="filtrosOpen = false"
                            x-text="'Ver ' + resultados.length + (resultados.length === 1 ? ' resultado' : ' resultados')"></button>
                    </div>

                </div>
            </div>
        </template>


        {{-- ======================================================
             LIGHTBOX
        ======================================================= --}}

        <template x-if="lightbox.open">
            <div class="lightbox-overlay" @click.self="cerrarLightbox()" @keydown.window.escape="cerrarLightbox()">
                <div class="lightbox-box">

                    <div class="lightbox-header">
                        <div>
                            <div class="lightbox-title" x-text="lightbox.referencia + ' · ' + lightbox.nombre"></div>
                            <div class="lightbox-sub"
                                x-text="lightbox.total > 0
                                    ? lightbox.colorNombre + ' — foto ' + ((lightbox.pagina - 1) * lightbox.porPagina + lightbox.index + 1) + ' de ' + lightbox.total
                                    : lightbox.colorNombre + ' — sin fotografías'">
                            </div>
                        </div>

                        <button type="button" class="lightbox-close" @click="cerrarLightbox()">✕</button>
                    </div>

                    <div class="lightbox-stage">

                        <button type="button" class="lightbox-nav prev" @click="moverLightbox(-1)" x-show="lightbox.total > 1">‹</button>

                        <template x-if="lightbox.cargando">
                            <div class="lightbox-msg">Cargando fotografías...</div>
                        </template>

                        <template x-if="!lightbox.cargando && lightbox.fotos.length > 0">
                            <img :src="lightbox.fotos[lightbox.index]" :alt="lightbox.nombre" decoding="async"
                                x-on:error="$event.target.src = placeholderGrande">
                        </template>

                        {{-- SIN FOTOS: imagen de "no encontrada" --}}
                        <template x-if="!lightbox.cargando && lightbox.fotos.length === 0">
                            <div class="lightbox-empty">
                                <img :src="placeholderGrande" alt="Imagen no encontrada">
                                <span x-text="lightbox.error || 'No hay fotografías para esta referencia.'"></span>
                            </div>
                        </template>

                        <button type="button" class="lightbox-nav next" @click="moverLightbox(1)" x-show="lightbox.total > 1">›</button>

                    </div>

                    <div class="lightbox-thumbs" x-show="lightbox.total > 1">
                        <template x-for="(foto, fi) in lightbox.fotos" :key="fi">
                            <img :src="foto" :class="{ activa: fi === lightbox.index }"
                                x-on:error="$event.target.src = placeholderThumb"
                                @click="lightbox.index = fi">
                        </template>
                    </div>

                    <div class="lightbox-pager" x-show="lightbox.total > lightbox.porPagina">
                        <button type="button" class="lightbox-close" :disabled="lightbox.pagina <= 1 || lightbox.cargando"
                            @click="cargarPaginaLightbox(lightbox.pagina - 1, lightbox.porPagina - 1)">Anterior</button>

                        <span x-text="'Página ' + lightbox.pagina + ' de ' + lightbox.ultimaPagina"></span>

                        <button type="button" class="lightbox-close" :disabled="lightbox.pagina >= lightbox.ultimaPagina || lightbox.cargando"
                            @click="cargarPaginaLightbox(lightbox.pagina + 1, 0)">Siguiente</button>
                    </div>

                </div>
            </div>
        </template>

    </div>


    {{-- ==========================================================
         JAVASCRIPT / ALPINE
    =========================================================== --}}

    <script>

        /* ----------------------------------------------------------
           Imagen "no encontrada" (SVG embebido, sin archivos extra)
        ----------------------------------------------------------- */

        function crearPlaceholder(conTexto) {

            const icono = '<g fill="none" stroke="#9ca3af" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" '
                + 'transform="translate(70 ' + (conTexto ? 52 : 75) + ')">'
                + '<rect x="0" y="0" width="60" height="50" rx="8"/><circle cx="18" cy="16" r="5"/>'
                + '<path d="M0 40 L20 24 L36 36 L46 28 L60 40"/></g>';

            const texto = conTexto
                ? '<text x="100" y="150" text-anchor="middle" font-family="Segoe UI,Arial,sans-serif" '
                + 'font-size="14" font-weight="600" fill="#9ca3af">Imagen no encontrada</text>'
                : '';

            const svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200">'
                + '<rect width="200" height="200" fill="#f3f4f6"/>' + icono + texto + '</svg>';

            return 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg);

        }

        function inventarioApp() {

            return {

                /* ======================================================
                   DATOS
                ======================================================= */

                warehouses: window.WAREHOUSES || [],
                colorGroups: window.COLOR_GROUPS || [],
                csrfToken: document.querySelector('meta[name="csrf-token"]')?.content || '',
                filterUrl: window.INVENTORY_FILTER_URL || '',
                imagesUrl: window.INVENTORY_FILTER_IMAGES_URL || '',

                placeholderThumb: crearPlaceholder(false),
                placeholderGrande: crearPlaceholder(true),

                productos: [],
                tienda: null,
                tiendaNombre: '',

                storeQuery: '',
                query: '',
                refSuggestOpen: false,

                // Filtros (viven en el modal)
                filtrosOpen: false,
                categoria: 'Todos',
                macroColor: 'Todos',
                colorEspecifico: '',
                talla: '',

                colorSeleccionado: {},
                cargando: false,
                error: null,

                lightbox: {
                    open: false, fotos: [], index: 0, referencia: '', nombre: '', colorNombre: '',
                    pagina: 1, porPagina: 12, total: 0, ultimaPagina: 1, cargando: false, error: null,
                },


                /* ======================================================
                   INIT
                ======================================================= */

                init() {

                    this.$watch('macroColor', () => this.aplicarColorAFiltrados());
                    this.$watch('colorEspecifico', () => this.aplicarColorAFiltrados());

                    // Bloquea el scroll del fondo mientras el modal está abierto
                    this.$watch('filtrosOpen', abierto => {
                        document.body.style.overflow = abierto ? 'hidden' : '';
                    });

                },


                aplicarColorAFiltrados() {

                    if (this.macroColor === 'Todos') return;

                    this.productos.forEach(p => {

                        if (!p || !Array.isArray(p.colores) || p.colores.length === 0) return;

                        // Preferimos un color que coincida y tenga stock; si no, el primero que coincida.
                        let idx = p.colores.findIndex(c => this.coincideFiltroColor(c) && this.totalColor(c) > 0);

                        if (idx === -1) idx = p.colores.findIndex(c => this.coincideFiltroColor(c));

                        if (idx !== -1) this.colorSeleccionado[p.id] = idx;

                    });

                },


                /* ======================================================
                   FILTROS: CONTEO Y LIMPIEZA
                ======================================================= */

                get filtrosActivos() {

                    return (this.categoria !== 'Todos' ? 1 : 0)
                        + (this.macroColor !== 'Todos' ? 1 : 0)
                        + (this.colorEspecifico ? 1 : 0)
                        + (this.talla ? 1 : 0);

                },

                limpiarFiltros() {

                    this.categoria = 'Todos';
                    this.macroColor = 'Todos';
                    this.colorEspecifico = '';
                    this.talla = '';

                },


                /* ======================================================
                   UTILIDADES
                ======================================================= */

                normalizarTexto(t) {

                    return String(t || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toUpperCase().trim();

                },

                // Ej: 75000 -> "$75.000". Sin precio válido muestra "$-".
                formatPrecio(precio) {

                    const valor = Number(precio);

                    if (precio === null || precio === undefined || !Number.isFinite(valor) || valor <= 0) return '$-';

                    return '$' + Math.round(valor).toLocaleString('es-CO', { maximumFractionDigits: 0 });

                },

                coincideFiltroColor(c) {

                    if (!c || this.macroColor === 'Todos') return false;

                    const macroOk = this.normalizarTexto(c.macrocategoria) === this.normalizarTexto(this.macroColor);

                    if (!macroOk) return false;

                    if (!this.colorEspecifico) return true;

                    return this.normalizarTexto(c.nombre) === this.normalizarTexto(this.colorEspecifico);

                },


                /* ======================================================
                   TIENDAS
                ======================================================= */

                get warehousesFiltrados() {

                    const q = this.storeQuery.trim().toLowerCase();

                    if (!q) return this.warehouses;

                    return this.warehouses.filter(w => String(w.name || '').toLowerCase().includes(q));

                },

                resetearEstado() {

                    this.storeQuery = '';
                    this.query = '';
                    this.categoria = 'Todos';
                    this.macroColor = 'Todos';
                    this.colorEspecifico = '';
                    this.talla = '';
                    this.colorSeleccionado = {};
                    this.filtrosOpen = false;

                },

                seleccionarTienda(w) {

                    if (!w) return;

                    this.resetearEstado();
                    this.tienda = w.id;
                    this.tiendaNombre = w.name || '';
                    this.cargarInventario();

                },

                cambiarTienda() {

                    this.resetearEstado();
                    this.tienda = null;
                    this.tiendaNombre = '';
                    this.productos = [];
                    this.error = null;
                    this.lightbox.open = false;
                    this.lightbox.fotos = [];

                },


                /* ======================================================
                   REFERENCIA: AUTOCOMPLETAR
                ======================================================= */

                get referenciasSugeridas() {

                    const q = this.query.trim().toLowerCase();

                    if (!q) return [];

                    const vistos = new Set();
                    const out = [];

                    this.productos.forEach(p => {

                        if (!p) return;

                        const ref = String(p.referencia || '').toLowerCase();
                        const nom = String(p.nombre || '').toLowerCase();

                        if ((ref.includes(q) || nom.includes(q)) && !vistos.has(p.referencia)) {
                            vistos.add(p.referencia);
                            out.push(p);
                        }

                    });

                    return out.slice(0, 8);

                },

                seleccionarReferencia(ref) {

                    this.query = ref;
                    this.refSuggestOpen = false;

                },


                /* ======================================================
                   FILTRO DE COLOR: FAMILIAS Y COLORES DISPONIBLES
                ======================================================= */

                get macroCategorias() {

                    const macros = [...new Set(this.colorGroups.map(g => g.macrocategoria).filter(Boolean))];

                    return ['Todos', ...macros];

                },

                get coloresDelMacro() {

                    if (this.macroColor === 'Todos') return [];

                    const grupo = this.colorGroups.find(
                        g => this.normalizarTexto(g.macrocategoria) === this.normalizarTexto(this.macroColor)
                    );

                    return grupo?.colores || [];

                },

                get colorEspecificoHex() {

                    if (!this.colorEspecifico) return null;

                    const c = this.coloresDelMacro.find(
                        c => this.normalizarTexto(c.nombre) === this.normalizarTexto(this.colorEspecifico)
                    );

                    return c?.hex || null;

                },


                /* ======================================================
                   CARGAR INVENTARIO
                ======================================================= */

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

                        if (!res.ok) throw new Error('HTTP ' + res.status);

                        const data = await res.json();

                        const lista = Array.isArray(data) ? data : (Array.isArray(data.productos) ? data.productos : []);

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


                /* ======================================================
                   NORMALIZAR PRODUCTO
                ======================================================= */

                normalizarProducto(p) {

                    p = p || {};

                    const colores = (Array.isArray(p.colores) ? p.colores : []).map(c => {

                        c = c || {};

                        return {
                            ...c,
                            nombre: c.nombre || '',
                            codigo: c.codigo ?? null,
                            hex: c.hex || '#9CA3AF',
                            macrocategoria: c.macrocategoria || 'OTROS',
                            tallas: c.tallas && typeof c.tallas === 'object' ? c.tallas : {},
                            fotos: Array.isArray(c.fotos) ? c.fotos : [],
                        };

                    });

                    return {
                        ...p,
                        id: p.id ?? p.referencia ?? crypto.randomUUID(),
                        referencia: p.referencia || '',
                        nombre: p.nombre || '',
                        categoria: p.categoria || '',
                        genero: p.genero || '',
                        imagen: p.imagen || '',
                        precio: p.precio ?? null,
                        colores,
                    };

                },


                /* ======================================================
                   COLOR ACTUAL
                ======================================================= */

                colorActual(p) {

                    if (!p || !Array.isArray(p.colores) || p.colores.length === 0) return null;

                    const index = Number(this.colorSeleccionado[p.id] ?? 0);

                    if (Number.isInteger(index) && index >= 0 && index < p.colores.length) return p.colores[index];

                    return p.colores[0] || null;

                },

                indiceColorActual(p) {

                    if (!p || !Array.isArray(p.colores) || p.colores.length === 0) return 0;

                    const index = Number(this.colorSeleccionado[p.id] ?? 0);

                    return Number.isInteger(index) && index >= 0 && index < p.colores.length ? index : 0;

                },

                // Si el producto es justo el buscado, sincroniza el filtro de tono con el color elegido.
                seleccionarColor(p, i) {

                    if (!p) return;

                    this.colorSeleccionado[p.id] = i;

                    const exacto = this.productoExacto;

                    if (exacto && exacto.id === p.id && Array.isArray(p.colores)) {

                        const color = p.colores[i];

                        if (color && color.macrocategoria) {
                            this.macroColor = color.macrocategoria;
                            this.colorEspecifico = '';
                        }

                    }

                },

                // Foto del color actual; si no hay ninguna, la imagen "no encontrada".
                imagenActual(p) {

                    const color = this.colorActual(p);

                    if (color && Array.isArray(color.fotos) && color.fotos[0]) return color.fotos[0];

                    return p?.imagen || this.placeholderThumb;

                },


                /* ======================================================
                   CATEGORÍAS Y TALLAS
                ======================================================= */

                get categorias() {

                    const cats = [...new Set(this.productos.map(p => p.categoria).filter(Boolean))];

                    return ['Todos', ...cats];

                },

                get tallas() {

                    const set = new Set();

                    this.productos.forEach(p => {

                        if (!Array.isArray(p.colores)) return;

                        p.colores.forEach(c => {
                            if (c && c.tallas) Object.keys(c.tallas).forEach(t => set.add(t));
                        });

                    });

                    return [...set].sort((a, b) => a.localeCompare(b, undefined, { numeric: true }));

                },

                // Con talla filtrada importa el stock de esa talla; si no, el total del color.
                stockRelevante(c) {

                    if (!c) return 0;

                    if (this.talla) return Number(c.tallas?.[this.talla]) || 0;

                    return this.totalColor(c);

                },


                /* ======================================================
                   RESULTADOS
                ======================================================= */

                get resultados() {

                    const q = this.query.trim().toLowerCase();

                    return this.productos.filter(p => {

                        // Si lo escrito es exactamente la referencia, siempre se muestra.
                        const esReferenciaExacta = q && this.normalizarTexto(p.referencia) === this.normalizarTexto(q);

                        if (!esReferenciaExacta) {

                            if (this.categoria !== 'Todos' && p.categoria !== this.categoria) return false;

                            // Debe tener AL MENOS un color que coincida (aunque esté agotado).
                            if (this.macroColor !== 'Todos' && !p.colores.some(c => this.coincideFiltroColor(c))) return false;

                            // Debe tener AL MENOS un color (respetando el tono) con stock en la talla elegida.
                            if (this.talla) {

                                const tieneTalla = p.colores.some(c => {

                                    if (this.macroColor !== 'Todos' && !this.coincideFiltroColor(c)) return false;

                                    return (Number(c.tallas?.[this.talla]) || 0) > 0;

                                });

                                if (!tieneTalla) return false;

                            }

                        }

                        if (!q) return true;

                        return String(p.referencia || '').toLowerCase().includes(q)
                            || String(p.nombre || '').toLowerCase().includes(q)
                            || String(p.categoria || '').toLowerCase().includes(q)
                            || p.colores.some(c =>
                                String(c.nombre || '').toLowerCase().includes(q)
                                || String(c.macrocategoria || '').toLowerCase().includes(q)
                            );

                    });

                },

                // Hay texto de búsqueda y el filtro deja exactamente un producto.
                get productoExacto() {

                    if (this.resultados.length !== 1) return null;

                    if (!this.query.trim()) return null;

                    return this.resultados[0];

                },


                /* ======================================================
                   "TAMBIÉN TE PUEDE INTERESAR"
                ======================================================= */

                get familiasSugeridas() {

                    const p = this.productoExacto;

                    if (!p) return [];

                    return this.macroColor !== 'Todos'
                        ? [this.macroColor]
                        : [...new Set((p.colores || []).map(c => c.macrocategoria).filter(Boolean))];

                },

                get sugerenciasProducto() {

                    const p = this.productoExacto;

                    if (!p) return [];

                    const familias = this.familiasSugeridas;
                    const candidatos = [];

                    this.productos.forEach(otro => {

                        if (!otro || otro.id === p.id || otro.categoria !== p.categoria || !Array.isArray(otro.colores)) return;

                        // Mejor color (más stock relevante) que coincida con la familia buscada.
                        let mejorStock = 0;

                        otro.colores.forEach(c => {

                            if (!c) return;

                            const stock = this.stockRelevante(c);

                            if (stock <= 0) return;

                            if (familias.length > 0) {

                                const coincide = familias.some(
                                    f => this.normalizarTexto(f) === this.normalizarTexto(c.macrocategoria)
                                );

                                if (!coincide) return;

                            }

                            if (stock > mejorStock) mejorStock = stock;

                        });

                        if (mejorStock > 0) candidatos.push({ producto: otro, stock: mejorStock });

                    });

                    candidatos.sort((a, b) => b.stock - a.stock);

                    return candidatos.slice(0, 6).map(c => c.producto);

                },

                colorSugeridoIndex(p) {

                    if (!p || !Array.isArray(p.colores) || p.colores.length === 0) return 0;

                    const familias = this.familiasSugeridas;

                    let idx = p.colores.findIndex(c =>
                        c
                        && familias.some(f => this.normalizarTexto(f) === this.normalizarTexto(c.macrocategoria))
                        && this.stockRelevante(c) > 0
                    );

                    if (idx === -1) idx = p.colores.findIndex(c => c && this.stockRelevante(c) > 0);

                    return idx === -1 ? 0 : idx;

                },


                /* ======================================================
                   SUGERENCIAS CUANDO NO HAY RESULTADOS
                ======================================================= */

                get sugerenciasVacio() {

                    if (this.resultados.length > 0) return [];

                    const candidatos = [];

                    this.productos.forEach(p => {

                        if (!p || !Array.isArray(p.colores)) return;

                        if (this.categoria !== 'Todos' && p.categoria !== this.categoria) return;

                        p.colores.forEach(c => {

                            if (!c) return;

                            const stock = this.stockRelevante(c);

                            if (stock <= 0) return;

                            if (this.macroColor !== 'Todos'
                                && this.normalizarTexto(c.macrocategoria) !== this.normalizarTexto(this.macroColor)) return;

                            candidatos.push({ producto: p, color: c, stock });

                        });

                    });

                    candidatos.sort((a, b) => b.stock - a.stock);

                    return candidatos.slice(0, 6);

                },


                /* ======================================================
                   TOTALES
                ======================================================= */

                totalColor(color) {

                    if (!color || !color.tallas || typeof color.tallas !== 'object') return 0;

                    return Object.values(color.tallas).reduce((total, cantidad) => total + (Number(cantidad) || 0), 0);

                },

                totalProducto(p) {

                    if (!p || !Array.isArray(p.colores)) return 0;

                    return p.colores.reduce((total, color) => total + this.totalColor(color), 0);

                },

                // Solo las unidades del color que se está viendo.
                totalColorActual(p) {

                    const c = this.colorActual(p);

                    return c ? this.totalColor(c) : 0;

                },

                nivel(qty) {

                    qty = Number(qty) || 0;

                    if (qty <= 0) return { color: '#dc2626' };

                    if (qty <= 2) return { color: '#d97706' };

                    return { color: '#16a34a' };

                },


                /* ======================================================
                   SUGERENCIAS CUANDO UN COLOR ESTÁ AGOTADO

                   1. Mismo producto, otro color con stock.
                   2. Misma categoría + mismo nombre de color.
                   3. Misma categoría + misma familia de color.
                   4. Si no hay nada: misma categoría, cualquier color.
                ======================================================= */

                sugerenciasPara(producto, colorSeleccionado) {

                    const sugerencias = [];
                    const vistos = new Set();

                    if (!producto || !colorSeleccionado) return sugerencias;

                    const agregar = (p, c, motivo) => {

                        const clave = p.id + '|' + c.nombre;

                        if (vistos.has(clave)) return;

                        vistos.add(clave);
                        sugerencias.push({ producto: p, color: c, motivo });

                    };

                    const categoriaTxt = p => String(p.categoria || '').toLowerCase();


                    // 1. Mismo producto / otros colores
                    if (Array.isArray(producto.colores)) {

                        producto.colores.forEach(c => {

                            if (c && c.nombre !== colorSeleccionado.nombre && this.totalColor(c) > 0) {
                                agregar(producto, c, `Mismo modelo en ${c.nombre}`);
                            }

                        });

                    }


                    // 2. Misma categoría / mismo color exacto
                    this.productos.forEach(p => {

                        if (!p || p.id === producto.id || p.categoria !== producto.categoria || !Array.isArray(p.colores)) return;

                        p.colores.forEach(c => {

                            if (c
                                && this.normalizarTexto(c.nombre) === this.normalizarTexto(colorSeleccionado.nombre)
                                && this.totalColor(c) > 0) {
                                agregar(p, c, `${c.nombre} en ${categoriaTxt(p)}`);
                            }

                        });

                    });


                    // 3. Misma categoría / misma familia de color
                    const familia = colorSeleccionado.macrocategoria || null;

                    if (sugerencias.length < 4 && familia) {

                        this.productos.forEach(p => {

                            if (!p || p.categoria !== producto.categoria || !Array.isArray(p.colores)) return;

                            p.colores.forEach(c => {

                                if (c
                                    && this.normalizarTexto(c.macrocategoria) === this.normalizarTexto(familia)
                                    && this.totalColor(c) > 0) {
                                    agregar(p, c, `Mismo tono (${familia}) en ${categoriaTxt(p)}`);
                                }

                            });

                        });

                    }


                    // 4. Cualquier color de la misma categoría con stock
                    if (sugerencias.length === 0) {

                        this.productos.forEach(p => {

                            if (!p || p.id === producto.id || p.categoria !== producto.categoria || !Array.isArray(p.colores)) return;

                            p.colores.forEach(c => {
                                if (c && this.totalColor(c) > 0) agregar(p, c, `Otra opción en ${categoriaTxt(p)}`);
                            });

                        });

                    }

                    return sugerencias.slice(0, 4);

                },


                /* ======================================================
                   LIGHTBOX
                ======================================================= */

                async abrirLightbox(producto, colorIdx, fotoIdx = 0) {

                    if (!producto || !Array.isArray(producto.colores)) return;

                    const color = producto.colores[colorIdx];

                    if (!color) return;

                    this.lightbox = {
                        open: true, fotos: [], index: 0,
                        referencia: producto.referencia || '',
                        nombre: producto.nombre || '',
                        colorNombre: color.nombre || '',
                        pagina: 1, porPagina: 12, total: 0, ultimaPagina: 1,
                        cargando: false, error: null,
                    };

                    await this.cargarPaginaLightbox(1, fotoIdx);

                },

                async cargarPaginaLightbox(pagina = 1, indiceInicial = 0) {

                    if (!this.lightbox.open || this.lightbox.cargando) return;

                    this.lightbox.cargando = true;
                    this.lightbox.error = null;

                    try {

                        const url = new URL(this.imagesUrl, window.location.origin);
                        url.searchParams.set('referencia', this.lightbox.referencia);
                        url.searchParams.set('color', this.lightbox.colorNombre);
                        url.searchParams.set('page', String(pagina));
                        url.searchParams.set('per_page', String(this.lightbox.porPagina));

                        const response = await fetch(url.toString(), {
                            method: 'GET',
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        });

                        if (!response.ok) throw new Error('No fue posible cargar las fotografías (HTTP ' + response.status + ').');

                        const data = await response.json();

                        this.lightbox.fotos = Array.isArray(data.images) ? data.images.map(image => image.url).filter(Boolean) : [];
                        this.lightbox.pagina = Number(data.page) || 1;
                        this.lightbox.total = Number(data.total) || 0;
                        this.lightbox.ultimaPagina = Number(data.last_page) || 1;

                        this.lightbox.index = this.lightbox.fotos.length
                            ? Math.max(0, Math.min(Number(indiceInicial) || 0, this.lightbox.fotos.length - 1))
                            : 0;

                        if (!this.lightbox.fotos.length) this.lightbox.error = 'No hay fotografías para esta referencia.';

                    } catch (error) {

                        this.lightbox.fotos = [];
                        this.lightbox.error = error.message || 'Ocurrió un error al cargar las fotografías.';

                    } finally {

                        this.lightbox.cargando = false;

                    }

                },

                cerrarLightbox() {

                    this.lightbox.open = false;

                },

                async moverLightbox(delta) {

                    if (this.lightbox.cargando || this.lightbox.fotos.length === 0) return;

                    const nextIndex = this.lightbox.index + delta;

                    if (nextIndex >= 0 && nextIndex < this.lightbox.fotos.length) {
                        this.lightbox.index = nextIndex;
                        return;
                    }

                    if (delta > 0 && this.lightbox.pagina < this.lightbox.ultimaPagina) {
                        await this.cargarPaginaLightbox(this.lightbox.pagina + 1, 0);
                        return;
                    }

                    if (delta < 0 && this.lightbox.pagina > 1) {
                        await this.cargarPaginaLightbox(this.lightbox.pagina - 1, this.lightbox.porPagina - 1);
                    }

                },

            };

        }

    </script>

</body>

</html>
