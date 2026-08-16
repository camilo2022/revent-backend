<?php

use App\Http\Controllers\Integration\InventorySiigoController;
use App\Http\Controllers\Integration\Invoice360SiigoController;
use App\Http\Controllers\Integration\InvoiceSiigoController;
use App\Http\Controllers\Integration\PurchaseSiigoController;
use App\Http\Controllers\Integration\SiigoController;
use App\Http\Controllers\Integration\MasiveTransferSiigoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/home', function () {
    return view('integration.home');
})->name('home');

Route::get('/siigo/export_inventory', [InventorySiigoController::class, 'export_inventory'])->name('siigo.export_inventory');
Route::post('/siigo/export_inventory_download', [InventorySiigoController::class, 'export_inventory_download'])->name('siigo.export_inventory_download');

Route::get('/siigo/export_invoice', [InvoiceSiigoController::class, 'export_invoice'])->name('siigo.export_invoice');
Route::post('/siigo/export_invoice_download', [InvoiceSiigoController::class, 'export_invoice_download'])->name('siigo.export_invoice_download');

Route::get('/siigo/export_invoice_360', [Invoice360SiigoController::class, 'export_invoice_360'])->name('siigo.export_invoice_360');
Route::post('/siigo/export_invoice_360_download', [Invoice360SiigoController::class, 'export_invoice_360_download'])->name('siigo.export_invoice_360_download');

Route::get('/siigo/export_purchase', [PurchaseSiigoController::class, 'export_purchase'])->name('siigo.export_purchase');
Route::get('/siigo/sync_unico', [SiigoController::class, 'sync'])->name('siigo.sync');

Route::get('/siigo/masive_transfer', [MasiveTransferSiigoController::class, 'masive_transfer'])->name('siigo.masive_transfer');
Route::post('/siigo/masive_transfer_upload', [MasiveTransferSiigoController::class, 'masive_transfer_upload'])->name('siigo.masive_transfer_upload');

Route::get('/exports/download/{file}', function (string $file) {
    abort_if(!preg_match('/^[\w\-]+\.xlsx$/', $file), 404);

    $path = public_path("storage/exports/{$file}");
    abort_unless(file_exists($path), 404);

    return response()->download($path);
})->name('exports.download');
