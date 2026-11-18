<?php

return [
    // API de Effi ERP: Bearer con la API key de un usuario (vence a los 360 días).
    'url' => env('EFFI_URL', 'https://effi.com.co/api'),
    'api_key' => env('EFFI_API_KEY'),

    // Bodega de Effi que debe quedar igual a la bodega WEB de Siigo.
    'sucursal' => (int) env('EFFI_SUCURSAL', 1),
    'bodega' => (int) env('EFFI_BODEGA', 1),

    // Bodega WEB en Siigo.
    'siigo_warehouse' => (int) env('EFFI_SIIGO_WAREHOUSE', 31),

    // Dropshipping: los artículos nuevos se crean sin Dropshipping. Los que ya están compartidos y se modifican llevan su misma
    // categoría y subcategoría; si no las tienen, Effi las exige y se envían estas (catálogo en resources/data/effi_dropshipping_categorias.json):
    // 22 = Moda Unisex; subcategoría 3 = Calzado y 2 = Bolsos, Mochilas y Carteras (para los artículos de la categoría Bolso).
    'dropshipping' => [
        'categoria' => (int) env('EFFI_DROPSHIPPING_CATEGORY', 22),
        'subcategoria' => (int) env('EFFI_DROPSHIPPING_SUBCATEGORY', 3),
        'subcategoria_bolso' => (int) env('EFFI_DROPSHIPPING_SUBCATEGORY_BAG', 2),
    ],

    // Interruptor de la sincronización que ESCRIBE en Effi (referencias, artículos y ajustes). Apagado por defecto.
    'sync_enabled' => (bool) env('EFFI_SYNC_ENABLED', false),

    // Si se define, TODOS los correos de la sincronización con Effi van solo a este destinatario (pruebas).
    // Vacío = van al correo de la bodega WEB según BODEGAS.
    'mail_to' => env('EFFI_MAIL_TO'),

    // Cola de la sincronización manual, en segundo plano. En el servidor hay que crear su worker:
    //   php artisan queue:work --queue=sync-siigo-effi --timeout=3600 --tries=1
    'queue' => env('EFFI_SYNC_QUEUE', 'sync-siigo-effi'),

    // Sincronización automática tras una nota de traslado que toca la bodega WEB (necesita también EFFI_SYNC_ENABLED).
    // Si hay decisiones pendientes no sincroniza: avisa por correo a la bodega WEB. Apagada por defecto.
    'auto_sync' => (bool) env('EFFI_AUTO_SYNC', false),
];
