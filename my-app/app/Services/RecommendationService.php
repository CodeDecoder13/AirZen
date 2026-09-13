<?php

declare(strict_types=1);

namespace App\Services;

final class RecommendationService
{
    /**
     * @var list<array{max: float, status: string, color: string, recommendations: list<string>}>
     */
    private const BANDS = [
        [
            'max' => 50.0,
            'status' => 'Good',
            'color' => '#22C55E',
            'recommendations' => [
                'Keep windows and doors open to allow fresh air circulation.',
                'Regularly clean and dust the space to minimize allergens.',
                'Indoor plants like peace lilies or spider plants can help maintain this air quality.',
            ],
        ],
        [
            'max' => 100.0,
            'status' => 'Normal',
            'color' => '#EAB308',
            'recommendations' => [
                'Enhance ventilation by opening windows or using fans.',
                'Use natural cleaning products to reduce VOC release.',
                'Keep indoor humidity in check to prevent mold and mildew.',
            ],
        ],
        [
            'max' => 150.0,
            'status' => 'Unhealthy for Sensitive Groups',
            'color' => '#F59E0B',
            'recommendations' => [
                'Utilize air purifiers with HEPA filters where possible.',
                'Minimize activities in the room during peak hours.',
                'Sensitive individuals should consider a mask indoors.',
            ],
        ],
        [
            'max' => 200.0,
            'status' => 'Unhealthy for All Groups',
            'color' => '#EF4444',
            'recommendations' => [
                'Improve ventilation by opening windows or using exhaust fans.',
                'Run air purifiers with HEPA filters continuously.',
                'Avoid smoking or use of harsh chemicals in the room.',
            ],
        ],
        [
            'max' => 300.0,
            'status' => 'Very Unhealthy',
            'color' => '#A855F7',
            'recommendations' => [
                'Close windows and doors to limit outdoor pollutant exposure.',
                'Use additional air filtration if available.',
                'Follow local advisories on indoor air quality and health.',
            ],
        ],
    ];

    /**
     * @return array{status: string, color: string, recommendations: list<string>}
     */
    public function getRecommendation(float $aqi): array
    {
        foreach (self::BANDS as $band) {
            if ($aqi <= $band['max']) {
                return [
                    'status' => $band['status'],
                    'color' => $band['color'],
                    'recommendations' => $band['recommendations'],
                ];
            }
        }

        $worst = self::BANDS[count(self::BANDS) - 1];

        return [
            'status' => $worst['status'],
            'color' => $worst['color'],
            'recommendations' => $worst['recommendations'],
        ];
    }
}
