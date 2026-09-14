<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SensorReading;
use Illuminate\Support\Carbon;

final class MorningDigestService
{
    public function __construct(
        private readonly AqiCalculator $aqiCalculator,
        private readonly RecommendationService $recommendationService,
    ) {}

    /**
     * Averages of today's readings so far, plus the AQI/recommendation they
     * imply. Returns null when nothing has been recorded yet today - the
     * command skips sending rather than emailing a fabricated report.
     *
     * @return array{
     *     date: string,
     *     readings: array{temperature: ?float, humidity: ?float, nitrogen: ?float, co: ?float, particulate_matter: ?float},
     *     aqi: ?float,
     *     status: ?string,
     *     color: ?string,
     *     recommendations: list<string>,
     * }|null
     */
    public function build(): ?array
    {
        $since = Carbon::today();

        $readings = [
            'temperature' => $this->averageSince(SensorReading::TEMPERATURE, $since),
            'humidity' => $this->averageSince(SensorReading::HUMIDITY, $since),
            'nitrogen' => $this->averageSince(SensorReading::NITROGEN, $since),
            'co' => $this->averageSince(SensorReading::C0, $since),
            'particulate_matter' => $this->averageSince(SensorReading::PARTICULATE_MATTER, $since),
        ];

        if (collect($readings)->every(fn (?float $value): bool => $value === null)) {
            return null;
        }

        // AQI is PM2.5-driven (see AqiCalculator) - without a PM2.5 average
        // today, report the raw readings honestly but no AQI/recommendation
        // rather than defaulting to a false "0 / Good".
        $aqi = $readings['particulate_matter'] !== null
            ? $this->aqiCalculator->calculateOverallAqi(['pm25' => $readings['particulate_matter']])
            : null;

        $recommendation = $aqi !== null ? $this->recommendationService->getRecommendation($aqi) : null;

        return [
            'date' => $since->toDateString(),
            'readings' => $readings,
            'aqi' => $aqi,
            'status' => $recommendation['status'] ?? null,
            'color' => $recommendation['color'] ?? null,
            'recommendations' => $recommendation['recommendations'] ?? [],
        ];
    }

    private function averageSince(string $type, Carbon $since): ?float
    {
        $average = SensorReading::where('type', $type)->where('created_at', '>=', $since)->avg('value');

        return $average !== null ? (float) $average : null;
    }
}
