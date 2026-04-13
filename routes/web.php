<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StockController;

Route::get('/', [StockController::class, 'index'])->name('stocks.index');
Route::get('/stocks', [StockController::class, 'index'])->name('stocks.index');
    