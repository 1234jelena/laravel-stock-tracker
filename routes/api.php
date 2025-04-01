<?php

use App\Http\Controllers\StockPriceController;
use Illuminate\Support\Facades\Route;

Route::get('/stock-prices/{stock}/latest', [StockPriceController::class, 'getLatestPrice']);
Route::get('/stock-prices/latest', [StockPriceController::class, 'getLatestPrices']);
Route::post('/stock-prices/change', [StockPriceController::class, 'calculatePriceChange']);
