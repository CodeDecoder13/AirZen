<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReadingRequest;
use App\Http\Resources\ReadingResource;
use App\Models\Reading;
use App\Services\AqiCalculator;
use App\Services\RecommendationService;
use Illuminate\Http\JsonResponse;

final class ReadingController extends Controller
{
    public function __construct(
        private readonly AqiCalculator $aqiCalculator,
        private readonly RecommendationService $recommendationService,
    ) {}

    public function store(StoreReadingRequest $request): JsonResponse
    {
        $data = $request->validated();

        $aqi = $this->aqiCalculator->calculateOverallAqi([
            'pm25' => (float) $data['pm25'],
            'co' => (float) $data['co'],
            'nitrogen' => (float) $data['nitrogen'],
        ]);

        $recommendation = $this->recommendationService->getRecommendation($aqi);

        $reading = Reading::create([
            ...$data,
            'aqi' => $aqi,
            'status' => $recommendation['status'],
            'color' => $recommendation['color'],
        ]);

        return (new ReadingResource($reading))
            ->additional(['recommendation' => $recommendation])
            ->response()
            ->setStatusCode(201);
    }

    public function latest(): JsonResponse
    {
        $reading = Reading::latest()->first();

        if (! $reading) {
            return response()->json(['message' => 'No readings yet.'], 404);
        }

        $recommendation = $this->recommendationService->getRecommendation($reading->aqi);

        return (new ReadingResource($reading))
            ->additional(['recommendation' => $recommendation])
            ->response();
    }
}
