<?php

namespace Tests\Feature;

use App\Models\SensorReading;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LatestSensorReadingTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_authentication()
    {
        $response = $this->getJson('/api/readings/latest');

        $response->assertStatus(401);
    }

    public function test_returns_the_latest_value_per_type_and_the_calculated_aqi()
    {
        $user = User::factory()->create();

        // Older PM2.5 reading, should be superseded by the newer one below.
        SensorReading::factory()->create(['type' => 'ParticulateMatter', 'value' => 5.0, 'created_at' => now()->subMinutes(10)]);

        SensorReading::factory()->create(['type' => 'TEMPERATURE', 'value' => 25.0]);
        SensorReading::factory()->create(['type' => 'HUMIDITY', 'value' => 55.0]);
        SensorReading::factory()->create(['type' => 'NITROGEN', 'value' => 40.0]);
        SensorReading::factory()->create(['type' => 'C0', 'value' => 4.5]);
        $latest = SensorReading::factory()->create(['type' => 'ParticulateMatter', 'value' => 25.45, 'created_at' => now()]);

        $response = $this->actingAs($user)->getJson('/api/readings/latest');

        $response->assertOk()
            ->assertJsonPath('readings.temperature', 25)
            ->assertJsonPath('readings.humidity', 55)
            ->assertJsonPath('readings.nitrogen', 40)
            ->assertJsonPath('readings.co', 4.5)
            ->assertJsonPath('readings.particulate_matter', 25.45)
            ->assertJsonPath('status', 'Normal')
            ->assertJsonPath('color', '#EAB308')
            ->assertJsonPath('updated_at', $latest->created_at->toIso8601String())
            ->assertJsonCount(3, 'recommendations');

        $aqi = $response->json('aqi');
        $this->assertGreaterThan(74.0, $aqi);
        $this->assertLessThan(77.0, $aqi);
    }

    public function test_returns_null_for_types_with_no_readings_yet_and_zero_aqi()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/readings/latest');

        $response->assertOk()
            ->assertJsonPath('readings.temperature', null)
            ->assertJsonPath('readings.particulate_matter', null)
            ->assertJsonPath('updated_at', null)
            ->assertJsonPath('aqi', 0)
            ->assertJsonPath('status', 'Good');
    }
}
