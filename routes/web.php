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

Route::prefix('/siigo')->group(function () {
    Route::prefix('/export')->group(function () {
        Route::controller(InventorySiigoController::class)->group(function () {
            Route::get('/inventory', 'inventory');
        });
        /*Route::controller(InvoiceSiigoController::class)->group(function () {
            Route::get('/invoice', 'inventory');
        });*/
    });
});

Route::get('/exports/download/{file}', function (string $file) {
    // Validar que solo sean archivos .xlsx del directorio exports
    abort_if(!preg_match('/^[\w\-]+\.xlsx$/', $file), 404);

    $path = storage_path("app/exports/{$file}");
    abort_unless(file_exists($path), 404);

    return response()->download($path);
})->name('exports.download');
