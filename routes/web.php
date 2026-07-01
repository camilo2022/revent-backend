<?php

use App\Http\Controllers\Integration\InventorySiigoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', function () {
    return view('login');
});

Route::get('/siigo/export_inventory', [App\Http\Controllers\Integration\InventorySiigoController::class, 'export_inventory']);
//Route::get('/siigo/export_invoice', [App\Http\Controllers\Integration\InvoiceSiigoController::class, 'export_invoice']);

Route::get('/exports/download/{file}', function (string $file) {
    // Validar que solo sean archivos .xlsx del directorio exports
    abort_if(!preg_match('/^[\w\-]+\.xlsx$/', $file), 404);

    $path = public_path("storage/exports/{$file}");
    abort_unless(file_exists($path), 404);

    return response()->download($path);
})->name('exports.download');
