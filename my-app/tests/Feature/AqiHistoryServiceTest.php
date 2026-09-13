<?php

namespace Tests\Feature;

use App\Models\SensorReading;
use App\Services\AqiHistoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AqiHistoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private AqiHistoryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AqiHistoryService::class);
    }

    public function test_pm25_trend_returns_readings_oldest_to_newest()
    {
        SensorReading::factory()->create(['type' => 'ParticulateMatter', 'value' => 8.4, 'created_at' => now()->subMinutes(10)]);
        SensorReading::factory()->create(['type' => 'ParticulateMatter', 'value' => 12.1, 'created_at' => now()->subMinutes(5)]);
        SensorReading::factory()->create(['type' => 'ParticulateMatter', 'value' => 9.7, 'created_at' => now()]);
        SensorReading::factory()->create(['type' => 'TEMPERATURE', 'value' => 25.0, 'created_at' => now()]);

        $trend = $this->service->pm25Trend();

        $this->assertCount(3, $trend);
        $this->assertSame(8.4, $trend[0]['value']);
        $this->assertSame(12.1, $trend[1]['value']);
        $this->assertSame(9.7, $trend[2]['value']);
        $this->assertArrayHasKey('recorded_at', $trend[0]);
    }

    public function test_pm25_trend_is_capped_at_the_given_limit()
    {
        for ($i = 0; $i < 5; $i++) {
            SensorReading::factory()->create(['type' => 'ParticulateMatter', 'value' => (float) $i, 'created_at' => now()->subMinutes(5 - $i)]);
        }

        $trend = $this->service->pm25Trend(limit: 3);

        $this->assertCount(3, $trend);
        // Newest 3 (values 2,3,4), kept in chronological order.
        $this->assertSame([2.0, 3.0, 4.0], array_column($trend, 'value'));
    }

    public function test_weekly_average_aqi_has_seven_days_ending_today()
    {
        SensorReading::factory()->create(['type' => 'ParticulateMatter', 'value' => 10.0, 'created_at' => now()]);

        $weekly = $this->service->weeklyAverageAqi();

        $this->assertCount(7, $weekly);
        $this->assertTrue($weekly[6]['is_today']);
        $this->assertNotNull($weekly[6]['average_aqi']);
    }

    public function test_weekly_average_aqi_is_null_for_days_with_no_readings()
    {
        // Only today has data; the other 6 days should report no average, not a fabricated 0.
        SensorReading::factory()->create(['type' => 'ParticulateMatter', 'value' => 10.0, 'created_at' => now()]);

        $weekly = $this->service->weeklyAverageAqi();

        foreach (array_slice($weekly, 0, 6) as $day) {
            $this->assertNull($day['average_aqi']);
        }
    }

    public function test_weekly_average_aqi_averages_multiple_readings_on_the_same_day()
    {
        SensorReading::factory()->create(['type' => 'ParticulateMatter', 'value' => 0.0, 'created_at' => now()]);
        SensorReading::factory()->create(['type' => 'ParticulateMatter', 'value' => 30.8, 'created_at' => now()]);

        $weekly = $this->service->weeklyAverageAqi();

        // Average PM2.5 = 15.4 -> top of the first breakpoint bracket -> AQI 50.
        $this->assertSame(50.0, $weekly[6]['average_aqi']);
    }
}
