<?php

use App\Http\Controllers\Api\ReadingController;
use Illuminate\Support\Facades\Route;

Route::middleware('device.key')->post('/readings', [ReadingController::class, 'store']);
