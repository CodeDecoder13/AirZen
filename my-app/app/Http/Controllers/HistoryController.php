<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\SensorReadingResource;
use App\Models\SensorReading;
use App\Services\AqiHistoryService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class HistoryController extends Controller
{
    private const ALLOWED_DAY_RANGES = [7, 30, 90];

    private const ALLOWED_CHART_TYPES = ['line', 'bar', 'pie'];

    public function __construct(
        private readonly AqiHistoryService $historyService,
    ) {}

    public function index(Request $request): Response
    {
        $days = (int) $request->query('days', 30);
        if (! in_array($days, self::ALLOWED_DAY_RANGES, true)) {
            $days = 30;
        }

        $type = (string) $request->query('type', '');

        $metric = (string) $request->query('metric', SensorReading::PARTICULATE_MATTER);
        if (! in_array($metric, SensorReading::TYPES, true)) {
            $metric = SensorReading::PARTICULATE_MATTER;
        }

        $chartType = (string) $request->query('chartType', 'line');
        if (! in_array($chartType, self::ALLOWED_CHART_TYPES, true)) {
            $chartType = 'line';
        }

        $dailyAverages = $this->historyService->dailyAverages($days);
        $playgroundDaily = $this->historyService->dailyMetricAverages($metric, $days);

        return Inertia::render('History', [
            'days' => $days,
            'filters' => [
                'type' => $type,
                'from' => $request->query('from', ''),
                'to' => $request->query('to', ''),
            ],
            'trend' => array_map(
                fn (array $day): array => ['value' => $day['average_aqi'] ?? 0.0, 'recorded_at' => $day['date']],
                array_values(array_filter($dailyAverages, fn (array $day): bool => $day['average_aqi'] !== null)),
            ),
            'bandDistribution' => $this->historyService->bandDistribution($days),
            'readings' => $this->historyService->paginatedReadings([
                'type' => $type,
                'from' => $request->query('from'),
                'to' => $request->query('to'),
            ], 20)->through(
                fn ($reading): array => (new SensorReadingResource($reading))->resolve(),
            ),
            'playground' => [
                'metric' => $metric,
                'chartType' => $chartType,
                'daily' => $playgroundDaily,
                'buckets' => $this->historyService->valueBuckets($playgroundDaily),
            ],
        ]);
    }
}
