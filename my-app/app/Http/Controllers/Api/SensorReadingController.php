<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LatestReadingSnapshot;
use Illuminate\Http\JsonResponse;

final class SensorReadingController extends Controller
{
    public function __construct(
        private readonly LatestReadingSnapshot $snapshot,
    ) {}

    public function latest(): JsonResponse
    {
        return response()->json($this->snapshot->get());
    }
}
