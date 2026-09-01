<?php

use App\Http\Controllers\Integration\InventorySiigoController;
use App\Http\Controllers\Integration\Invoice360SiigoController;
use App\Http\Controllers\Integration\InvoiceSiigoController;
use App\Http\Controllers\Integration\PurchaseSiigoController;
use App\Http\Controllers\Integration\SiigoController;
use App\Http\Controllers\Integration\MasiveTransferSiigoController;
use App\Http\Controllers\Integration\ProductTraceabilitySiigoController;
use App\Http\Controllers\Integration\MasivePurchaseOrderSiigoController;
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
Route::post('/siigo/export_purchase_download', [PurchaseSiigoController::class, 'export_purchase_download'])->name('siigo.export_purchase_download');

Route::get('/siigo/sync_unico', [SiigoController::class, 'sync'])->name('siigo.sync');

Route::get('/siigo/masive_transfer', [MasiveTransferSiigoController::class, 'masive_transfer'])->name('siigo.masive_transfer');
Route::post('/siigo/masive_transfer_upload', [MasiveTransferSiigoController::class, 'masive_transfer_upload'])->name('siigo.masive_transfer_upload');

Route::get('/siigo/product_traceability', [ProductTraceabilitySiigoController::class, 'product_traceability'])->name('siigo.product_traceability');
Route::post('/siigo/product_traceability_download', [ProductTraceabilitySiigoController::class, 'product_traceability_download'])->name('siigo.product_traceability_download');

Route::get('/siigo/masive_purchase_order', [MasivePurchaseOrderSiigoController::class, 'masive_purchase_order'])->name('siigo.masive_purchase_order');
Route::post('/siigo/masive_purchase_order_upload', [MasivePurchaseOrderSiigoController::class, 'masive_purchase_order_upload'])->name('siigo.masive_purchase_order_upload');
Route::post('/siigo/masive_purchase_order_format', [MasivePurchaseOrderSiigoController::class, 'masive_purchase_order_format'])->name('siigo.masive_purchase_order_format');

Route::get('/exports/download/{file}', function (string $file) {
    abort_if(!preg_match('/^[\w\-]+\.xlsx$/', $file), 404);

    $path = public_path("storage/exports/{$file}");
    abort_unless(file_exists($path), 404);

    return response()->download($path);
})->name('exports.download');

Route::get('/formats/download/{file}', function (string $file) {
    abort_if(!preg_match('/^[\w\-]+\.xlsx$/', $file), 404);

    $path = public_path("storage/formats/{$file}");
    abort_unless(file_exists($path), 404);

    return response()->download($path);
})->name('formats.download');
