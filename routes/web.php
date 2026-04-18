<?php

use App\Http\Controllers\Front\StockController;
use App\Http\Controllers\Front\TradingController;
use App\Http\Controllers\Front\ZerodhaAuthController;
use App\Services\KiteSessionManager;
use Illuminate\Support\Facades\Route;

Route::get('/', function (KiteSessionManager $kiteSessionManager) {
    if ($kiteSessionManager->hasActiveSession()) {
        return redirect()->route('dashboard');
    }

    return view('Front.welcome', [
        'kiteConnected' => false,
        'tokenFilePath' => $kiteSessionManager->getTokenFilePath(),
        'sessionData' => $kiteSessionManager->getSessionData(),
    ]);
})->name('home');

Route::get('/zerodha/login', [ZerodhaAuthController::class, 'redirectToProvider'])->name('zerodha.login');
Route::get('/zerodha/callback', [ZerodhaAuthController::class, 'handleCallback'])->name('zerodha.callback');
Route::post('/zerodha/logout', [ZerodhaAuthController::class, 'logout'])->name('zerodha.logout');

Route::middleware('kite.session')->group(function () {
    Route::get('/dashboard', [StockController::class, 'index'])->name('dashboard');
    Route::get('/stocks', [StockController::class, 'index'])->name('stocks.index');
    Route::get('/symbol/data', [TradingController::class, 'SymbolData'])->name('symbol.data');
    Route::get('/lot-ladder/data', [TradingController::class, 'LotLadderData'])->name('lot.ladder.data');
    Route::get('/positions/data', [TradingController::class, 'PositionsData'])->name('positions.data');
    Route::post('/create/strategy', [TradingController::class, 'CreateStrategy'])->name('create.strategy.store');
});

Route::get('/paper/trading', [StockController::class, 'paperTrading'])->name('paper.trading');
    
