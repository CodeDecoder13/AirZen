<?php

use App\Http\Controllers\Api\SensorReadingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DigestRecipientController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\IotIngestController;
use App\Services\LatestReadingSnapshot;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function (LatestReadingSnapshot $snapshot) {
    return Inertia::render('Welcome', [
        'initialSnapshot' => $snapshot->get(),
    ]);
})->name('home');

Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('notifications', [DigestRecipientController::class, 'index'])->name('notifications.index');
    Route::post('notifications', [DigestRecipientController::class, 'store'])->name('notifications.store');
    Route::delete('notifications/{digestRecipient}', [DigestRecipientController::class, 'destroy'])->name('notifications.destroy');

    Route::get('history', [HistoryController::class, 'index'])->name('history.index');
});

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::get('readings/latest', [SensorReadingController::class, 'latest'])->name('readings.latest');
    Route::get('readings/history', [SensorReadingController::class, 'history'])->name('readings.history');
});

// Powers the live preview card on the public landing page — same read-only
// AQI/sensor snapshot as the authenticated dashboard, no account data in it.
Route::middleware('throttle:60,1')->prefix('api/public')->group(function () {
    Route::get('readings/latest', [SensorReadingController::class, 'latest'])->name('readings.public-latest');
});

// Literal path and query param names are hardcoded into the already-flashed
// ESP32 firmware — do not rename this route or its val1/val2/type1/type2 params.
Route::get('/iot.php', [IotIngestController::class, 'store'])
    ->middleware('throttle:120,1')
    ->name('iot.ingest');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
