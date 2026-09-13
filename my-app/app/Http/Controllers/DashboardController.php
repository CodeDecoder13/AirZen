<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\ReadingResource;
use App\Models\Reading;
use App\Services\RecommendationService;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly RecommendationService $recommendationService,
    ) {}

    public function index(): Response
    {
        $reading = Reading::latest()->first();

        return Inertia::render('Dashboard', [
            'initialReading' => $reading ? (new ReadingResource($reading))->resolve() : null,
            'initialRecommendation' => $reading ? $this->recommendationService->getRecommendation($reading->aqi) : null,
        ]);
    }
}
