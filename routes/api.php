<?php

use App\Http\Controllers\Api\EarthquakeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('earthquakes')->group(function () {
    Route::get('/', [EarthquakeController::class, 'index']);
    Route::get('/recent', [EarthquakeController::class, 'recent']);
    Route::get('/significant', [EarthquakeController::class, 'significant']);
    Route::get('/{earthquake}', [EarthquakeController::class, 'show']);
    Route::post('/fetch', [EarthquakeController::class, 'fetchData']);
});