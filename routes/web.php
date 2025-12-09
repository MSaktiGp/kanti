<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ForecastingController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application.
| These routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// 1. Redirect Halaman Utama ke Dashboard
Route::get('/', function () {
    return redirect('/dashboard');
});

// 2. Route Dashboard Utama (Carousel & Grid Data)
// File: resources/views/dashboard/index.blade.php
Route::get('/dashboard', function () {
    return view('admin.index');
})->name('dashboard');

// 3. Route Peta Geospasial (Leaflet Map)
// File: resources/views/map/index.blade.php
Route::get('/map', function () {
    return view('admin.peta');
})->name('map');

// 4. Route Smart Matching (Rekomendasi KAD)
// File: resources/views/matching/index.blade.php
Route::get('/matching', function () {
    return view('admin.matching');
})->name('matching');

// 5. Route Proyeksi Inflasi (Forecasting SARIMA)
// File: resources/views/forecasting/index.blade.php
Route::get('/prediksi', [ForecastingController::class, 'index'])->name('forecasting');
