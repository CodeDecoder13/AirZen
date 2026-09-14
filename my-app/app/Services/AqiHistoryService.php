<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SensorReading;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

final class AqiHistoryService
{
    public function __construct(
        private readonly AqiCalculator $aqiCalculator,
        private readonly RecommendationService $recommendationService,
    ) {}

    /**
     * @return list<array{value: float, recorded_at: string}>
     */
    public function pm25Trend(int $limit = 20): array
    {
        return SensorReading::where('type', SensorReading::PARTICULATE_MATTER)
            ->latest()
            ->limit($limit)
            ->get(['value', 'created_at'])
            ->reverse()
            ->values()
            ->map(fn (SensorReading $reading): array => [
                'value' => $reading->value,
                'recorded_at' => $reading->created_at->toIso8601String(),
            ])
            ->all();
    }

    /**
     * @return list<array{label: string, date: string, average_aqi: ?float, is_today: bool}>
     */
    public function weeklyAverageAqi(): array
    {
        return $this->dailyAverages(7);
    }

    /**
     * $days days ending today. A day with no PM2.5 readings reports a null
     * average rather than a fabricated 0 - the chart must show "no data",
     * never a fake reading.
     *
     * @return list<array{label: string, date: string, average_aqi: ?float, is_today: bool}>
     */
    public function dailyAverages(int $days): array
    {
        $since = Carbon::now()->startOfDay()->subDays($days - 1);

        $readingsByDate = SensorReading::where('type', SensorReading::PARTICULATE_MATTER)
            ->where('created_at', '>=', $since)
            ->get(['value', 'created_at'])
            ->groupBy(fn (SensorReading $reading): string => $reading->created_at->toDateString());

        $result = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dayReadings = $readingsByDate->get($date->toDateString());

            $result[] = [
                'label' => $date->format('D'),
                'date' => $date->toDateString(),
                'average_aqi' => $dayReadings && $dayReadings->isNotEmpty()
                    ? $this->aqiCalculator->calculateOverallAqi(['pm25' => $dayReadings->avg('value')])
                    : null,
                'is_today' => $date->isToday(),
            ];
        }

        return $result;
    }

    /**
     * How many days in the range fell into each AQI status band. Days with
     * no PM2.5 data are excluded entirely (not counted as any band).
     *
     * @return array<string, int>
     */
    public function bandDistribution(int $days): array
    {
        $counts = [];

        foreach ($this->dailyAverages($days) as $day) {
            if ($day['average_aqi'] === null) {
                continue;
            }

            $status = $this->recommendationService->getRecommendation($day['average_aqi'])['status'];
            $counts[$status] = ($counts[$status] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * @param array{type?: ?string, from?: ?string, to?: ?string} $filters
     */
    public function paginatedReadings(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = SensorReading::query()->latest();

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['from'])->startOfDay());
        }

        if (! empty($filters['to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['to'])->endOfDay());
        }

        return $query->paginate($perPage)->withQueryString();
    }
}
