{{--
    Menu de navegacion del modulo de integracion con Siigo.
    Fuente: tarjetas de integration/home.blade.php. 'active' son patrones para request()->routeIs().
    Las paginas sin sesion (acceso por correo) definen @section('nav_minimal') para ocultar el menu.
--}}
@php
    $integrationMenu = [
        ['label' => 'Inicio', 'route' => 'home', 'active' => ['home']],
        ['label' => 'Inventario', 'route' => 'siigo.export_inventory', 'active' => ['siigo.export_inventory*']],
        ['label' => 'Facturación', 'route' => 'siigo.export_invoice', 'active' => ['siigo.export_invoice', 'siigo.export_invoice_download']],
        ['label' => 'Facturación 360', 'route' => 'siigo.export_invoice_360', 'active' => ['siigo.export_invoice_360*']],
        ['label' => 'Compras', 'route' => 'siigo.export_purchase', 'active' => ['siigo.export_purchase*']],
        ['label' => 'Traslados masivos', 'route' => 'siigo.masive_transfer', 'active' => ['siigo.masive_transfer*']],
        ['label' => 'Trazabilidad', 'route' => 'siigo.product_traceability', 'active' => ['siigo.product_traceability*']],
        ['label' => 'OC masivas', 'route' => 'siigo.masive_purchase_order', 'active' => ['siigo.masive_purchase_order*']],
        ['label' => 'Fotos de producto', 'route' => 'siigo.product_photo', 'active' => ['siigo.product_photo*']],
        ['label' => 'Cuentas por pagar', 'route' => 'siigo.account_payable_access', 'active' => ['siigo.account_payable*', 'siigo.accounts_*', 'siigo.payment_html']],
        ['label' => 'Consulta de inventario', 'route' => 'siigo.inventory_filter', 'active' => ['siigo.inventory_filter*']],
        ['label' => 'Recepción de OC', 'route' => 'siigo.invoice_purchase_order_reception', 'active' => ['siigo.invoice_purchase_order_reception', 'siigo.invoice_purchase_order_search', 'siigo.invoice_purchase_order_confirmed']],
        ['label' => 'Compras por recepcionar', 'route' => 'siigo.invoice_purchase_order_access', 'active' => ['siigo.invoice_purchase_order', 'siigo.invoice_purchase_order_access', 'siigo.invoice_purchase_order_send_access_link', 'siigo.invoice_purchase_order_documents', 'siigo.invoice_purchase_order_missing_download']],
        ['label' => 'Consulta de documentos', 'route' => 'siigo.search_document', 'active' => ['siigo.search_document*']],
    ];
@endphp
<header class="rv-topbar">
    <div class="rv-topbar-inner">
        <a href="{{ route('home') }}" class="rv-brand">Revent <span>·</span> Siigo</a>
        @hasSection('nav_minimal')
        @else
            <nav class="rv-nav" aria-label="Acciones disponibles">
                @foreach ($integrationMenu as $item)
                    <a href="{{ route($item['route']) }}"
                        class="rv-nav-link {{ request()->routeIs(...$item['active']) ? 'active' : '' }}">{{ $item['label'] }}</a>
                @endforeach
            </nav>
        @endif
    </div>
</header>
