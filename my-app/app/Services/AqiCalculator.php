<?php

declare(strict_types=1);

namespace App\Services;

final class AqiCalculator
{
    /**
     * @var array<string, list<array{bpLo: float, bpHi: float, iLo: float, iHi: float}>>
     */
    private const BREAKPOINTS = [
        'pm25' => [
            ['bpLo' => 0.0, 'bpHi' => 15.4, 'iLo' => 0.0, 'iHi' => 50.0],
            ['bpLo' => 15.5, 'bpHi' => 35.4, 'iLo' => 51.0, 'iHi' => 100.0],
            ['bpLo' => 35.5, 'bpHi' => 54.4, 'iLo' => 101.0, 'iHi' => 150.0],
            ['bpLo' => 54.5, 'bpHi' => 150.4, 'iLo' => 151.0, 'iHi' => 200.0],
            ['bpLo' => 150.5, 'bpHi' => 250.4, 'iLo' => 201.0, 'iHi' => 300.0],
        ],
        'co' => [
            ['bpLo' => 0.0, 'bpHi' => 4.4, 'iLo' => 0.0, 'iHi' => 50.0],
            ['bpLo' => 4.5, 'bpHi' => 9.4, 'iLo' => 51.0, 'iHi' => 100.0],
            ['bpLo' => 9.5, 'bpHi' => 12.4, 'iLo' => 101.0, 'iHi' => 150.0],
            ['bpLo' => 12.5, 'bpHi' => 15.4, 'iLo' => 151.0, 'iHi' => 200.0],
            ['bpLo' => 15.5, 'bpHi' => 30.4, 'iLo' => 201.0, 'iHi' => 300.0],
        ],
        'nitrogen' => [
            ['bpLo' => 0.0, 'bpHi' => 40.0, 'iLo' => 0.0, 'iHi' => 50.0],
            ['bpLo' => 41.0, 'bpHi' => 100.0, 'iLo' => 51.0, 'iHi' => 100.0],
            ['bpLo' => 101.0, 'bpHi' => 150.0, 'iLo' => 101.0, 'iHi' => 150.0],
            ['bpLo' => 150.0, 'bpHi' => 200.0, 'iLo' => 151.0, 'iHi' => 200.0],
            ['bpLo' => 201.0, 'bpHi' => 300.0, 'iLo' => 201.0, 'iHi' => 300.0],
        ],
    ];

    public function calculateSubIndex(string $pollutant, float $concentration): ?float
    {
        foreach (self::BREAKPOINTS[$pollutant] ?? [] as $bracket) {
            if ($concentration >= $bracket['bpLo'] && $concentration <= $bracket['bpHi']) {
                return (($bracket['iHi'] - $bracket['iLo']) / ($bracket['bpHi'] - $bracket['bpLo']))
                    * ($concentration - $bracket['bpLo'])
                    + $bracket['iLo'];
            }
        }

        return null;
    }

    /**
     * @param array{pm25: float, co: float, nitrogen: float} $readings
     */
    public function calculateOverallAqi(array $readings): float
    {
        $subIndices = array_filter([
            $this->calculateSubIndex('pm25', $readings['pm25']),
            $this->calculateSubIndex('co', $readings['co']),
            $this->calculateSubIndex('nitrogen', $readings['nitrogen']),
        ], static fn (?float $value): bool => $value !== null);

        return $subIndices === [] ? 0.0 : max($subIndices);
    }
}
