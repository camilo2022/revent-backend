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
];
