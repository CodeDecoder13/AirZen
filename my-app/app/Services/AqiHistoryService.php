<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SensorReading;
use Illuminate\Support\Carbon;

final class AqiHistoryService
{
    public function __construct(
        private readonly AqiCalculator $aqiCalculator,
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
     * Seven days ending today. A day with no PM2.5 readings reports a null
     * average rather than a fabricated 0 - the chart must show "no data",
     * never a fake reading.
     *
     * @return list<array{label: string, date: string, average_aqi: ?float, is_today: bool}>
     */
    public function weeklyAverageAqi(): array
    {
        $since = Carbon::now()->startOfDay()->subDays(6);

        $readingsByDate = SensorReading::where('type', SensorReading::PARTICULATE_MATTER)
            ->where('created_at', '>=', $since)
            ->get(['value', 'created_at'])
            ->groupBy(fn (SensorReading $reading): string => $reading->created_at->toDateString());

        $days = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dayReadings = $readingsByDate->get($date->toDateString());

            $days[] = [
                'label' => $date->format('D'),
                'date' => $date->toDateString(),
                'average_aqi' => $dayReadings && $dayReadings->isNotEmpty()
                    ? $this->aqiCalculator->calculateOverallAqi(['pm25' => $dayReadings->avg('value')])
                    : null,
                'is_today' => $date->isToday(),
            ];
        }

        return $days;
    }
}
