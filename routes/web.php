<?php

use App\Http\Controllers\Integration\InventorySiigoController;
use App\Http\Controllers\Integration\InvoiceSiigoController;
use App\Http\Controllers\Integration\PurchaseSiigoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', function () {
    return view('login');
});

Route::get('/siigo/export_inventory', [InventorySiigoController::class, 'export_inventory']);
Route::get('/siigo/export_invoice', [InvoiceSiigoController::class, 'export_invoice']);
Route::get('/siigo/export_purchase', [PurchaseSiigoController::class, 'export_purchase']);

Route::get('/exports/download/{file}', function (string $file) {
    abort_if(!preg_match('/^[\w\-]+\.xlsx$/', $file), 404);

    $path = public_path("storage/exports/{$file}");
    abort_unless(file_exists($path), 404);

    return response()->download($path);
})->name('exports.download');
