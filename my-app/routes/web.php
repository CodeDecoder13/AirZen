<?php

use App\Http\Controllers\Api\SensorReadingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IotIngestController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::get('readings/latest', [SensorReadingController::class, 'latest'])->name('readings.latest');
});

// Literal path and query param names are hardcoded into the already-flashed
// ESP32 firmware — do not rename this route or its val1/val2/type1/type2 params.
Route::get('/iot.php', [IotIngestController::class, 'store'])->name('iot.ingest');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
