<?php

use App\Http\Controllers\Api\ReadingController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::get('readings/latest', [ReadingController::class, 'latest'])->name('readings.latest');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
