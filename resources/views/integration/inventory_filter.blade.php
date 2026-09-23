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
        * {
            box-sizing: border-box;
        }

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

        .store-search-wrap {
            position: relative;
            max-width: 420px;
            margin: 0 auto 1.5rem;
        }

        .store-search-wrap input {
            width: 100%;
            padding-left: 2.4rem;
        }

        .store-search-wrap .icon {
            position: absolute;
            left: .85rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
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
        /* Pantalla 2: inventario                                    */
        /* ---------------------------------------------------------- */

        .app-shell {
            min-height: 100vh;
        }

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

        .app-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: #1f2937;
            margin: 0;
        }

        .app-subtitle {
            font-size: .78rem;
            color: #6b7280;
            margin: .1rem 0 0;
        }

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

        .store-pill:hover {
            background: #15803d;
        }

        .app-body {
            padding: 1.5rem 2rem 3rem;
            max-width: 1280px;
            margin: 0 auto;
        }

        .toolbar {
            display: flex;
            gap: .75rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .excel-field-input,
        .excel-field-select {
            padding: 0.65rem 0.85rem;
            font-size: 0.88rem;
            color: #1f2937;
            background: #f9fafb;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            transition: border-color 0.2s ease, background 0.2s ease;
        }

        .excel-field-select {
            cursor: pointer;
        }

        .search-wrap {
            position: relative;
            flex: 1;
            min-width: 240px;
        }

        .search-wrap input {
            width: 100%;
            padding-left: 2.4rem;
        }

        .search-wrap .icon {
            position: absolute;
            left: .85rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
        }

        .search-wrap input:focus,
        .excel-field-select:focus {
            outline: none;
            border-color: #16a34a;
            background: #ffffff;
        }

        .clear-btn {
            position: absolute;
            right: .6rem;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            cursor: pointer;
            color: #9ca3af;
            font-size: 1rem;
        }

        /* =========================
           AUTOCOMPLETAR (referencia / tienda)
        ========================= */

        .autocomplete-list {
            position: absolute;
            top: calc(100% + 6px);
            left: 0;
            right: 0;
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, .1);
            max-height: 280px;
            overflow-y: auto;
            z-index: 30;
            text-align: left;
        }

        .autocomplete-item {
            padding: .6rem .9rem;
            font-size: .85rem;
            cursor: pointer;
            color: #1f2937;
            display: flex;
            align-items: baseline;
            gap: .35rem;
        }

        .autocomplete-item strong {
            color: #16a34a;
        }

        .autocomplete-item:hover {
            background: #f0fdf4;
        }

        .autocomplete-item+.autocomplete-item {
            border-top: 1px solid #f3f4f6;
        }

        .filtro-label {
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .3px;
            text-transform: uppercase;
            color: #9ca3af;
            margin: 0 0 .4rem;
        }

        .filtro-bloque {
            margin-bottom: 1rem;
        }

        .filtros-superiores {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            gap: 0 2rem;
        }

        .filtro-row {
            display: flex;
            gap: .6rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .swatch-inline {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 1px solid rgba(0, 0, 0, .12);
            flex-shrink: 0;
        }

        .contador {
            font-size: .78rem;
            color: #9ca3af;
            margin-bottom: 1rem;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 1.1rem;
        }

        .prod-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            border: 1px solid #eef0f2;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .prod-head {
            display: flex;
            gap: 1rem;
            padding: 1.1rem 1.2rem .8rem;
        }

        .prod-thumb {
            width: 68px;
            height: 68px;
            border-radius: 10px;
            object-fit: cover;
            border: 1px solid #eef0f2;
            flex-shrink: 0;
            background: #f9fafb;
        }

        .prod-ref {
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .3px;
            color: #16a34a;
            text-transform: uppercase;
        }

        .prod-nombre {
            font-size: 1rem;
            font-weight: 700;
            color: #1f2937;
            line-height: 1.25;
            margin-top: .1rem;
        }

        .prod-meta {
            font-size: .78rem;
            color: #6b7280;
            margin-top: .15rem;
        }

        .prod-total {
            text-align: right;
            flex-shrink: 0;
        }

        .prod-total-num {
            font-size: 1.3rem;
            font-weight: 800;
        }

        .prod-total-label {
            font-size: .68rem;
            color: #9ca3af;
        }

        .color-tabs {
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
            padding: 0 1.2rem .7rem;
        }

        .color-tab {
            display: flex;
            align-items: center;
            gap: .4rem;
            padding: .3rem .6rem .3rem .3rem;
            border-radius: 999px;
            border: 1.5px solid #d1d5db;
            background: #fff;
            cursor: pointer;
            font-size: .78rem;
            font-weight: 600;
            color: #4b5563;
            position: relative;
        }

        .color-tab.activo {
            border-color: #16a34a;
            background: #f0fdf4;
            color: #15803d;
        }

        .color-tab.coincide {
            border-color: #d97706;
            box-shadow: 0 0 0 1px #d97706 inset;
        }

        .color-dot {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 1px solid rgba(0, 0, 0, .12);
            flex-shrink: 0;
            position: relative;
        }

        .color-dot.agotado::after {
            content: '';
            position: absolute;
            inset: 0;
            margin: auto;
            top: 50%;
            width: 140%;
            height: 1.5px;
            background: #dc2626;
            transform: rotate(45deg);
        }

        .photo-gallery {
            display: flex;
            gap: .5rem;
            overflow-x: auto;
            padding: 0 1.2rem .8rem;
        }

        .photo-gallery img {
            width: 84px;
            height: 84px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #eef0f2;
            flex-shrink: 0;
        }

        .tallas-row {
            display: flex;
            gap: .4rem;
            flex-wrap: wrap;
            padding: 0 1.2rem .9rem;
        }

        .talla-chip {
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 38px;
            padding: .35rem .3rem .4rem;
            border-radius: 8px;
            border: 1px solid #eef0f2;
        }

        .talla-num {
            font-weight: 700;
            font-size: .78rem;
            color: #1f2937;
        }

        .talla-stock {
            font-size: .68rem;
            font-weight: 700;
            margin-top: .1rem;
        }

        .prod-footer {
            border-top: 1px solid #eef0f2;
            background: #f9fafb;
            padding: .7rem 1.2rem;
            font-size: .74rem;
            color: #9ca3af;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
        }

        .suggest-box {
            border-top: 1px solid #fde68a;
            background: #fffbeb;
            padding: .85rem 1.2rem 1rem;
        }

        .suggest-title {
            display: flex;
            align-items: center;
            gap: .4rem;
            font-size: .76rem;
            font-weight: 700;
            color: #92400e;
            margin-bottom: .55rem;
        }

        .suggest-item {
            display: flex;
            align-items: center;
            gap: .6rem;
            background: #fff;
            border: 1px solid #fde68a;
            border-radius: 10px;
            padding: .45rem .7rem;
            margin-bottom: .4rem;
            font-size: .78rem;
        }

        .suggest-item:last-child {
            margin-bottom: 0;
        }

        .suggest-dot {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            border: 1px solid rgba(0, 0, 0, .12);
            flex-shrink: 0;
        }

        .suggest-name {
            font-weight: 700;
            color: #1f2937;
        }

        .suggest-reason {
            font-size: .7rem;
            color: #6b7280;
        }

        .suggest-stock {
            margin-left: auto;
            font-weight: 700;
            color: #16a34a;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #9ca3af;
            font-size: .9rem;
        }

        .empty-suggest-wrap {
            margin-top: 1.25rem;
            text-align: left;
            max-width: 520px;
            margin-left: auto;
            margin-right: auto;
        }

        .prod-thumb,
        .photo-gallery img {
            cursor: zoom-in;
            transition: opacity .15s ease;
        }

        .prod-thumb:hover,
        .photo-gallery img:hover {
            opacity: .85;
        }

        /* ---------------------------------------------------------- */
        /* Estados de carga / error                                   */
        /* ---------------------------------------------------------- */

        .status-box {
            display: flex;
            align-items: center;
            gap: .6rem;
            padding: .9rem 1.1rem;
            border-radius: 12px;
            font-size: .85rem;
            font-weight: 600;
            margin-bottom: 1.1rem;
        }

        .status-box.cargando {
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
        }

        .status-box.error {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .spinner {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 2px solid rgba(29, 78, 216, .25);
            border-top-color: #1d4ed8;
            animation: spin .7s linear infinite;
            flex-shrink: 0;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .retry-btn {
            margin-left: auto;
            border: none;
            background: #b91c1c;
            color: #fff;
            padding: .35rem .8rem;
            border-radius: 8px;
            font-size: .78rem;
            font-weight: 700;
            cursor: pointer;
        }

        /* ---------------------------------------------------------- */
        /* Lightbox                                                    */
        /* ---------------------------------------------------------- */

        .lightbox-overlay {
            position: fixed;
            inset: 0;
            background: rgba(17, 24, 39, .82);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 100;
            padding: 2rem 1rem;
        }

        .lightbox-box {
            max-width: 720px;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .lightbox-header {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #fff;
            margin-bottom: .85rem;
        }

        .lightbox-title {
            font-size: .95rem;
            font-weight: 700;
        }

        .lightbox-sub {
            font-size: .78rem;
            color: #d1d5db;
            margin-top: .1rem;
        }

        .lightbox-close {
            background: rgba(255, 255, 255, .12);
            border: none;
            color: #fff;
            cursor: pointer;
            width: 34px;
            height: 34px;
            border-radius: 999px;
            font-size: 1.1rem;
            line-height: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .lightbox-close:hover {
            background: rgba(255, 255, 255, .22);
        }

        .lightbox-stage {
            position: relative;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .lightbox-stage img {
            max-width: 100%;
            max-height: 62vh;
            object-fit: contain;
            border-radius: 12px;
            background: #fff;
        }

        .lightbox-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, .92);
            border: none;
            cursor: pointer;
            width: 42px;
            height: 42px;
            border-radius: 999px;
            font-size: 1.2rem;
            color: #1f2937;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .2);
        }

        .lightbox-nav:hover {
            background: #fff;
        }

        .lightbox-nav.prev {
            left: -8px;
        }

        .lightbox-nav.next {
            right: -8px;
        }

        .lightbox-thumbs {
            display: flex;
            gap: .5rem;
            margin-top: 1rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .lightbox-thumbs img {
            width: 56px;
            height: 56px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            border: 2px solid transparent;
            opacity: .65;
        }

        .lightbox-thumbs img.activa {
            border-color: #16a34a;
            opacity: 1;
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
        window.COLOR_GROUPS = @json($colorGroups ?? []);
        window.INVENTORY_FILTER_URL = "{{ route('siigo.inventory_filter_search') }}";
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

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="#16a34a"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            width="28"
                            height="28">

                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />

                            <polyline points="9 22 9 12 15 12 15 22" />

                        </svg>

                    </div>

                    <div class="store-title">
                        ¿En qué tienda estás?
                    </div>

                    <div class="store-subtitle">
                        Escribe para buscar tu punto de venta y selecciónalo
                    </div>


                    <template x-if="warehouses.length > 0">

                        <div>

                            <div class="store-search-wrap">

                                <span class="icon">🔍</span>

                                <input
                                    type="text"
                                    class="excel-field-input"
                                    x-model="storeQuery"
                                    placeholder="Busca tu tienda...">

                            </div>


                            <template x-if="warehousesFiltrados.length > 0">

                                <div class="store-grid">

                                    <template
                                        x-for="w in warehousesFiltrados"
                                        :key="w.id">

                                        <button
                                            type="button"
                                            class="store-btn"
                                            :disabled="cargando"
                                            @click="seleccionarTienda(w)"
                                            x-text="w.name">
                                        </button>

                                    </template>

                                </div>

                            </template>


                            <template x-if="warehousesFiltrados.length === 0">

                                <div class="store-empty">
                                    No encontramos una tienda con ese nombre.
                                </div>

                            </template>

                        </div>

                    </template>


                    <template x-if="warehouses.length === 0">

                        <div class="store-empty">
                            No hay bodegas disponibles desde Siigo en este momento.
                        </div>

                    </template>

                </div>

            </div>

        </template>


        {{-- ======================================================
             PASO 2: INVENTARIO
        ======================================================= --}}

        <template x-if="tienda">

            <div class="app-shell">

                {{-- TOPBAR --}}

                <div class="app-topbar">

                    <div>

                        <p class="app-title">
                            Inventario disponible
                        </p>

                        <p class="app-subtitle">
                            Disponibilidad por color y talla de cada referencia
                        </p>

                    </div>


                    <button
                        type="button"
                        class="store-pill"
                        @click="cambiarTienda()">

                        <span x-text="tiendaNombre"></span>

                    </button>

                </div>


                {{-- BODY --}}

                <div class="app-body">


                    {{-- CARGANDO --}}

                    <template x-if="cargando">

                        <div class="status-box cargando">

                            <span class="spinner"></span>

                            <span>
                                Cargando inventario de
                                <span x-text="tiendaNombre"></span>...
                            </span>

                        </div>

                    </template>


                    {{-- ERROR --}}

                    <template x-if="error && !cargando">

                        <div class="status-box error">

                            <span>⚠</span>

                            <span x-text="error"></span>

                            <button
                                type="button"
                                class="retry-btn"
                                @click="cargarInventario()">

                                Reintentar

                            </button>

                        </div>

                    </template>


                    {{-- CONTENIDO --}}

                    <template x-if="!cargando">

                        <div>

                            {{-- TOOLBAR: búsqueda / referencia con autocompletar --}}

                            <div class="toolbar">

                                <div
                                    class="search-wrap"
                                    @click.outside="refSuggestOpen = false">

                                    <span class="icon">
                                        🔍
                                    </span>

                                    <input
                                        type="text"
                                        class="excel-field-input"
                                        x-model="query"
                                        @focus="refSuggestOpen = true"
                                        @input="refSuggestOpen = true"
                                        placeholder="Busca por referencia, nombre o color...">

                                    <button
                                        type="button"
                                        class="clear-btn"
                                        x-show="query"
                                        @click="query = ''; refSuggestOpen = false">

                                        ✕

                                    </button>


                                    {{-- Lista de referencias sugeridas mientras se escribe --}}

                                    <template
                                        x-if="refSuggestOpen && query && referenciasSugeridas.length > 0">

                                        <div class="autocomplete-list">

                                            <template
                                                x-for="r in referenciasSugeridas"
                                                :key="r.referencia">

                                                <div
                                                    class="autocomplete-item"
                                                    @click="seleccionarReferencia(r.referencia)">

                                                    <strong x-text="r.referencia"></strong>

                                                    <span x-text="' · ' + (r.nombre || '')"></span>

                                                </div>

                                            </template>

                                        </div>

                                    </template>

                                </div>

                            </div>


                            {{-- FILTROS SUPERIORES: en pc van uno al lado del otro,
                                 en tablet/celular se acomodan según el ancho --}}

                            <div class="filtros-superiores">

                                {{-- CATEGORÍA DE PRODUCTO (select) --}}

                                <div class="filtro-bloque">

                                    <p class="filtro-label">
                                        Tipo de producto
                                    </p>

                                    <select
                                        class="excel-field-select"
                                        x-model="categoria"
                                        style="min-width:200px;">

                                        <template
                                            x-for="c in categorias"
                                            :key="c">

                                            <option :value="c" x-text="c"></option>

                                        </template>

                                    </select>

                                </div>


                                {{-- TONO: familia de color (select) --}}

                                <template x-if="macroCategorias.length > 1">

                                    <div class="filtro-bloque">

                                        <p class="filtro-label">
                                            Tono
                                        </p>

                                        <select
                                            class="excel-field-select"
                                            x-model="macroColor"
                                            @change="colorEspecifico = ''"
                                            style="min-width:190px;">

                                            <template
                                                x-for="m in macroCategorias"
                                                :key="m">

                                                <option :value="m" x-text="m"></option>

                                            </template>

                                        </select>

                                    </div>

                                </template>


                                {{-- COLOR: color específico dentro del tono elegido (select) --}}

                                <template
                                    x-if="macroColor !== 'Todos' && coloresDelMacro.length > 0">

                                    <div class="filtro-bloque">

                                        <p class="filtro-label">
                                            Color
                                        </p>

                                        <div class="filtro-row" style="gap:.5rem;">

                                            <span
                                                class="swatch-inline"
                                                :style="{ background: colorEspecificoHex || '#9CA3AF' }">
                                            </span>

                                            <select
                                                class="excel-field-select"
                                                x-model="colorEspecifico"
                                                style="min-width:170px;">

                                                <option value="">Todos los tonos</option>

                                                <template
                                                    x-for="c in coloresDelMacro"
                                                    :key="c.nombre">

                                                    <option :value="c.nombre" x-text="c.nombre"></option>

                                                </template>

                                            </select>

                                        </div>

                                    </div>

                                </template>


                                {{-- TALLA (select) --}}

                                <template x-if="tallas.length > 0">

                                    <div class="filtro-bloque">

                                        <p class="filtro-label">
                                            Talla
                                        </p>

                                        <select
                                            class="excel-field-select"
                                            x-model="talla"
                                            style="min-width:130px;">

                                            <option value="">Todas</option>

                                            <template
                                                x-for="t in tallas"
                                                :key="t">

                                                <option :value="t" x-text="t"></option>

                                            </template>

                                        </select>

                                    </div>

                                </template>

                            </div>


                            {{-- CONTADOR --}}

                            <div class="contador">

                                <span x-text="resultados.length"></span>

                                <span
                                    x-text="resultados.length === 1
                                        ? 'referencia encontrada'
                                        : 'referencias encontradas'">
                                </span>

                                en

                                <span x-text="tiendaNombre"></span>

                            </div>


                            {{-- PRODUCTOS --}}

                            <div class="grid">

                                <template
                                    x-for="p in resultados"
                                    :key="p.id ?? p.referencia">

                                    <div class="prod-card">


                                        {{-- ==================================================
                                             CABECERA DEL PRODUCTO
                                        =================================================== --}}

                                        <div class="prod-head">

                                            {{-- IMAGEN --}}

                                            <template x-if="colorActual(p)">

                                                <img
                                                    class="prod-thumb"
                                                    :src="imagenActual(p)"
                                                    :alt="p.nombre || p.referencia"
                                                    @click="abrirLightbox(
                                                        p,
                                                        indiceColorActual(p)
                                                    )">

                                            </template>


                                            {{-- SIN COLORES --}}

                                            <template x-if="!colorActual(p)">

                                                <div
                                                    class="prod-thumb"
                                                    style="
                                                        display:flex;
                                                        align-items:center;
                                                        justify-content:center;
                                                        color:#9ca3af;
                                                        font-size:.7rem;
                                                    ">

                                                    Sin imagen

                                                </div>

                                            </template>


                                            {{-- INFORMACIÓN --}}

                                            <div
                                                style="
                                                    min-width:0;
                                                    flex:1;
                                                    cursor:pointer;
                                                "
                                                @click="
                                                    colorActual(p)
                                                        ? abrirLightbox(
                                                            p,
                                                            indiceColorActual(p)
                                                        )
                                                        : null
                                                ">

                                                <div
                                                    class="prod-ref"
                                                    x-text="p.referencia || ''">
                                                </div>

                                                <div
                                                    class="prod-nombre"
                                                    x-text="p.nombre || ''">
                                                </div>

                                                <div
                                                    class="prod-meta"
                                                    x-text="
                                                        (p.categoria || '') +
                                                        (p.genero ? ' · ' + p.genero : '')
                                                    ">
                                                </div>

                                            </div>


                                            {{-- TOTAL --}}

                                            <div class="prod-total">

                                                <div
                                                    class="prod-total-num"
                                                    :style="{
                                                        color: totalColorActual(p) > 0
                                                            ? '#16a34a'
                                                            : '#dc2626'
                                                    }"
                                                    x-text="totalColorActual(p)">
                                                </div>

                                                <div class="prod-total-label">

                                                    en
                                                    <span x-text="tiendaNombre"></span>

                                                </div>

                                            </div>

                                        </div>


                                        {{-- ==================================================
                                             COLORES
                                        =================================================== --}}

                                        <template x-if="p.colores.length > 0">

                                            <div class="color-tabs">

                                                <template
                                                    x-for="(c, i) in p.colores"
                                                    :key="c.nombre + '-' + i">

                                                    <button
                                                        type="button"
                                                        class="color-tab"
                                                        :class="{
                                                            activo:
                                                                (colorSeleccionado[p.id] ?? 0) === i,
                                                            coincide: coincideFiltroColor(c)
                                                        }"
                                                        @click="seleccionarColor(p, i)">

                                                        <span
                                                            class="color-dot"
                                                            :class="{
                                                                agotado: totalColor(c) === 0
                                                            }"
                                                            :style="{
                                                                background: c.hex || '#9CA3AF'
                                                            }">
                                                        </span>

                                                        <span
                                                            x-text="c.nombre || ''">
                                                        </span>

                                                    </button>

                                                </template>

                                            </div>

                                        </template>


                                        {{-- ==================================================
                                             GALERÍA
                                        =================================================== --}}

                                        <template
                                            x-if="colorActual(p) && colorActual(p).fotos.length > 0">

                                            <div class="photo-gallery">

                                                <template
                                                    x-for="(foto, fi) in colorActual(p).fotos"
                                                    :key="fi">

                                                    <img
                                                        :src="foto"
                                                        :alt="
                                                            (p.nombre || '') +
                                                            ' foto ' +
                                                            (fi + 1)
                                                        "
                                                        @click="
                                                            abrirLightbox(
                                                                p,
                                                                indiceColorActual(p),
                                                                fi
                                                            )
                                                        ">

                                                </template>

                                            </div>

                                        </template>


                                        {{-- ==================================================
                                             TALLAS
                                        =================================================== --}}

                                        <template x-if="colorActual(p)">

                                            <div class="tallas-row">

                                                <template
                                                    x-for="
                                                        [talla, qty]
                                                        in Object.entries(
                                                            colorActual(p).tallas || {}
                                                        )
                                                    "
                                                    :key="talla">

                                                    <div
                                                        class="talla-chip"
                                                        :style="
                                                            qty > 0
                                                                ? {
                                                                    borderColor: nivel(qty).color,
                                                                    background:
                                                                        nivel(qty).color + '14'
                                                                }
                                                                : {}
                                                        ">

                                                        <span
                                                            class="talla-num"
                                                            x-text="talla">
                                                        </span>

                                                        <span
                                                            class="talla-stock"
                                                            :style="{
                                                                color: qty > 0
                                                                    ? nivel(qty).color
                                                                    : '#9ca3af'
                                                            }"
                                                            x-text="qty">
                                                        </span>

                                                    </div>

                                                </template>

                                            </div>

                                        </template>


                                        {{-- ==================================================
                                             FOOTER
                                        =================================================== --}}

                                        <div class="prod-footer">

                                            <span>

                                                Total referencia:

                                                <strong
                                                    x-text="totalProducto(p)">
                                                </strong>

                                                und.

                                            </span>

                                            <span
                                                x-text="
                                                    p.colores.length +
                                                    (p.colores.length === 1
                                                        ? ' color'
                                                        : ' colores')
                                                ">
                                            </span>

                                        </div>


                                        {{-- ==================================================
                                             SUGERENCIAS (mismo producto sin stock en el color elegido)
                                        =================================================== --}}

                                        <template
                                            x-if="
                                                colorActual(p) &&
                                                totalColor(colorActual(p)) === 0
                                            ">

                                            <div class="suggest-box">

                                                <div class="suggest-title">

                                                    ⚠ Sin stock en

                                                    <span
                                                        x-text="
                                                            (colorActual(p).nombre || '')
                                                                .toLowerCase()
                                                        ">
                                                    </span>

                                                    — alternativas

                                                </div>


                                                <template
                                                    x-for="
                                                        (s, idx) in sugerenciasPara(
                                                            p,
                                                            colorActual(p)
                                                        )
                                                    "
                                                    :key="idx">

                                                    <div class="suggest-item">

                                                        <span
                                                            class="suggest-dot"
                                                            :style="{
                                                                background:
                                                                    s.color.hex ||
                                                                    '#9CA3AF'
                                                            }">
                                                        </span>


                                                        <div style="min-width:0;">

                                                            <div
                                                                class="suggest-name"
                                                                x-text="
                                                                    s.producto.referencia +
                                                                    ' · ' +
                                                                    s.producto.nombre
                                                                ">
                                                            </div>

                                                            <div
                                                                class="suggest-reason"
                                                                x-text="s.motivo">
                                                            </div>

                                                        </div>


                                                        <span
                                                            class="suggest-stock"
                                                            x-text="
                                                                totalColor(s.color) +
                                                                ' und.'
                                                            ">
                                                        </span>

                                                    </div>

                                                </template>

                                            </div>

                                        </template>

                                    </div>

                                </template>


                                {{-- SIN RESULTADOS: producto/color buscado no existe → sugerir alternativas --}}

                                <div
                                    class="empty-state"
                                    x-show="resultados.length === 0"
                                    style="grid-column:1 / -1;">

                                    <div>

                                        No encontramos nada con esa búsqueda en

                                        <span x-text="tiendaNombre"></span>.

                                    </div>


                                    <template x-if="sugerenciasVacio.length > 0">

                                        <div class="empty-suggest-wrap">

                                            <p class="filtro-label" style="text-align:center;">
                                                Quizás te sirva esto
                                            </p>

                                            <template
                                                x-for="(s, idx) in sugerenciasVacio"
                                                :key="idx">

                                                <div class="suggest-item">

                                                    <span
                                                        class="suggest-dot"
                                                        :style="{
                                                            background:
                                                                s.color.hex ||
                                                                '#9CA3AF'
                                                        }">
                                                    </span>

                                                    <div style="min-width:0;">

                                                        <div
                                                            class="suggest-name"
                                                            x-text="
                                                                s.producto.referencia +
                                                                ' · ' +
                                                                s.producto.nombre
                                                            ">
                                                        </div>

                                                        <div
                                                            class="suggest-reason"
                                                            x-text="
                                                                (s.color.nombre || '') +
                                                                ' · ' +
                                                                (s.producto.categoria || '')
                                                            ">
                                                        </div>

                                                    </div>

                                                    <span
                                                        class="suggest-stock"
                                                        x-text="s.stock + ' und.'">
                                                    </span>

                                                </div>

                                            </template>

                                        </div>

                                    </template>

                                </div>


                                <template
                                    x-if="productoExacto && sugerenciasProducto.length > 0">

                                    <p
                                        class="filtro-label"
                                        style="grid-column:1 / -1; text-align:center; margin-top:.5rem;">

                                        También te puede interesar

                                    </p>

                                </template>


                                <template
                                    x-for="p in sugerenciasProducto"
                                    :key="'sugerido-' + (p.id ?? p.referencia)">

                                    <div class="prod-card" x-init="colorSeleccionado[p.id] = colorSugeridoIndex(p)">


                                        {{-- ==================================================
                                             CABECERA DEL PRODUCTO
                                        =================================================== --}}

                                        <div class="prod-head">

                                            {{-- IMAGEN --}}

                                            <template x-if="colorActual(p)">

                                                <img
                                                    class="prod-thumb"
                                                    :src="imagenActual(p)"
                                                    :alt="p.nombre || p.referencia"
                                                    @click="abrirLightbox(
                                                        p,
                                                        indiceColorActual(p)
                                                    )">

                                            </template>


                                            {{-- SIN COLORES --}}

                                            <template x-if="!colorActual(p)">

                                                <div
                                                    class="prod-thumb"
                                                    style="
                                                        display:flex;
                                                        align-items:center;
                                                        justify-content:center;
                                                        color:#9ca3af;
                                                        font-size:.7rem;
                                                    ">

                                                    Sin imagen

                                                </div>

                                            </template>


                                            {{-- INFORMACIÓN --}}

                                            <div
                                                style="
                                                    min-width:0;
                                                    flex:1;
                                                    cursor:pointer;
                                                "
                                                @click="
                                                    colorActual(p)
                                                        ? abrirLightbox(
                                                            p,
                                                            indiceColorActual(p)
                                                        )
                                                        : null
                                                ">

                                                <div
                                                    class="prod-ref"
                                                    x-text="p.referencia || ''">
                                                </div>

                                                <div
                                                    class="prod-nombre"
                                                    x-text="p.nombre || ''">
                                                </div>

                                                <div
                                                    class="prod-meta"
                                                    x-text="
                                                        (p.categoria || '') +
                                                        (p.genero ? ' · ' + p.genero : '')
                                                    ">
                                                </div>

                                            </div>


                                            {{-- TOTAL --}}

                                            <div class="prod-total">

                                                <div
                                                    class="prod-total-num"
                                                    :style="{
                                                        color: totalColorActual(p) > 0
                                                            ? '#16a34a'
                                                            : '#dc2626'
                                                    }"
                                                    x-text="totalColorActual(p)">
                                                </div>

                                                <div class="prod-total-label">

                                                    en
                                                    <span x-text="tiendaNombre"></span>

                                                </div>

                                            </div>

                                        </div>


                                        {{-- ==================================================
                                             COLORES
                                        =================================================== --}}

                                        <template x-if="p.colores.length > 0">

                                            <div class="color-tabs">

                                                <template
                                                    x-for="(c, i) in p.colores"
                                                    :key="c.nombre + '-' + i">

                                                    <button
                                                        type="button"
                                                        class="color-tab"
                                                        :class="{
                                                            activo:
                                                                (colorSeleccionado[p.id] ?? 0) === i,
                                                            coincide: coincideFiltroColor(c)
                                                        }"
                                                        @click="seleccionarColor(p, i)">

                                                        <span
                                                            class="color-dot"
                                                            :class="{
                                                                agotado: totalColor(c) === 0
                                                            }"
                                                            :style="{
                                                                background: c.hex || '#9CA3AF'
                                                            }">
                                                        </span>

                                                        <span
                                                            x-text="c.nombre || ''">
                                                        </span>

                                                    </button>

                                                </template>

                                            </div>

                                        </template>


                                        {{-- ==================================================
                                             GALERÍA
                                        =================================================== --}}

                                        <template
                                            x-if="colorActual(p) && colorActual(p).fotos.length > 0">

                                            <div class="photo-gallery">

                                                <template
                                                    x-for="(foto, fi) in colorActual(p).fotos"
                                                    :key="fi">

                                                    <img
                                                        :src="foto"
                                                        :alt="
                                                            (p.nombre || '') +
                                                            ' foto ' +
                                                            (fi + 1)
                                                        "
                                                        @click="
                                                            abrirLightbox(
                                                                p,
                                                                indiceColorActual(p),
                                                                fi
                                                            )
                                                        ">

                                                </template>

                                            </div>

                                        </template>


                                        {{-- ==================================================
                                             TALLAS
                                        =================================================== --}}

                                        <template x-if="colorActual(p)">

                                            <div class="tallas-row">

                                                <template
                                                    x-for="
                                                        [talla, qty]
                                                        in Object.entries(
                                                            colorActual(p).tallas || {}
                                                        )
                                                    "
                                                    :key="talla">

                                                    <div
                                                        class="talla-chip"
                                                        :style="
                                                            qty > 0
                                                                ? {
                                                                    borderColor: nivel(qty).color,
                                                                    background:
                                                                        nivel(qty).color + '14'
                                                                }
                                                                : {}
                                                        ">

                                                        <span
                                                            class="talla-num"
                                                            x-text="talla">
                                                        </span>

                                                        <span
                                                            class="talla-stock"
                                                            :style="{
                                                                color: qty > 0
                                                                    ? nivel(qty).color
                                                                    : '#9ca3af'
                                                            }"
                                                            x-text="qty">
                                                        </span>

                                                    </div>

                                                </template>

                                            </div>

                                        </template>


                                        {{-- ==================================================
                                             FOOTER
                                        =================================================== --}}

                                        <div class="prod-footer">

                                            <span>

                                                Total referencia:

                                                <strong
                                                    x-text="totalProducto(p)">
                                                </strong>

                                                und.

                                            </span>

                                            <span
                                                x-text="
                                                    p.colores.length +
                                                    (p.colores.length === 1
                                                        ? ' color'
                                                        : ' colores')
                                                ">
                                            </span>

                                        </div>


                                        {{-- ==================================================
                                             SUGERENCIAS (mismo producto sin stock en el color elegido)
                                        =================================================== --}}

                                        <template
                                            x-if="
                                                colorActual(p) &&
                                                totalColor(colorActual(p)) === 0
                                            ">

                                            <div class="suggest-box">

                                                <div class="suggest-title">

                                                    ⚠ Sin stock en

                                                    <span
                                                        x-text="
                                                            (colorActual(p).nombre || '')
                                                                .toLowerCase()
                                                        ">
                                                    </span>

                                                    — alternativas

                                                </div>


                                                <template
                                                    x-for="
                                                        (s, idx) in sugerenciasPara(
                                                            p,
                                                            colorActual(p)
                                                        )
                                                    "
                                                    :key="idx">

                                                    <div class="suggest-item">

                                                        <span
                                                            class="suggest-dot"
                                                            :style="{
                                                                background:
                                                                    s.color.hex ||
                                                                    '#9CA3AF'
                                                            }">
                                                        </span>


                                                        <div style="min-width:0;">

                                                            <div
                                                                class="suggest-name"
                                                                x-text="
                                                                    s.producto.referencia +
                                                                    ' · ' +
                                                                    s.producto.nombre
                                                                ">
                                                            </div>

                                                            <div
                                                                class="suggest-reason"
                                                                x-text="s.motivo">
                                                            </div>

                                                        </div>


                                                        <span
                                                            class="suggest-stock"
                                                            x-text="
                                                                totalColor(s.color) +
                                                                ' und.'
                                                            ">
                                                        </span>

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
             LIGHTBOX
        ======================================================= --}}

        <template x-if="lightbox.open">

            <div
                class="lightbox-overlay"
                @click.self="cerrarLightbox()"
                @keydown.window.escape="cerrarLightbox()">

                <div class="lightbox-box">


                    <div class="lightbox-header">

                        <div>

                            <div
                                class="lightbox-title"
                                x-text="
                                    lightbox.referencia +
                                    ' · ' +
                                    lightbox.nombre
                                ">
                            </div>

                            <div
                                class="lightbox-sub"
                                x-text="
                                    lightbox.colorNombre +
                                    ' — foto ' +
                                    (lightbox.index + 1) +
                                    ' de ' +
                                    lightbox.fotos.length
                                ">
                            </div>

                        </div>


                        <button
                            type="button"
                            class="lightbox-close"
                            @click="cerrarLightbox()">

                            ✕

                        </button>

                    </div>


                    <div class="lightbox-stage">

                        <button
                            type="button"
                            class="lightbox-nav prev"
                            @click="moverLightbox(-1)"
                            x-show="lightbox.fotos.length > 1">

                            ‹

                        </button>


                        <template x-if="lightbox.fotos.length > 0">

                            <img
                                :src="lightbox.fotos[lightbox.index]"
                                :alt="lightbox.nombre">

                        </template>


                        <button
                            type="button"
                            class="lightbox-nav next"
                            @click="moverLightbox(1)"
                            x-show="lightbox.fotos.length > 1">

                            ›

                        </button>

                    </div>


                    <div
                        class="lightbox-thumbs"
                        x-show="lightbox.fotos.length > 1">

                        <template
                            x-for="(foto, fi) in lightbox.fotos"
                            :key="fi">

                            <img
                                :src="foto"
                                :class="{
                                    activa: fi === lightbox.index
                                }"
                                @click="lightbox.index = fi">

                        </template>

                    </div>

                </div>

            </div>

        </template>

    </div>


    {{-- ==========================================================
         JAVASCRIPT / ALPINE
    =========================================================== --}}

    <script>

        function inventarioApp() {

            return {

                /* ======================================================
                   DATOS
                ======================================================= */

                warehouses: window.WAREHOUSES || [],

                colorGroups: window.COLOR_GROUPS || [],

                csrfToken:
                    document.querySelector(
                        'meta[name="csrf-token"]'
                    )?.content || '',

                filterUrl:
                    window.INVENTORY_FILTER_URL || '',

                productos: [],

                tienda: null,

                tiendaNombre: '',

                // Buscador de tienda (pantalla 1)
                storeQuery: '',

                query: '',

                // Se abre/cierra el listado de referencias sugeridas
                refSuggestOpen: false,

                categoria: 'Todos',

                // Filtro de color: familia (macrocategoria) + color específico opcional
                macroColor: 'Todos',

                colorEspecifico: '',

                // Filtro de talla: vacío = todas
                talla: '',

                colorSeleccionado: {},

                cargando: false,

                error: null,


                /* ======================================================
                   LIGHTBOX
                ======================================================= */

                lightbox: {

                    open: false,

                    fotos: [],

                    index: 0,

                    referencia: '',

                    nombre: '',

                    colorNombre: '',

                },


                /* ======================================================
                   INIT
                   Cuando cambia el filtro de color, movemos la pestaña de
                   color activa de cada tarjeta hacia el color que coincide
                   con el filtro (si existe), para que se vea de inmediato.
                ======================================================= */

                init() {

                    this.$watch(
                        'macroColor',
                        () => this.aplicarColorAFiltrados()
                    );

                    this.$watch(
                        'colorEspecifico',
                        () => this.aplicarColorAFiltrados()
                    );

                },


                aplicarColorAFiltrados() {

                    this.productos.forEach(p => {

                        if (
                            !p ||
                            !Array.isArray(p.colores) ||
                            p.colores.length === 0
                        ) {

                            return;

                        }


                        if (this.macroColor === 'Todos') {

                            return;

                        }


                        // Preferimos un color que coincida con el filtro y
                        // que además tenga stock; si no hay con stock,
                        // usamos el primero que coincida.

                        let idx = p.colores.findIndex(
                            c => this.coincideFiltroColor(c) &&
                                this.totalColor(c) > 0
                        );

                        if (idx === -1) {

                            idx = p.colores.findIndex(
                                c => this.coincideFiltroColor(c)
                            );

                        }


                        if (idx !== -1) {

                            this.colorSeleccionado[p.id] = idx;

                        }

                    });

                },


                /* ======================================================
                   NORMALIZAR TEXTO (para comparar colores sin tildes/caja)
                ======================================================= */

                normalizarTexto(t) {

                    return String(t || '')
                        .normalize('NFD')
                        .replace(/[\u0300-\u036f]/g, '')
                        .toUpperCase()
                        .trim();

                },


                /* ======================================================
                   ¿ESTE COLOR COINCIDE CON EL FILTRO ACTIVO?
                ======================================================= */

                coincideFiltroColor(c) {

                    if (!c || this.macroColor === 'Todos') {

                        return false;

                    }


                    const macroOk =
                        this.normalizarTexto(c.macrocategoria) ===
                        this.normalizarTexto(this.macroColor);

                    if (!macroOk) {

                        return false;

                    }


                    if (!this.colorEspecifico) {

                        return true;

                    }


                    return (
                        this.normalizarTexto(c.nombre) ===
                        this.normalizarTexto(this.colorEspecifico)
                    );

                },


                /* ======================================================
                   TIENDAS FILTRADAS (buscador de la pantalla 1)
                ======================================================= */

                get warehousesFiltrados() {

                    const q =
                        this.storeQuery.trim().toLowerCase();

                    if (!q) {

                        return this.warehouses;

                    }


                    return this.warehouses.filter(
                        w => String(w.name || '')
                            .toLowerCase()
                            .includes(q)
                    );

                },


                /* ======================================================
                   SELECCIONAR TIENDA
                ======================================================= */

                seleccionarTienda(w) {

                    if (!w) {
                        return;
                    }

                    this.tienda = w.id;

                    this.tiendaNombre = w.name || '';

                    this.storeQuery = '';

                    this.query = '';

                    this.categoria = 'Todos';

                    this.macroColor = 'Todos';

                    this.colorEspecifico = '';

                    this.talla = '';

                    this.colorSeleccionado = {};

                    this.cargarInventario();

                },


                /* ======================================================
                   CAMBIAR TIENDA
                ======================================================= */

                cambiarTienda() {

                    this.tienda = null;

                    this.tiendaNombre = '';

                    this.productos = [];

                    this.storeQuery = '';

                    this.query = '';

                    this.categoria = 'Todos';

                    this.macroColor = 'Todos';

                    this.colorEspecifico = '';

                    this.talla = '';

                    this.colorSeleccionado = {};

                    this.error = null;

                    this.lightbox = {

                        open: false,

                        fotos: [],

                        index: 0,

                        referencia: '',

                        nombre: '',

                        colorNombre: '',

                    };

                },


                /* ======================================================
                   REFERENCIA: AUTOCOMPLETAR
                ======================================================= */

                get referenciasSugeridas() {

                    const q =
                        this.query.trim().toLowerCase();

                    if (!q) {

                        return [];

                    }


                    const vistos = new Set();

                    const out = [];

                    this.productos.forEach(p => {

                        if (!p) {

                            return;

                        }


                        const ref =
                            String(p.referencia || '').toLowerCase();

                        const nom =
                            String(p.nombre || '').toLowerCase();


                        if (
                            (ref.includes(q) || nom.includes(q)) &&
                            !vistos.has(p.referencia)
                        ) {

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

                    const macros =
                        [
                            ...new Set(
                                this.colorGroups
                                    .map(g => g.macrocategoria)
                                    .filter(Boolean)
                            )
                        ];


                    return [
                        'Todos',
                        ...macros
                    ];

                },


                get coloresDelMacro() {

                    if (this.macroColor === 'Todos') {

                        return [];

                    }


                    const grupo = this.colorGroups.find(
                        g =>
                            this.normalizarTexto(g.macrocategoria) ===
                            this.normalizarTexto(this.macroColor)
                    );


                    return grupo?.colores || [];

                },


                get colorEspecificoHex() {

                    if (!this.colorEspecifico) {

                        return null;

                    }


                    const c = this.coloresDelMacro.find(
                        c =>
                            this.normalizarTexto(c.nombre) ===
                            this.normalizarTexto(this.colorEspecifico)
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

                        const res = await fetch(
                            this.filterUrl,
                            {
                                method: 'POST',

                                headers: {

                                    'Content-Type':
                                        'application/json',

                                    'Accept':
                                        'application/json',

                                    'X-CSRF-TOKEN':
                                        this.csrfToken,

                                    'X-Requested-With':
                                        'XMLHttpRequest',

                                },

                                body: JSON.stringify({

                                    warehouse_id:
                                        this.tienda

                                }),

                            }
                        );


                        if (!res.ok) {

                            throw new Error(
                                'HTTP ' + res.status
                            );

                        }


                        const data =
                            await res.json();


                        const lista =
                            Array.isArray(data)
                                ? data
                                : (
                                    Array.isArray(data.productos)
                                        ? data.productos
                                        : []
                                );


                        this.productos =
                            lista.map(
                                p => this.normalizarProducto(p)
                            );


                        this.colorSeleccionado = {};

                    } catch (e) {

                        console.error(
                            'Error cargando inventario:',
                            e
                        );

                        this.error =
                            'No pudimos cargar el inventario de esta bodega. Intenta de nuevo.';

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


                    let colores =
                        Array.isArray(p.colores)
                            ? p.colores
                            : [];


                    colores =
                        colores.map(c => {

                            c = c || {};


                            return {

                                ...c,

                                nombre:
                                    c.nombre || '',

                                codigo:
                                    c.codigo ?? null,

                                hex:
                                    c.hex || '#9CA3AF',

                                macrocategoria:
                                    c.macrocategoria || 'OTROS',

                                tallas:
                                    c.tallas &&
                                    typeof c.tallas === 'object'
                                        ? c.tallas
                                        : {},

                                fotos:
                                    Array.isArray(c.fotos)
                                        ? c.fotos
                                        : [],

                            };

                        });


                    return {

                        ...p,

                        id:
                            p.id ??
                            p.referencia ??
                            crypto.randomUUID(),

                        referencia:
                            p.referencia || '',

                        nombre:
                            p.nombre || '',

                        categoria:
                            p.categoria || '',

                        genero:
                            p.genero || '',

                        imagen:
                            p.imagen || '',

                        colores:

                            colores,

                    };

                },


                /* ======================================================
                   COLOR ACTUAL

                   ESTE MÉTODO ES EL CAMBIO MÁS IMPORTANTE
                   PARA EVITAR:
                   Cannot read properties of undefined
                ======================================================= */

                colorActual(p) {

                    if (
                        !p ||
                        !Array.isArray(p.colores) ||
                        p.colores.length === 0
                    ) {

                        return null;

                    }


                    const index =
                        Number(
                            this.colorSeleccionado[p.id] ?? 0
                        );


                    if (
                        Number.isInteger(index) &&
                        index >= 0 &&
                        index < p.colores.length
                    ) {

                        return p.colores[index];

                    }


                    return p.colores[0] || null;

                },


                /* ======================================================
                   SELECCIONAR UN COLOR EN LA PESTAÑA DE UNA TARJETA

                   Si el producto es justo el que se está buscando
                   (productoExacto), además de cambiar la pestaña,
                   sincronizamos el filtro de "Tono" con la familia de
                   ese color (y limpiamos "Color" a "Todos"), para que
                   "también te puede interesar" se sombree de inmediato
                   con tonos parecidos al que el cliente está viendo.
                ======================================================= */

                seleccionarColor(p, i) {

                    if (!p) {

                        return;

                    }


                    this.colorSeleccionado[p.id] = i;


                    const exacto = this.productoExacto;

                    if (
                        exacto &&
                        exacto.id === p.id &&
                        Array.isArray(p.colores)
                    ) {

                        const color = p.colores[i];

                        if (color && color.macrocategoria) {

                            this.macroColor =
                                color.macrocategoria;

                            this.colorEspecifico = '';

                        }

                    }

                },


                /* ======================================================
                   ÍNDICE DEL COLOR ACTUAL
                ======================================================= */

                indiceColorActual(p) {

                    if (
                        !p ||
                        !Array.isArray(p.colores) ||
                        p.colores.length === 0
                    ) {

                        return 0;

                    }


                    const index =
                        Number(
                            this.colorSeleccionado[p.id] ?? 0
                        );


                    if (
                        Number.isInteger(index) &&
                        index >= 0 &&
                        index < p.colores.length
                    ) {

                        return index;

                    }


                    return 0;

                },


                /* ======================================================
                   IMAGEN ACTUAL
                ======================================================= */

                imagenActual(p) {

                    const color =
                        this.colorActual(p);


                    if (!color) {

                        return p?.imagen || '';

                    }


                    if (
                        Array.isArray(color.fotos) &&
                        color.fotos.length > 0 &&
                        color.fotos[0]
                    ) {

                        return color.fotos[0];

                    }


                    return p?.imagen || '';

                },


                /* ======================================================
                   CATEGORÍAS DE PRODUCTO
                ======================================================= */

                get categorias() {

                    const cats =
                        [
                            ...new Set(
                                this.productos
                                    .map(
                                        p => p.categoria
                                    )
                                    .filter(Boolean)
                            )
                        ];


                    return [
                        'Todos',
                        ...cats
                    ];

                },


                /* ======================================================
                   TALLAS DISPONIBLES (para el filtro de talla)
                ======================================================= */

                get tallas() {

                    const set = new Set();

                    this.productos.forEach(p => {

                        if (!Array.isArray(p.colores)) {

                            return;

                        }


                        p.colores.forEach(c => {

                            if (!c || !c.tallas) {

                                return;

                            }


                            Object.keys(c.tallas).forEach(
                                t => set.add(t)
                            );

                        });

                    });


                    return [...set].sort(
                        (a, b) =>
                            a.localeCompare(
                                b,
                                undefined,
                                { numeric: true }
                            )
                    );

                },


                /* ======================================================
                   STOCK RELEVANTE DE UN COLOR

                   Si hay una talla filtrada, el stock que importa es el
                   de esa talla puntual (no el total del color); si no
                   hay talla filtrada, se usa el total del color.
                ======================================================= */

                stockRelevante(c) {

                    if (!c) {

                        return 0;

                    }


                    if (this.talla) {

                        return Number(
                            c.tallas?.[this.talla]
                        ) || 0;

                    }


                    return this.totalColor(c);

                },


                /* ======================================================
                   RESULTADOS
                   Aplica: categoría de producto, familia/color de color
                   y texto de búsqueda libre.
                ======================================================= */

                get resultados() {

                    const q =
                        this.query
                            .trim()
                            .toLowerCase();


                    return this.productos.filter(p => {

                        // Si el texto buscado es exactamente la
                        // referencia de este producto, lo mostramos
                        // siempre, sin importar categoría, tono o talla
                        // seleccionados: es justo el producto que el
                        // cliente está buscando.

                        const esReferenciaExacta =
                            q &&
                            this.normalizarTexto(p.referencia) ===
                                this.normalizarTexto(q);


                        if (!esReferenciaExacta) {

                            if (
                                this.categoria !== 'Todos' &&
                                p.categoria !== this.categoria
                            ) {

                                return false;

                            }


                            // Filtro por familia de color / color
                            // específico: el producto debe tener AL
                            // MENOS un color que coincida (no importa si
                            // está agotado, para que el usuario vea
                            // igual el producto y las sugerencias de
                            // alternativas).

                            if (this.macroColor !== 'Todos') {

                                const tieneColor =
                                    p.colores.some(
                                        c => this.coincideFiltroColor(c)
                                    );

                                if (!tieneColor) {

                                    return false;

                                }

                            }


                            // Filtro por talla: el producto debe tener
                            // AL MENOS un color (que además respete el
                            // tono filtrado, si hay uno) con stock en la
                            // talla seleccionada.

                            if (this.talla) {

                                const tieneTalla =
                                    p.colores.some(c => {

                                        if (
                                            this.macroColor !== 'Todos' &&
                                            !this.coincideFiltroColor(c)
                                        ) {

                                            return false;

                                        }


                                        return (
                                            Number(
                                                c.tallas?.[this.talla]
                                            ) || 0
                                        ) > 0;

                                    });

                                if (!tieneTalla) {

                                    return false;

                                }

                            }

                        }


                        if (!q) {

                            return true;

                        }


                        return (

                            String(
                                p.referencia || ''
                            )
                            .toLowerCase()
                            .includes(q)

                            ||

                            String(
                                p.nombre || ''
                            )
                            .toLowerCase()
                            .includes(q)

                            ||

                            String(
                                p.categoria || ''
                            )
                            .toLowerCase()
                            .includes(q)

                            ||

                            p.colores.some(c =>
                                String(
                                    c.nombre || ''
                                )
                                .toLowerCase()
                                .includes(q)

                                ||

                                String(
                                    c.macrocategoria || ''
                                )
                                .toLowerCase()
                                .includes(q)
                            )

                        );

                    });

                },


                /* ======================================================
                   ¿LA BÚSQUEDA APUNTA A UNA SOLA REFERENCIA ESPECÍFICA?

                   Cuando el filtro deja exactamente un producto y hay
                   texto de búsqueda activo (el cliente escribió o eligió
                   una referencia puntual), consideramos que está
                   buscando ESE producto en particular.
                ======================================================= */

                get productoExacto() {

                    if (this.resultados.length !== 1) {

                        return null;

                    }


                    if (!this.query.trim()) {

                        return null;

                    }


                    return this.resultados[0];

                },


                /* ======================================================
                   SIMILARES AL PRODUCTO ENCONTRADO

                   Aunque el producto buscado sí tenga resultado, la
                   talla que el cliente necesita puede no estar en ese
                   color/tienda. Por eso mostramos alternativas de la
                   misma categoría y, si hay un color/familia de color
                   filtrado, de esa misma familia; si no hay filtro de
                   color activo, usamos las familias de color que tiene
                   el propio producto encontrado.
                ======================================================= */

                /* ======================================================
                   FAMILIAS DE COLOR "OBJETIVO" para las sugerencias del
                   producto encontrado: el filtro de color activo, o si
                   no hay filtro, las familias de color que ya tiene el
                   propio producto encontrado.
                ======================================================= */

                get familiasSugeridas() {

                    const p = this.productoExacto;

                    if (!p) {

                        return [];

                    }


                    return this.macroColor !== 'Todos'
                        ? [this.macroColor]
                        : [
                            ...new Set(
                                (p.colores || [])
                                    .map(c => c.macrocategoria)
                                    .filter(Boolean)
                            )
                        ];

                },


                get sugerenciasProducto() {

                    const p = this.productoExacto;

                    if (!p) {

                        return [];

                    }


                    const familias = this.familiasSugeridas;

                    const candidatos = [];

                    this.productos.forEach(otro => {

                        if (
                            !otro ||
                            otro.id === p.id ||
                            otro.categoria !== p.categoria ||
                            !Array.isArray(otro.colores)
                        ) {

                            return;

                        }


                        // Nos quedamos con el mejor color (más stock
                        // relevante: el de la talla filtrada si hay una,
                        // o el total del color si no) de este producto
                        // que coincida con la familia buscada.

                        let mejorStock = 0;

                        otro.colores.forEach(c => {

                            if (!c) {

                                return;

                            }


                            const stock =
                                this.stockRelevante(c);

                            if (stock <= 0) {

                                return;

                            }


                            if (familias.length > 0) {

                                const coincide =
                                    familias.some(
                                        f =>
                                            this.normalizarTexto(f) ===
                                            this.normalizarTexto(c.macrocategoria)
                                    );

                                if (!coincide) {

                                    return;

                                }

                            }


                            if (stock > mejorStock) {

                                mejorStock = stock;

                            }

                        });


                        if (mejorStock > 0) {

                            candidatos.push({
                                producto: otro,
                                stock: mejorStock,
                            });

                        }

                    });


                    candidatos.sort(
                        (a, b) => b.stock - a.stock
                    );


                    return candidatos
                        .slice(0, 6)
                        .map(c => c.producto);

                },


                /* ======================================================
                   ÍNDICE DE COLOR SUGERIDO PARA UNA TARJETA DE "TAMBIÉN
                   TE PUEDE INTERESAR"

                   Elige, dentro de los colores del producto sugerido, el
                   que coincide con la familia buscada y tiene stock
                   relevante (en la talla filtrada si hay una); si no hay
                   ninguno así, cae al primer color con stock, y si
                   tampoco hay, al primero de la lista.
                ======================================================= */

                colorSugeridoIndex(p) {

                    if (
                        !p ||
                        !Array.isArray(p.colores) ||
                        p.colores.length === 0
                    ) {

                        return 0;

                    }


                    const familias = this.familiasSugeridas;


                    let idx = p.colores.findIndex(
                        c =>
                            c &&
                            familias.some(
                                f =>
                                    this.normalizarTexto(f) ===
                                    this.normalizarTexto(c.macrocategoria)
                            ) &&
                            this.stockRelevante(c) > 0
                    );

                    if (idx === -1) {

                        idx = p.colores.findIndex(
                            c => c && this.stockRelevante(c) > 0
                        );

                    }


                    return idx === -1 ? 0 : idx;

                },


                /* ======================================================
                   SUGERENCIAS CUANDO NO HAY RESULTADOS

                   Se usa cuando lo que el cliente busca (referencia y/o
                   color) no aparece en esta tienda. Se buscan productos
                   con stock que respeten la categoría elegida (si hay) y
                   la familia/color de color elegida (si hay), ordenados
                   por cantidad disponible, para ofrecer alternativas
                   parecidas al zapato que el cliente vio en exhibición.
                ======================================================= */

                get sugerenciasVacio() {

                    if (this.resultados.length > 0) {

                        return [];

                    }


                    const candidatos = [];

                    this.productos.forEach(p => {

                        if (
                            !p ||
                            !Array.isArray(p.colores)
                        ) {

                            return;

                        }


                        if (
                            this.categoria !== 'Todos' &&
                            p.categoria !== this.categoria
                        ) {

                            return;

                        }


                        p.colores.forEach(c => {

                            if (!c) {

                                return;

                            }


                            const stock =
                                this.stockRelevante(c);

                            if (stock <= 0) {

                                return;

                            }


                            if (this.macroColor !== 'Todos') {

                                const macroOk =
                                    this.normalizarTexto(c.macrocategoria) ===
                                    this.normalizarTexto(this.macroColor);

                                if (!macroOk) {

                                    return;

                                }

                            }


                            candidatos.push({
                                producto: p,
                                color: c,
                                stock,
                            });

                        });

                    });


                    candidatos.sort(
                        (a, b) => b.stock - a.stock
                    );


                    return candidatos.slice(0, 6);

                },


                /* ======================================================
                   TOTAL COLOR
                ======================================================= */

                totalColor(color) {

                    if (
                        !color ||
                        !color.tallas ||
                        typeof color.tallas !== 'object'
                    ) {

                        return 0;

                    }


                    return Object.values(
                        color.tallas
                    )
                    .reduce(
                        (total, cantidad) =>
                            total + (Number(cantidad) || 0),
                        0
                    );

                },


                /* ======================================================
                   TOTAL PRODUCTO
                ======================================================= */

                totalProducto(p) {

                    if (
                        !p ||
                        !Array.isArray(p.colores)
                    ) {

                        return 0;

                    }


                    return p.colores.reduce(
                        (total, color) =>
                            total + this.totalColor(color),
                        0
                    );

                },


                /* ======================================================
                   TOTAL DEL COLOR ACTUALMENTE SELECCIONADO

                   Usado en el número de arriba a la derecha de cada
                   tarjeta: no es la suma de todos los colores, sino
                   solo las unidades del color que se está viendo en
                   ese momento.
                ======================================================= */

                totalColorActual(p) {

                    const c = this.colorActual(p);

                    return c ? this.totalColor(c) : 0;

                },


                /* ======================================================
                   NIVEL STOCK
                ======================================================= */

                nivel(qty) {

                    qty = Number(qty) || 0;


                    if (qty <= 0) {

                        return {
                            color: '#dc2626'
                        };

                    }


                    if (qty <= 2) {

                        return {
                            color: '#d97706'
                        };

                    }


                    return {
                        color: '#16a34a'
                    };

                },


                /* ======================================================
                   SUGERENCIAS

                   Orden de prioridad al buscar alternativas cuando el
                   color elegido está sin stock:

                   1. Mismo producto, otro color con stock.
                   2. Misma categoría + EXACTAMENTE el mismo nombre de
                      color, con stock.
                   3. Misma categoría + MISMA FAMILIA de color
                      (macrocategoria), con stock. Así, si buscan "Nude"
                      y no hay, se sugiere "Vainilla" o "Arena" antes de
                      mostrar cualquier cosa.
                   4. Si aún no hay nada: misma categoría, cualquier
                      color con stock.
                ======================================================= */

                sugerenciasPara(
                    producto,
                    colorSeleccionado
                ) {

                    const sugerencias = [];

                    const vistos = new Set();


                    if (
                        !producto ||
                        !colorSeleccionado
                    ) {

                        return sugerencias;

                    }


                    const agregar = (p, c, motivo) => {

                        const clave =
                            p.id + '|' + c.nombre;

                        if (vistos.has(clave)) {

                            return;

                        }

                        vistos.add(clave);

                        sugerencias.push({
                            producto: p,
                            color: c,
                            motivo,
                        });

                    };


                    /* -----------------------------------------------
                       1. MISMO PRODUCTO / OTROS COLORES
                    ------------------------------------------------ */

                    if (
                        Array.isArray(producto.colores)
                    ) {

                        producto.colores.forEach(c => {

                            if (
                                c &&
                                c.nombre !==
                                    colorSeleccionado.nombre &&
                                this.totalColor(c) > 0
                            ) {

                                agregar(
                                    producto,
                                    c,
                                    `Mismo modelo en ${c.nombre}`
                                );

                            }

                        });

                    }


                    /* -----------------------------------------------
                       2. MISMA CATEGORÍA / MISMO COLOR EXACTO
                    ------------------------------------------------ */

                    this.productos.forEach(p => {

                        if (
                            !p ||
                            p.id === producto.id ||
                            p.categoria !== producto.categoria ||
                            !Array.isArray(p.colores)
                        ) {

                            return;

                        }


                        p.colores.forEach(c => {

                            if (!c) {
                                return;
                            }


                            if (
                                this.normalizarTexto(c.nombre) ===
                                    this.normalizarTexto(
                                        colorSeleccionado.nombre
                                    ) &&
                                this.totalColor(c) > 0
                            ) {

                                agregar(
                                    p,
                                    c,
                                    `${c.nombre} en ${String(
                                        p.categoria || ''
                                    ).toLowerCase()}`
                                );

                            }

                        });

                    });


                    /* -----------------------------------------------
                       3. MISMA CATEGORÍA / MISMA FAMILIA DE COLOR
                    ------------------------------------------------ */

                    if (sugerencias.length < 4) {

                        const familia =
                            colorSeleccionado.macrocategoria || null;

                        if (familia) {

                            this.productos.forEach(p => {

                                if (
                                    !p ||
                                    p.categoria !== producto.categoria ||
                                    !Array.isArray(p.colores)
                                ) {

                                    return;

                                }


                                p.colores.forEach(c => {

                                    if (
                                        c &&
                                        this.normalizarTexto(
                                            c.macrocategoria
                                        ) ===
                                            this.normalizarTexto(familia) &&
                                        this.totalColor(c) > 0
                                    ) {

                                        agregar(
                                            p,
                                            c,
                                            `Mismo tono (${familia}) en ${String(
                                                p.categoria || ''
                                            ).toLowerCase()}`
                                        );

                                    }

                                });

                            });

                        }

                    }


                    /* -----------------------------------------------
                       4. SI SIGUE SIN HABER NADA: CUALQUIER COLOR
                          DE LA MISMA CATEGORÍA CON STOCK
                    ------------------------------------------------ */

                    if (sugerencias.length === 0) {

                        this.productos.forEach(p => {

                            if (
                                !p ||
                                p.id === producto.id ||
                                p.categoria !== producto.categoria ||
                                !Array.isArray(p.colores)
                            ) {

                                return;

                            }


                            p.colores.forEach(c => {

                                if (
                                    c &&
                                    this.totalColor(c) > 0
                                ) {

                                    agregar(
                                        p,
                                        c,
                                        `Otra opción en ${String(
                                            p.categoria || ''
                                        ).toLowerCase()}`
                                    );

                                }

                            });

                        });

                    }


                    return sugerencias.slice(0, 4);

                },


                /* ======================================================
                   ABRIR LIGHTBOX
                ======================================================= */

                abrirLightbox(
                    producto,
                    colorIdx,
                    fotoIdx = 0
                ) {

                    if (
                        !producto ||
                        !Array.isArray(producto.colores)
                    ) {

                        return;

                    }


                    const color =
                        producto.colores[colorIdx];


                    if (!color) {

                        return;

                    }


                    let fotos = [];


                    if (
                        Array.isArray(color.fotos) &&
                        color.fotos.length > 0
                    ) {

                        fotos =
                            color.fotos.filter(Boolean);

                    }


                    if (
                        fotos.length === 0 &&
                        producto.imagen
                    ) {

                        fotos = [
                            producto.imagen
                        ];

                    }


                    if (fotos.length === 0) {

                        return;

                    }


                    this.lightbox = {

                        open: true,

                        fotos,

                        index:
                            Math.max(
                                0,
                                Math.min(
                                    Number(fotoIdx) || 0,
                                    fotos.length - 1
                                )
                            ),

                        referencia:
                            producto.referencia || '',

                        nombre:
                            producto.nombre || '',

                        colorNombre:
                            color.nombre || '',

                    };

                },


                /* ======================================================
                   CERRAR LIGHTBOX
                ======================================================= */

                cerrarLightbox() {

                    this.lightbox.open = false;

                },


                /* ======================================================
                   MOVER LIGHTBOX
                ======================================================= */

                moverLightbox(delta) {

                    const total =
                        this.lightbox.fotos.length;


                    if (total <= 1) {

                        return;

                    }


                    this.lightbox.index =
                        (
                            this.lightbox.index +
                            delta +
                            total
                        ) % total;

                },

            };

        }

    </script>

</body>

</html>
