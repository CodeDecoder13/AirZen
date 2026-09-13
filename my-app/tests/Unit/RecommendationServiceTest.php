<?php

namespace Tests\Unit;

use App\Services\RecommendationService;
use PHPUnit\Framework\TestCase;

class RecommendationServiceTest extends TestCase
{
    private RecommendationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RecommendationService();
    }

    public function test_returns_the_good_band_for_aqi_0_50()
    {
        $result = $this->service->getRecommendation(30.0);

        $this->assertSame('Good', $result['status']);
        $this->assertSame('#22C55E', $result['color']);
        $this->assertCount(3, $result['recommendations']);
        $this->assertSame(
            'Keep windows and doors open to allow fresh air circulation.',
            $result['recommendations'][0]
        );
    }

    public function test_returns_the_normal_band_for_aqi_51_100()
    {
        $result = $this->service->getRecommendation(75.0);

        $this->assertSame('Normal', $result['status']);
        $this->assertSame('#EAB308', $result['color']);
    }

    public function test_returns_the_unhealthy_for_sensitive_groups_band_for_aqi_101_150()
    {
        $result = $this->service->getRecommendation(120.0);

        $this->assertSame('Unhealthy for Sensitive Groups', $result['status']);
        $this->assertSame('#F59E0B', $result['color']);
    }

    public function test_returns_the_unhealthy_for_all_groups_band_for_aqi_151_200()
    {
        $result = $this->service->getRecommendation(175.0);

        $this->assertSame('Unhealthy for All Groups', $result['status']);
        $this->assertSame('#EF4444', $result['color']);
    }

    public function test_returns_the_very_unhealthy_band_for_aqi_201_300()
    {
        $result = $this->service->getRecommendation(250.0);

        $this->assertSame('Very Unhealthy', $result['status']);
        $this->assertSame('#A855F7', $result['color']);
    }

    public function test_falls_back_to_the_worst_band_when_aqi_exceeds_300()
    {
        $result = $this->service->getRecommendation(450.0);

        $this->assertSame('Very Unhealthy', $result['status']);
        $this->assertSame('#A855F7', $result['color']);
    }
}
