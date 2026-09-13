<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SensorReading;

final class LatestReadingSnapshot
{
    public function __construct(
        private readonly AqiCalculator $aqiCalculator,
        private readonly RecommendationService $recommendationService,
    ) {}

    /**
     * @return array{
     *     readings: array{temperature: ?float, humidity: ?float, nitrogen: ?float, co: ?float, particulate_matter: ?float},
     *     aqi: float,
     *     status: string,
     *     color: string,
     *     recommendations: list<string>,
     * }
     */
    public function get(): array
    {
        $readings = [
            'temperature' => $this->latestValue(SensorReading::TEMPERATURE),
            'humidity' => $this->latestValue(SensorReading::HUMIDITY),
            'nitrogen' => $this->latestValue(SensorReading::NITROGEN),
            'co' => $this->latestValue(SensorReading::C0),
            'particulate_matter' => $this->latestValue(SensorReading::PARTICULATE_MATTER),
        ];

        $aqi = $this->aqiCalculator->calculateOverallAqi(['pm25' => $readings['particulate_matter'] ?? 0.0]);
        $recommendation = $this->recommendationService->getRecommendation($aqi);

        return [
            'readings' => $readings,
            'aqi' => $aqi,
            'status' => $recommendation['status'],
            'color' => $recommendation['color'],
            'recommendations' => $recommendation['recommendations'],
        ];
    }

    private function latestValue(string $type): ?float
    {
        return SensorReading::where('type', $type)->latest()->value('value');
    }
}
