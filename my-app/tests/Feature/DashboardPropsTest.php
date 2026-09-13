<?php

namespace Tests\Feature;

use App\Models\SensorReading;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardPropsTest extends TestCase
{
    use RefreshDatabase;

    public function test_passes_the_latest_reading_snapshot_as_an_initial_prop()
    {
        $user = User::factory()->create();
        SensorReading::factory()->create(['type' => 'ParticulateMatter', 'value' => 25.45]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('initialSnapshot')
            ->where('initialSnapshot.readings.particulate_matter', 25.45)
            ->where('initialSnapshot.status', 'Normal'));
    }

    public function test_passes_a_zero_aqi_snapshot_when_no_readings_exist_yet()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('initialSnapshot.aqi', 0)
            ->where('initialSnapshot.status', 'Good'));
    }
}
