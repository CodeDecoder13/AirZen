<?php

namespace Tests\Feature;

use App\Models\SensorReading;
use App\Services\MorningDigestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MorningDigestServiceTest extends TestCase
{
    use RefreshDatabase;

    private MorningDigestService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MorningDigestService::class);
    }

    public function test_returns_null_when_no_readings_exist_today()
    {
        $this->assertNull($this->service->build());
    }

    public function test_ignores_readings_from_before_today()
    {
        SensorReading::factory()->create(['type' => 'ParticulateMatter', 'value' => 50.0, 'created_at' => now()->subDay()]);

        $this->assertNull($this->service->build());
    }

    public function test_averages_multiple_readings_per_type_from_today()
    {
        SensorReading::factory()->create(['type' => 'TEMPERATURE', 'value' => 24.0, 'created_at' => now()]);
        SensorReading::factory()->create(['type' => 'TEMPERATURE', 'value' => 26.0, 'created_at' => now()]);
        SensorReading::factory()->create(['type' => 'ParticulateMatter', 'value' => 0.0, 'created_at' => now()]);

        $digest = $this->service->build();

        $this->assertSame(25.0, $digest['readings']['temperature']);
        $this->assertSame(0.0, $digest['aqi']);
    }

    public function test_computes_aqi_and_recommendation_from_average_pm25()
    {
        SensorReading::factory()->create(['type' => 'ParticulateMatter', 'value' => 0.0, 'created_at' => now()]);
        SensorReading::factory()->create(['type' => 'ParticulateMatter', 'value' => 30.8, 'created_at' => now()]);

        $digest = $this->service->build();

        // average pm25 = 15.4 -> top of first bracket -> AQI 50 -> "Good"
        $this->assertSame(50.0, $digest['aqi']);
        $this->assertSame('Good', $digest['status']);
        $this->assertCount(3, $digest['recommendations']);
    }

    public function test_aqi_is_null_when_pm25_has_no_readings_today_even_if_other_types_do()
    {
        SensorReading::factory()->create(['type' => 'TEMPERATURE', 'value' => 24.0, 'created_at' => now()]);

        $digest = $this->service->build();

        $this->assertNotNull($digest);
        $this->assertNull($digest['aqi']);
        $this->assertNull($digest['status']);
        $this->assertSame([], $digest['recommendations']);
    }
}
