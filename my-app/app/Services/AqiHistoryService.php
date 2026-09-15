<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SensorReading;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
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
        return array_map(
            fn (array $day): array => [
                'label' => $day['label'],
                'date' => $day['date'],
                'average_aqi' => $day['average_value'] !== null
                    ? $this->aqiCalculator->calculateOverallAqi(['pm25' => $day['average_value']])
                    : null,
                'is_today' => $day['is_today'],
            ],
            $this->dailyMetricAverages(SensorReading::PARTICULATE_MATTER, $days),
        );
    }

    /**
     * Same $days-days-ending-today shape as dailyAverages(), but the raw
     * average value for any sensor type instead of an AQI conversion - powers
     * the History playground, which has no health-band scale for most
     * sensors. A day with no readings reports a null average.
     *
     * @return list<array{label: string, date: string, average_value: ?float, is_today: bool}>
     */
    public function dailyMetricAverages(string $type, int $days): array
    {
        $since = Carbon::now()->startOfDay()->subDays($days - 1);

        $readingsByDate = SensorReading::where('type', $type)
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
                'average_value' => $dayReadings && $dayReadings->isNotEmpty()
                    ? round($dayReadings->avg('value'), 2)
                    : null,
                'is_today' => $date->isToday(),
            ];
        }

        return $result;
    }

    /**
     * Splits a set of daily averages into three roughly-even Low/Mid/High
     * buckets by their own observed min-max range - not a health-band scale
     * (most sensors don't have one), just a relative-occurrence lens for the
     * playground's pie view.
     *
     * @param list<array{average_value: ?float}> $dailyAverages
     * @return array<string, int>
     */
    public function valueBuckets(array $dailyAverages): array
    {
        $values = array_values(array_filter(
            array_column($dailyAverages, 'average_value'),
            fn (?float $value): bool => $value !== null,
        ));

        if ($values === []) {
            return [];
        }

        $min = min($values);
        $range = max(max($values) - $min, 0.0001);
        $counts = ['Low' => 0, 'Mid' => 0, 'High' => 0];

        foreach ($values as $value) {
            $ratio = ($value - $min) / $range;
            $bucket = match (true) {
                $ratio < 1 / 3 => 'Low',
                $ratio < 2 / 3 => 'Mid',
                default => 'High',
            };
            $counts[$bucket]++;
        }

        return $counts;
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
        return $this->filteredQuery($filters)->paginate($perPage)->withQueryString();
    }

    /**
     * Same filters as paginatedReadings(), unpaginated, for CSV/PDF export.
     * Capped so a runaway date range can't exhaust memory generating a file.
     *
     * @param array{type?: ?string, from?: ?string, to?: ?string} $filters
     * @return Collection<int, SensorReading>
     */
    public function exportableReadings(array $filters, int $limit = 10000): Collection
    {
        return $this->filteredQuery($filters)->limit($limit)->get();
    }

    /**
     * @param array{type?: ?string, from?: ?string, to?: ?string} $filters
     */
    private function filteredQuery(array $filters): Builder
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

        return $query;
    }
}
