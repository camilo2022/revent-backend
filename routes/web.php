<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', function () {
    return view('login');
});

Route::get('/siigo/export_inventory', [App\Http\Controllers\Integration\InventorySiigoController::class, 'export_inventory'])->name('siigo');
//Route::get('/siigo/export_invoice', [App\Http\Controllers\Integration\InvoiceSiigoController::class, 'export_invoice'])->name('siigo');
